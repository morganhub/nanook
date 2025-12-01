<?php


require_once __DIR__ . '/../../services/TextService.php';
if (!isset($product) || !$product) {
    echo "<div class='nk-container' style='padding:100px; text-align:center;'>Produit introuvable.</div>";
    return;
}

$pdo = getPdo();


$commonImages = [];
$variantImagesMap = [];

if (!empty($product['images'])) {
    foreach ($product['images'] as $img) {
        if ($img['variant_id'] === null) {
            $commonImages[] = $img;
        } else {
            $vid = (int)$img['variant_id'];
            if (!isset($variantImagesMap[$vid])) $variantImagesMap[$vid] = [];
            $variantImagesMap[$vid][] = $img;
        }
    }
}
if (empty($commonImages)) {
    $commonImages[] = ['file_path' => null];
}


$hasVariants = !empty($product['variants']);
$attributesDisplay = [];
$combinationsMap = [];
$jsCombinations = [];
$firstVariantId = null;

if ($hasVariants) {
    $stmtAttrs = $pdo->prepare("
        SELECT 
            v.id as variant_id,
            a.id as attr_id, a.public_name as attr_name, a.type as attr_type, a.display_order as attr_order,
            o.id as opt_id, o.name as opt_name, o.value as opt_value, o.display_order as opt_order
        FROM nanook_product_variants v
        JOIN nanook_product_variant_combinations pvc ON v.id = pvc.variant_id
        JOIN nanook_attribute_options o ON pvc.option_id = o.id
        JOIN nanook_attributes a ON o.attribute_id = a.id
        WHERE v.product_id = :pid AND v.is_active = 1
        ORDER BY a.display_order ASC, o.display_order ASC
    ");
    $stmtAttrs->execute([':pid' => $product['id']]);
    $rows = $stmtAttrs->fetchAll(PDO::FETCH_ASSOC);

    $tempOptions = [];
    foreach ($rows as $row) {
        $aid = $row['attr_id'];
        $oid = $row['opt_id'];

        if (!isset($attributesDisplay[$aid])) {
            $attributesDisplay[$aid] = [
                'id' => $aid, 
                'name' => $row['attr_name'],
                'type' => $row['attr_type'],
                'options' => []
            ];
        }
        if (!isset($tempOptions[$aid][$oid])) {
            $attributesDisplay[$aid]['options'][] = [
                'id' => $oid,
                'name' => $row['opt_name'],
                'value' => $row['opt_value']
            ];
            $tempOptions[$aid][$oid] = true;
        }

        if (!isset($combinationsMap[$row['variant_id']])) {
            $combinationsMap[$row['variant_id']] = [];
        }
        $combinationsMap[$row['variant_id']][] = (int)$oid;
    }

    foreach ($product['variants'] as $v) {
        $vid = (int)$v['id'];
        if (!isset($combinationsMap[$vid])) continue;

        $opts = $combinationsMap[$vid];
        sort($opts);
        $key = implode('_', $opts);

        $jsCombinations[$key] = [
            'id' => $vid,
            'price' => (float)$v['price'],
            'stock' => (int)$v['stock_quantity'],
            'preorder' => (int)$v['allow_preorder_when_oos'],
            'date' => $v['availability_date'],
            'desc' => nl2br(htmlspecialchars($v['short_description'] ?? '')),
            'images' => $variantImagesMap[$vid] ?? []
        ];

        if (!$firstVariantId) $firstVariantId = $vid;
    }
}


if ($firstVariantId && isset($product['variants'][0])) {
    $initPrice = (float)$product['variants'][0]['price'];
    if (!$initPrice) $initPrice = (float)$product['price'];
} else {
    $initPrice = (float)$product['price'];
}
?>

<div class="nk-product-page">
    <div class="nk-container nk-product-layout">

        <div class="nk-product-visuals">
            <div class="nk-gallery-container" id="productGallery">
                <?php
                $initImages = ($firstVariantId && !empty($variantImagesMap[$firstVariantId]))
                    ? $variantImagesMap[$firstVariantId]
                    : $commonImages;

                $firstImgSrc = '/assets/img/placeholder.jpg';
                if (!empty($initImages[0]['file_path'])) {
                    $firstImgSrc = '/storage/product_images/' . $initImages[0]['file_path'];
                }
                ?>

                <div class="nk-main-image-wrapper">
                    <img src="<?= htmlspecialchars($firstImgSrc) ?>" id="mainImg" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>

                <div class="nk-thumbnails" id="thumbsContainer">
                    <?php foreach ($initImages as $index => $img): ?>
                        <?php
                        $src = ($img['file_path']) ? '/storage/product_images/' . $img['file_path'] : '/assets/img/placeholder.jpg';
                        $activeClass = ($index === 0) ? 'active' : '';
                        ?>
                        <div class="nk-thumb <?= $activeClass ?>" data-index="<?= $index ?>" data-src="<?= htmlspecialchars($src) ?>">
                            <img src="<?= htmlspecialchars($src) ?>" alt="Vignette <?= $index ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="nk-product-sidebar-wrapper">
            <div class="nk-product-sidebar">

                <div class="nk-mobile-header">
                    <h1 class="nk-product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="nk-product-price js-price-display"><?= number_format($initPrice, 2, ',', ' ') ?> €</div>
                </div>

                <div class="nk-desktop-header">
                    <h1 class="nk-product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="nk-product-price js-price-display"><?= number_format($initPrice, 2, ',', ' ') ?> €</div>
                </div>

                <form id="addToCartForm" class="nk-product-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="variant_id" id="selectedVariantId" value="">

                    <?php if (!empty($attributesDisplay)): ?>
                        <div class="nk-attributes-section">
                            <?php foreach ($attributesDisplay as $attrId => $attr): ?>
                                <div class="nk-form-group js-attr-group" data-group-id="<?= $attrId ?>">
                                    <div class="nk-label">
                                        <?= htmlspecialchars($attr['name']) ?>
                                        <span class="js-selected-val" style="font-weight:400; color:#666;font-size:0.9em;"></span>
                                    </div>

                                    <div class="nk-attr-options">
                                        <?php foreach ($attr['options'] as $opt): ?>
                                            <label class="nk-attr-label" data-fltooltip="<?= htmlspecialchars($opt['name']) ?>">
                                                <input type="radio"
                                                       name="attr_<?= $attrId ?>"
                                                       value="<?= $opt['id'] ?>"
                                                       class="js-attr-radio"
                                                       data-attr-id="<?= $attrId ?>"
                                                       data-name="<?= htmlspecialchars($opt['name']) ?>">

                                                <?php
                                                $val = $opt['value'];

                                                
                                                if ($attr['type'] === 'color'): ?>
                                                    <span class="nk-swatch-color" style="background-color: <?= htmlspecialchars($val) ?>;"></span>

                                                <?php elseif ($attr['type'] === 'image' && $val): ?>
                                                    <span class="nk-swatch-image" style="background-image: url('/storage/<?= htmlspecialchars($val) ?>');">
                                                        <?= htmlspecialchars($opt['name']) ?>
                                                    </span>

                                                <?php else: ?>
                                                    <span class="nk-swatch-text"><?= htmlspecialchars($opt['name']) ?></span>
                                                <?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($product['customizations'])): ?>
                        <div class="nk-customizations-section">
                            <?php foreach ($product['customizations'] as $cust): ?>
                                <div class="nk-form-group">
                                    <label class="nk-label">
                                        <?= htmlspecialchars($cust['label']) ?> <?= $cust['is_required'] ? '*' : '' ?>
                                    </label>
                                    <?php if ($cust['field_type'] === 'select'): ?>
                                        <div class="nk-select-wrapper">
                                            <select name="customization[<?= $cust['id'] ?>]" class="nk-input" <?= $cust['is_required'] ? 'required' : '' ?>>
                                                <option value="">Sélectionner...</option>
                                                <?php foreach ($cust['options'] as $opt): ?>
                                                    <option value="<?= $opt['id'] ?>"><?= htmlspecialchars($opt['label']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php else: ?>
                                        <input type="text" name="customization[<?= $cust['id'] ?>]" class="nk-input" placeholder="Votre texte..." <?= $cust['is_required'] ? 'required' : '' ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="nk-form-actions">
                        <div class="nk-qty-wrapper">
                            <button type="button" class="js-qty-btn" data-action="dec">-</button>
                            <input type="number" name="quantity" id="quantityInput" value="1" min="1" readonly>
                            <button type="button" class="js-qty-btn" data-action="inc">+</button>
                        </div>

                        <button type="submit" class="nk-btn-add" data-fltooltip="Ajouter ce produit au panier" id="btnAddToCart" <?= $hasVariants ? 'disabled' : '' ?>>
                            <?= $hasVariants ? 'Choisir les options' : 'Ajouter au panier' ?>
                        </button>
                    </div>

                    <div id="stockMessageArea" class="nk-stock-warning" style="display:none; margin-top:15px;"></div>
                </form>

                <div class="nk-product-desc-short" id="shortDescDisplay">
                    <?= autoLinkContact(nl2br(htmlspecialchars($product['short_description'] ?? '')), $pdo) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const productBase = {
        id: <?= $product['id'] ?>,
        price: <?= (float)$product['price'] ?>,
        stock: <?= (int)$product['stock_quantity'] ?>,
        preorder: <?= (int)$product['allow_preorder_when_oos'] ?>,
        date: <?= json_encode($product['availability_date']) ?>,
        desc: <?= json_encode(nl2br(htmlspecialchars($product['short_description'] ?? ''))) ?>,
        images: <?= json_encode($commonImages) ?>
    };

    const combinations = <?= json_encode($jsCombinations) ?>;
</script>