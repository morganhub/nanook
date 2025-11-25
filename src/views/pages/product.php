<?php
// src/views/pages/product.php

// Si la variable $product n'existe pas (accès direct interdit ou bug routeur), on arrête.
if (!isset($product) || !$product) {
    echo "<div class='nk-container' style='padding:100px; text-align:center;'>Produit introuvable.</div>";
    return;
}

// Affichage du prix (La variable 'price' est maintenant en decimal/float dans votre DB)
// Si le prix est 0, on regarde si une variante a un prix
$displayPrice = (float)$product['price'];
if ($displayPrice == 0 && !empty($product['variants'])) {
    // On prend le prix de la première variante
    $displayPrice = (float)$product['variants'][0]['price'];
}

$priceFormatted = number_format($displayPrice, 2, ',', ' ') . ' €';
?>

<div class="nk-product-page">
    <div class="nk-container nk-product-layout">

        <div class="nk-product-visuals">
            <div class="nk-gallery">
                <?php if(!empty($product['images'])): ?>
                    <?php foreach ($product['images'] as $img): ?>
                        <div class="nk-image-wrapper">
                            <img src="/storage/product_images/<?= htmlspecialchars($img['file_path']) ?>"
                                 alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="nk-image-wrapper">
                        <img src="/assets/img/placeholder.jpg" alt="Placeholder">
                    </div>
                <?php endif; ?>
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
                    <div class="nk-product-price"><?= $priceFormatted ?></div>
                </div>

                <div class="nk-desktop-header">
                    <h1 class="nk-product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="nk-product-price"><?= $priceFormatted ?></div>
                </div>

                <div class="nk-product-desc-short">
                    <?= nl2br(htmlspecialchars($product['short_description'] ?? '')) ?>
                </div>

                <form id="addToCartForm" class="nk-product-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <?php if (!empty($product['variants'])): ?>
                        <div class="nk-form-group">
                            <label class="nk-label">Déclinaison</label>
                            <div class="nk-variant-grid">
                                <?php foreach ($product['variants'] as $idx => $v): ?>
                                    <?php
                                    $disabled = ($v['stock_quantity'] <= 0 && !$v['allow_preorder_when_oos']) ? 'disabled' : '';
                                    ?>
                                    <label class="nk-variant-option <?= $disabled ?>">
                                        <input type="radio" name="variant_id" value="<?= $v['id'] ?>" <?= $idx===0 && !$disabled ? 'checked' : '' ?> <?= $disabled ?>>
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
                            <button type="button" onclick="this.nextElementSibling.stepDown()">-</button>
                            <input type="number" name="quantity" value="1" min="1" max="10" readonly>
                            <button type="button" onclick="this.previousElementSibling.stepUp()">+</button>
                        </div>
                        <button type="submit" class="nk-btn-add" id="btnAddToCart">
                            Ajouter au panier
                        </button>
                    </div>

                    <?php if ($product['stock_quantity'] <= 0 && $product['allow_preorder_when_oos']): ?>
                        <div class="nk-stock-warning">
                            Précommande : Délai env. 2 semaines.
                        </div>
                    <?php endif; ?>
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