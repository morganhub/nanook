<?php
require_once __DIR__ . '/../../services/ProductService.php';

$slug = $_GET['slug'] ?? '';
$pdo = getPdo();
$product = getProductBySlug($pdo, $slug);

if (!$product) {
    http_response_code(404);
    echo "<div class='nk-container' style='padding:100px;'>Produit introuvable.</div>";
    return;
}

$price = number_format($product['price_cents'] / 100, 2, ',', ' ') . ' €';
?>

<div class="nk-container" style="padding-top: 40px; padding-bottom: 80px;">
    <div style="display: grid; grid-template-columns: 1fr; gap: 40px; @media(min-width:768px){ grid-template-columns: 1.5fr 1fr; }">

        <div class="nk-product-gallery" style="display:flex; flex-direction:column; gap:10px;">
            <?php foreach ($product['images'] as $img): ?>
                <img src="/storage/product_images/<?= htmlspecialchars($img['file_path']) ?>"
                     style="width:100%; display:block;"
                     alt="<?= htmlspecialchars($product['name']) ?>">
            <?php endforeach; ?>
            <?php if(empty($product['images'])): ?>
                <img src="/assets/img/placeholder.jpg" style="width:100%;" alt="Placeholder">
            <?php endif; ?>
        </div>

        <div style="position: sticky; top: 100px; height: fit-content;">

            <h1 class="nk-title-lg" style="margin-bottom:10px;"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="nk-price" style="font-size:1.2rem; margin-bottom:20px;"><?= $price ?></div>

            <div class="nk-text-body" style="margin-bottom:30px;">
                <?= nl2br(htmlspecialchars($product['short_description'] ?? '')) ?>
            </div>

            <form id="addToCartForm" class="nk-product-form">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <?php if (!empty($product['variants'])): ?>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-weight:700; margin-bottom:8px; font-size:0.9rem;">Déclinaison</label>
                        <div style="display:flex; flex-wrap:wrap; gap:10px;">
                            <?php foreach ($product['variants'] as $idx => $v): ?>
                                <?php
                                $vPrice = $v['price_cents'] ? number_format($v['price_cents']/100, 2) . ' €' : '';
                                $label = htmlspecialchars($v['name']) . ($vPrice ? " ($vPrice)" : "");
                                $disabled = ($v['stock_quantity'] <= 0 && !$v['allow_preorder_when_oos']) ? 'disabled' : '';
                                $opacity = $disabled ? '0.5' : '1';
                                ?>
                                <label style="cursor:pointer; opacity:<?= $opacity ?>;">
                                    <input type="radio" name="variant_id" value="<?= $v['id'] ?>" <?= $idx===0 && !$disabled ? 'checked' : '' ?> <?= $disabled ?>>
                                    <span style="border:1px solid #ddd; padding:8px 15px; font-size:0.9rem; display:inline-block;"><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($product['customizations'] as $cust): ?>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; font-weight:700; margin-bottom:8px; font-size:0.9rem;">
                            <?= htmlspecialchars($cust['label']) ?>
                            <?= $cust['is_required'] ? '*' : '(Optionnel)' ?>
                        </label>

                        <?php if ($cust['field_type'] === 'text' || $cust['field_type'] === 'textarea'): ?>
                            <input type="text" name="customization[<?= $cust['id'] ?>]"
                                   placeholder="Votre texte ici..."
                                   style="width:100%; padding:10px; border:1px solid #ddd; font-family:inherit;"
                                <?= $cust['is_required'] ? 'required' : '' ?>>

                        <?php elseif ($cust['field_type'] === 'select'): ?>
                            <select name="customization[<?= $cust['id'] ?>]" style="width:100%; padding:10px; border:1px solid #ddd;" <?= $cust['is_required'] ? 'required' : '' ?>>
                                <option value="">Choisir une option...</option>
                                <?php foreach ($cust['options'] as $opt): ?>
                                    <option value="<?= $opt['id'] ?>"><?= htmlspecialchars($opt['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-weight:700; margin-bottom:8px; font-size:0.9rem;">Quantité</label>
                    <input type="number" name="quantity" value="1" min="1" max="10"
                           style="width:60px; padding:10px; border:1px solid #ddd; text-align:center;">
                </div>

                <button type="submit" class="nk-btn-primary" id="btnAddToCart">
                    Ajouter au panier
                </button>

                <?php if ($product['stock_quantity'] <= 0 && $product['allow_preorder_when_oos']): ?>
                    <p style="font-size:0.8rem; color:#C18C5D; margin-top:10px; text-align:center;">
                        Ce produit est en précommande. Délai de fabrication : 2 semaines.
                    </p>
                <?php endif; ?>
            </form>

            <div style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px;">
                <h3 class="nk-title-md" style="font-size:1.1rem; margin-bottom:10px;">Détails</h3>
                <div class="nk-text-body">
                    <?= nl2br(htmlspecialchars($product['long_description'] ?? '')) ?>
                </div>
            </div>

        </div>
    </div>
</div>