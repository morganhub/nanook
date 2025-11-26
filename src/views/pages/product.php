<?php
// src/views/pages/product.php

// Si la variable $product n'existe pas (accès direct interdit ou bug routeur), on arrête.
if (!isset($product) || !$product) {
    echo "<div class='nk-container' style='padding:100px; text-align:center;'>Produit introuvable.</div>";
    return;
}

// --- TRI DES IMAGES ---
// On sépare les images "communes" (parent) des images spécifiques aux variantes
$commonImages = [];
$variantImagesMap = []; // [variant_id => [img1, img2]]

foreach ($product['images'] as $img) {
    if ($img['variant_id'] === null) {
        $commonImages[] = $img;
    } else {
        $vid = $img['variant_id'];
        if (!isset($variantImagesMap[$vid])) $variantImagesMap[$vid] = [];
        $variantImagesMap[$vid][] = $img;
    }
}

// Fallback : Si aucune image commune, on met un placeholder
if (empty($commonImages)) {
    $commonImages[] = ['file_path' => null]; // null signalera placeholder
}

// --- LOGIQUE INITIALE PHP ---
$hasVariants = !empty($product['variants']);

if ($hasVariants) {
    $firstVar = $product['variants'][0];
    $currentPrice = (float)$firstVar['price'];
    $currentStock = (int)$firstVar['stock_quantity'];
    $currentPreorder = (int)$firstVar['allow_preorder_when_oos'];
    $currentDate = $firstVar['availability_date'];

    // Images initiales : Celles de la variante SI elle en a, sinon communes
    $currentImages = !empty($variantImagesMap[$firstVar['id']])
        ? $variantImagesMap[$firstVar['id']]
        : $commonImages;
} else {
    $currentPrice = (float)$product['price'];
    $currentStock = (int)$product['stock_quantity'];
    $currentPreorder = (int)$product['allow_preorder_when_oos'];
    $currentDate = $product['availability_date'];
    $currentImages = $commonImages;
}

$priceFormatted = number_format($currentPrice, 2, ',', ' ') . ' €';
?>

