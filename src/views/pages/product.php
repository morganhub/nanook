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
                                <div class="nk-form-group">
                                    <div class="nk-label">
                                        <?= htmlspecialchars($attr['name']) ?>
                                        <span class="js-selected-val" style="font-weight:400; color:#666;font-size:0.9em;"></span>
                                    </div>

                                    <div class="nk-attr-options <?= $attr['type'] === 'color' ? 'is-color' : 'is-box' ?>">
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
                                                $isHex = ($val && strpos($val, '#') === 0);
                                                ?>

                                                <?php if ($attr['type'] === 'color'): ?>
                                                    <span class="nk-swatch-color" style="background-color: <?= htmlspecialchars($val) ?>;"></span>

                                                <?php elseif ($attr['type'] === 'image' && $val && !$isHex): ?>
                                                    <span class="nk-swatch-box" style="background-image: url('/storage/<?= htmlspecialchars($val) ?>'); background-size:cover;  ">
                                                        <?= htmlspecialchars($opt['name']) ?>
                                                    </span>

                                                <?php else: ?>
                                                    <span class="nk-swatch-box"><?= htmlspecialchars($opt['name']) ?></span>
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

<style>
    
    .nk-gallery-container { display: flex; flex-direction: column; gap: 15px; }

    .nk-main-image-wrapper {
        position: relative; width: 100%; aspect-ratio: 4/5;
        overflow: hidden; background: #f9f9f9; cursor: crosshair;
    }
    .nk-main-image-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s; }

    .nk-thumbnails { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; scrollbar-width: none; }
    .nk-thumbnails::-webkit-scrollbar { display: none; }

    .nk-thumb {
        width: 70px; height: 70px; flex-shrink: 0; cursor: pointer;
        border: 1px solid transparent; opacity: 0.6; transition: 0.2s;
    }
    .nk-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .nk-thumb:hover { opacity: 1; }
    .nk-thumb.active { border-color: var(--nk-text-main); opacity: 1; }

    
    .nk-attr-label .nk-swatch-box {
        border: unset;
        border-radius: 16px;
        text-indent:-9999px;
        width:60px; height:60px;
    }
    .nk-attr-label.active .nk-swatch-box {
        border: 4px solid #000;
    }
    .nk-product-desc-short { margin-top: 30px; }
    .nk-attr-options { display: flex; flex-wrap: wrap; gap: 14px; margin-top:6px; }
    .nk-attr-label input { display: none; }
    .nk-attributes-section .nk-form-group + .nk-form-group { margin-top:18px; }

    .nk-attr-options.is-box .nk-swatch-box { display: block; padding: 10px 15px;   cursor: pointer; transition: all 0.2s; font-size: 0.9rem; min-width: 40px; text-align: center; background: #FFF; }
    .nk-attr-label input:checked + .nk-swatch-box { border-color: var(--nk-text-main); background: var(--nk-text-main); color: #FFF; }
    .nk-attr-label input:disabled + .nk-swatch-box { opacity: 0.4; cursor: not-allowed; text-decoration: line-through; background: #f9f9f9; }

    .nk-attr-options.is-color .nk-swatch-color { display: block; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; border: 2px solid transparent; box-shadow: 0 0 0 1px #E5E5E5; transition: all 0.2s; }
    .nk-attr-label input:checked + .nk-swatch-color { box-shadow: 0 0 0 2px #FFF, 0 0 0 3px var(--nk-text-main); transform: scale(1.1); }
    .nk-attr-label input:disabled + .nk-swatch-color { opacity: 0.3; cursor: not-allowed; }
</style>

<script>
    
    const productBase = {
        price: <?= (float)$product['price'] ?>,
        stock: <?= (int)$product['stock_quantity'] ?>,
        preorder: <?= (int)$product['allow_preorder_when_oos'] ?>,
        date: <?= json_encode($product['availability_date']) ?>,
        desc: <?= json_encode(nl2br(htmlspecialchars($product['short_description'] ?? ''))) ?>,
        images: <?= json_encode($commonImages) ?>
    };

    
    const combinations = <?= json_encode($jsCombinations) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const attrRadios = document.querySelectorAll('.js-attr-radio');
        const btnAddToCart = document.getElementById('btnAddToCart');
        const stockMsg = document.getElementById('stockMessageArea');
        const priceDisplay = document.querySelector('.js-price-display');
        const descDisplay = document.getElementById('shortDescDisplay');
        const variantInput = document.getElementById('selectedVariantId');
        const quantityInput = document.getElementById('quantityInput');

        
        const mainImgWrap = document.querySelector('.nk-main-image-wrapper');
        const mainImg = document.getElementById('mainImg');
        const thumbsContainer = document.getElementById('thumbsContainer');

        let currentImages = [];
        let currentIdx = 0;
        let sliderInterval = null;
        let touchStartX = 0;
        let touchEndX = 0;

        
        function startSlider() {
            stopSlider();
            sliderInterval = setInterval(() => nextImage(), 4000);
        }
        function stopSlider() { if(sliderInterval) clearInterval(sliderInterval); }
        function showImage(index) {
            if(!currentImages.length) return;
            if(index >= currentImages.length) index = 0;
            if(index < 0) index = currentImages.length - 1;
            currentIdx = index;
            mainImg.style.opacity = '0.8';
            setTimeout(() => { mainImg.src = currentImages[currentIdx]; mainImg.style.opacity = '1'; }, 100);
            document.querySelectorAll('.nk-thumb').forEach(t => t.classList.remove('active'));
            const activeThumb = document.querySelector(`.nk-thumb[data-index="${currentIdx}"]`);
            if(activeThumb) { activeThumb.classList.add('active'); activeThumb.scrollIntoView({behavior:'smooth', block:'nearest', inline:'center'}); }
        }
        function nextImage() { showImage(currentIdx + 1); }
        function prevImage() { showImage(currentIdx - 1); }

        function initGallery(imagesData) {
            stopSlider();
            currentImages = imagesData.map(img => img.file_path ? '/storage/product_images/' + img.file_path : '/assets/img/placeholder.jpg');
            thumbsContainer.innerHTML = '';
            currentImages.forEach((src, idx) => {
                const thumb = document.createElement('div');
                thumb.className = (idx === 0) ? 'nk-thumb active' : 'nk-thumb';
                thumb.dataset.index = idx;
                thumb.innerHTML = `<img src="${src}" alt="">`;
                thumb.addEventListener('click', () => { stopSlider(); showImage(idx); });
                thumbsContainer.appendChild(thumb);
            });
            currentIdx = 0;
            if(currentImages.length) mainImg.src = currentImages[0];
            if(currentImages.length > 1) startSlider();
        }

        if(mainImgWrap) {
            mainImgWrap.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; stopSlider(); }, {passive: true});
            mainImgWrap.addEventListener('touchend', e => { touchEndX = e.changedTouches[0].screenX; if(touchStartX - touchEndX > 50) nextImage(); if(touchEndX - touchStartX > 50) prevImage(); }, {passive: true});
        }

        initGallery(productBase.images);

        
        const formatPrice = (p) => new Intl.NumberFormat('fr-FR', {style:'decimal', minimumFractionDigits:2}).format(p) + ' €';
        const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', {month:'long', year:'numeric'}) : "date inconnue";

        function updateButtonState(stock, canPreorder, availDate) {
            const currentQty = parseInt(quantityInput.value);
            btnAddToCart.disabled = false;
            btnAddToCart.textContent = "Ajouter au panier";
            btnAddToCart.style.backgroundColor = "#1A1A2E";
            stockMsg.style.display = 'none';

            if (stock <= 0) {
                if (canPreorder) {
                    const dateTxt = availDate ? formatDate(availDate) : 'quelques semaines';
                    stockMsg.style.display = 'block';
                    stockMsg.style.color = '#C18C5D';
                    stockMsg.innerHTML = `Précommande : Expédition prévue en ${dateTxt}`;
                    btnAddToCart.textContent = "Précommander";
                    btnAddToCart.style.backgroundColor = "#C18C5D";
                } else {
                    btnAddToCart.disabled = true;
                    btnAddToCart.textContent = "Rupture de stock";
                    btnAddToCart.style.backgroundColor = "#ccc";
                }
            } else if (currentQty > stock && canPreorder) {
                stockMsg.style.display = 'block';
                stockMsg.style.color = '#C18C5D';
                stockMsg.innerHTML = `Attention : ${stock} en stock immédiat, le reste en précommande.`;
            }
        }

        
        function updateActiveClasses() {
            document.querySelectorAll('.nk-attr-label').forEach(label => {
                const input = label.querySelector('input');
                if (input && input.checked) {
                    label.classList.add('active');
                } else {
                    label.classList.remove('active');
                }
            });
        }

        function updateAttributeLabels() {
            const groups = document.querySelectorAll('.nk-attributes-section .nk-form-group');
            groups.forEach(group => {
                const checked = group.querySelector('input:checked');
                const labelSpan = group.querySelector('.js-selected-val');
                if (labelSpan) {
                    labelSpan.textContent = checked ? ': ' + checked.dataset.name : '';
                }
            });
        }

        function checkCombination() {
            updateActiveClasses(); 
            updateAttributeLabels(); 

            const groups = document.querySelectorAll('.nk-attributes-section .nk-form-group');
            let selectedIds = [];
            let allSelected = true;

            groups.forEach(group => {
                const checked = group.querySelector('input:checked');
                if(checked) selectedIds.push(parseInt(checked.value));
                else allSelected = false;
            });

            if (!allSelected) {
                btnAddToCart.disabled = true;
                btnAddToCart.textContent = "Choisir les options";
                stockMsg.style.display = 'none';
                priceDisplay.textContent = formatPrice(productBase.price);
                return;
            }

            selectedIds.sort((a, b) => a - b);
            const key = selectedIds.join('_');
            const variant = combinations[key];

            if (!variant) {
                btnAddToCart.disabled = true;
                btnAddToCart.textContent = "Indisponible";
                stockMsg.style.display = 'block';
                stockMsg.innerHTML = "<span style='color:#b91c1c'>Cette combinaison n'existe pas.</span>";
                return;
            }

            variantInput.value = variant.id;
            const finalPrice = (variant.price !== null && variant.price > 0) ? variant.price : productBase.price;
            priceDisplay.textContent = formatPrice(finalPrice);

            if (variant.desc && variant.desc.trim() !== "") descDisplay.innerHTML = variant.desc;
            else descDisplay.innerHTML = productBase.desc;

            if (variant.images && variant.images.length > 0) initGallery(variant.images);
            else initGallery(productBase.images);

            updateButtonState(variant.stock, variant.preorder === 1, variant.date);
        }

        if (document.querySelector('.js-attr-radio')) {
            attrRadios.forEach(r => r.addEventListener('change', checkCombination));

            
            const groups = document.querySelectorAll('.nk-attributes-section .nk-form-group');
            let autoSelect = true;
            groups.forEach(g => { if(g.querySelector('input:checked')) autoSelect=false; });

            if(autoSelect && Object.keys(combinations).length > 0) {
                const firstKey = Object.keys(combinations)[0];
                const ids = firstKey.split('_');
                ids.forEach(id => {
                    const input = document.querySelector(`.js-attr-radio[value="${id}"]`);
                    if(input) input.checked = true;
                });
                checkCombination();
            }
        } else {
            updateButtonState(productBase.stock, productBase.preorder === 1, productBase.date);
        }

        document.querySelectorAll('.js-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                let val = parseInt(quantityInput.value);
                const isInc = (btn.dataset.action === 'inc');
                if (isInc) quantityInput.value = val + 1;
                else if (val > 1) quantityInput.value = val - 1;

                if (document.querySelector('.js-attr-radio')) checkCombination();
                else updateButtonState(productBase.stock, productBase.preorder === 1, productBase.date);
            });
        });
    });
</script>