<div class="nk-product-page">
    <div class="nk-container nk-product-layout">

        <!-- GALERIE PHOTO -->
        <div class="nk-product-visuals">
            <div class="nk-gallery" id="productGallery">
                <!-- Injection PHP initiale -->
                <?php foreach ($currentImages as $img): ?>
                    <div class="nk-image-wrapper">
                        <?php if ($img['file_path']): ?>
                            <img src="/storage/product_images/<?= htmlspecialchars($img['file_path']) ?>"
                                 alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <img src="/assets/img/placeholder.jpg" alt="Placeholder">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="nk-product-story">
                <h2 class="nk-story-title">Les secrets d'un objet</h2>
                <div class="nk-story-text">
                    <?= nl2br(htmlspecialchars($product['long_description'] ?? '')) ?>
                </div>
            </div>
        </div>

        <div class="nk-product-sidebar-wrapper">
            <div class="nk-product-sidebar">

                <div class="nk-mobile-header">
                    <h1 class="nk-product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="nk-product-price js-product-price"><?= $priceFormatted ?></div>
                </div>

                <div class="nk-desktop-header">
                    <h1 class="nk-product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="nk-product-price js-product-price"><?= $priceFormatted ?></div>
                </div>

                <div class="nk-product-desc-short">
                    <?= nl2br(htmlspecialchars($product['short_description'] ?? '')) ?>
                </div>

                <form id="addToCartForm" class="nk-product-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <?php if ($hasVariants): ?>
                        <div class="nk-form-group">
                            <label class="nk-label">Déclinaison</label>
                            <div class="nk-variant-grid">
                                <?php foreach ($product['variants'] as $idx => $v): ?>
                                    <?php
                                    $vStock = (int)$v['stock_quantity'];
                                    $vPreorder = (int)$v['allow_preorder_when_oos'];
                                    $vDate = $v['availability_date'];
                                    $vPrice = (float)$v['price'];
                                    ?>
                                    <label class="nk-variant-option">
                                        <input type="radio"
                                               name="variant_id"
                                               value="<?= $v['id'] ?>"
                                               class="js-variant-radio"
                                               data-price="<?= $vPrice ?>"
                                               data-stock="<?= $vStock ?>"
                                               data-preorder="<?= $vPreorder ?>"
                                               data-date="<?= htmlspecialchars($vDate ?? '') ?>"
                                            <?= $idx === 0 ? 'checked' : '' ?>
                                        >
                                        <span class="nk-variant-box">
                                            <?= htmlspecialchars($v['name']) ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

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

                    <div class="nk-form-actions">
                        <div class="nk-qty-wrapper">
                            <button type="button" class="js-qty-btn" data-action="dec">-</button>
                            <input type="number" name="quantity" id="quantityInput" value="1" min="1" readonly>
                            <button type="button" class="js-qty-btn" data-action="inc">+</button>
                        </div>

                        <button type="submit" class="nk-btn-add" id="btnAddToCart">
                            Ajouter au panier
                        </button>
                    </div>

                    <div id="stockMessageArea" class="nk-stock-warning" style="display:none; margin-top:15px; font-size:0.9rem; line-height:1.4;"></div>

                    <!-- Données initiales pour JS (Cas sans variante) -->
                    <input type="hidden" id="initStock" value="<?= $currentStock ?>">
                    <input type="hidden" id="initPreorder" value="<?= $currentPreorder ?>">
                    <input type="hidden" id="initDate" value="<?= htmlspecialchars($currentDate ?? '') ?>">

                </form>

                <div class="nk-product-meta">
                    <ul>
                        <li>Livraison offerte dès 200€</li>
                        <li>Retours gratuits sous 30 jours</li>
                        <li>Fabriqué à la main à Paris</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Données injectées pour le JS
    const commonImages = <?= json_encode($commonImages) ?>;
    const variantImagesMap = <?= json_encode($variantImagesMap) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        // Elements DOM
        const variantRadios = document.querySelectorAll('.js-variant-radio');
        const priceEls = document.querySelectorAll('.js-product-price');
        const stockMessageArea = document.getElementById('stockMessageArea');
        const btnAddToCart = document.getElementById('btnAddToCart');
        const quantityInput = document.getElementById('quantityInput');
        const qtyBtns = document.querySelectorAll('.js-qty-btn');
        const galleryEl = document.getElementById('productGallery');

        // Init Data
        const initStock = document.getElementById('initStock');
        const initPreorder = document.getElementById('initPreorder');
        const initDate = document.getElementById('initDate');

        // State global pour le produit courant
        let currentState = {
            price: 0,
            stock: parseInt(initStock ? initStock.value : 0),
            preorder: parseInt(initPreorder ? initPreorder.value : 0),
            date: initDate ? initDate.value : ''
        };

        // --- Helpers ---
        const formatPrice = (price) => {
            return new Intl.NumberFormat('fr-FR', {
                style: 'decimal',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(price) + ' €';
        };

        const formatDate = (dateString) => {
            if (!dateString) return "date inconnue";
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
        };

        // --- Render Galerie ---
        function renderGallery(images) {
            galleryEl.innerHTML = images.map(img => {
                const src = img.file_path ? '/storage/product_images/' + img.file_path : '/assets/img/placeholder.jpg';
                return `<div class="nk-image-wrapper"><img src="${src}" loading="lazy"></div>`;
            }).join('');
        }

        // --- UI Update Logic ---
        function refreshUI() {
            const qty = parseInt(quantityInput.value);
            const stock = currentState.stock;
            const canPreorder = (currentState.preorder === 1);

            // 1. Mise à jour du prix
            if (currentState.price > 0) {
                priceEls.forEach(el => el.textContent = formatPrice(currentState.price));
            }

            // 2. Gestion de l'input quantité
            if (canPreorder) {
                quantityInput.removeAttribute('max');
            } else {
                quantityInput.setAttribute('max', stock);
                if (qty > stock && stock > 0) {
                    quantityInput.value = stock;
                }
            }

            // 3. Messages et Bouton
            stockMessageArea.style.display = 'none';
            btnAddToCart.disabled = false;
            btnAddToCart.textContent = 'Ajouter au panier';
            btnAddToCart.style.backgroundColor = '#1A1A2E';

            // Cas A : Rupture Totale
            if (stock <= 0 && !canPreorder) {
                stockMessageArea.style.display = 'block';
                stockMessageArea.textContent = 'Rupture de stock';
                stockMessageArea.style.color = '#b91c1c';
                btnAddToCart.disabled = true;
                btnAddToCart.textContent = 'Indisponible';
                btnAddToCart.style.backgroundColor = '#ccc';
                return;
            }

            // Cas B : Précommande Pure
            if (stock <= 0 && canPreorder) {
                stockMessageArea.style.display = 'block';
                const dateTxt = currentState.date ? formatDate(currentState.date) : '2 semaines';
                stockMessageArea.textContent = 'Précommande : Expédition prévue en ' + dateTxt;
                stockMessageArea.style.color = '#C18C5D';
                btnAddToCart.textContent = 'Précommander';
                btnAddToCart.style.backgroundColor = '#C18C5D';
                return;
            }

            // Cas C : Mixte
            if (stock > 0 && qty > stock && canPreorder) {
                const preOrderQty = qty - stock;
                const dateTxt = currentState.date ? formatDate(currentState.date) : '2 semaines';
                stockMessageArea.style.display = 'block';
                stockMessageArea.innerHTML = `
                <span style="color:#15803d">✓ ${stock} en stock immédiat</span><br>
                <span style="color:#C18C5D">⚠ ${preOrderQty} en précommande (Dispo ${dateTxt})</span>
            `;
                btnAddToCart.textContent = 'Commander (Stock + Précommande)';
                return;
            }
        }

        // --- Event Listeners ---

        // 1. Changement de variante
        variantRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.checked) {
                    const vid = e.target.value;

                    currentState.price = parseFloat(e.target.dataset.price);
                    currentState.stock = parseInt(e.target.dataset.stock);
                    currentState.preorder = parseInt(e.target.dataset.preorder);
                    currentState.date = e.target.dataset.date;

                    refreshUI();

                    // Update Images
                    if (variantImagesMap[vid] && variantImagesMap[vid].length > 0) {
                        renderGallery(variantImagesMap[vid]);
                    } else {
                        renderGallery(commonImages);
                    }
                }
            });
        });

        // 2. Boutons +/-
        qtyBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                let val = parseInt(quantityInput.value);
                const isInc = (btn.dataset.action === 'inc');
                const max = quantityInput.hasAttribute('max') ? parseInt(quantityInput.getAttribute('max')) : 999;

                if (isInc) {
                    if (val < max) quantityInput.value = val + 1;
                } else {
                    if (val > 1) quantityInput.value = val - 1;
                }
                refreshUI();
            });
        });

        // 3. Input manuel
        quantityInput.addEventListener('change', () => {
            let val = parseInt(quantityInput.value);
            const max = quantityInput.hasAttribute('max') ? parseInt(quantityInput.getAttribute('max')) : 999;
            if (val < 1) quantityInput.value = 1;
            if (val > max) quantityInput.value = max;
            refreshUI();
        });

        // --- Initialisation ---
        const checkedRadio = document.querySelector('.js-variant-radio:checked');
        if (checkedRadio) {
            currentState.price = parseFloat(checkedRadio.dataset.price);
            currentState.stock = parseInt(checkedRadio.dataset.stock);
            currentState.preorder = parseInt(checkedRadio.dataset.preorder);
            currentState.date = checkedRadio.dataset.date;
        }
        refreshUI();
    });
</script>