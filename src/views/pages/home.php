<?php
// src/views/pages/home.php

// On s'assure que le service est chargé (si index.php ne l'a pas fait, ceinture et bretelles)
require_once __DIR__ . '/../../services/ProductService.php';

$pdo = getPdo();
$products = getHomeProducts($pdo);
?>

<section style="height: 80vh; background: url('/assets/img/hero-nanook.jpg') center/cover no-repeat; display:flex; align-items:center; justify-content:center; margin-bottom:60px;">
    <div style="text-align:center; color:#FFF;">
        <h1 class="hero-title" style="margin-bottom:20px;"> </h1>
        <a href="#shop" class="nk-btn-primary btn-glass"  >Découvrir</a>
    </div>
</section>

<div class="nk-container" id="shop">
    <div style="text-align:center; max-width:600px; margin: 0 auto 60px;">
        <h2 class="nk-title-lg">La Collection</h2>
        <p class="nk-text-body" style="margin-top:10px;">Pièces uniques façonnées à Paris.</p>
    </div>

    <div class="nk-grid">
        <?php foreach ($products as $index => $p): ?>
            <?php
            // Alternance de taille (Rhythme)
            $gridClass = ($index % 4 === 0 && $index !== 0) ? 'nk-span-6' : 'nk-span-3';
            $imgSrc = $p['image_path'] ? '/storage/product_images/' . $p['image_path'] : '/assets/img/placeholder.jpg';

            // CORRECTION ICI : On utilise 'price' directement sans diviser par 100
            $price = number_format((float)$p['price'], 2, ',', ' ') . ' €';
            ?>

            <a href="/p/<?= htmlspecialchars($p['slug']) ?>" class="nk-product-card <?= $gridClass ?>">
                <div class="nk-product-img-wrapper">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" class="nk-product-img" alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div class="nk-product-info">
                    <div class="nk-product-name"><?= htmlspecialchars($p['name']) ?></div>

                    <?php if (!empty($p['category_names'])): ?>
                        <div style="font-size:0.8rem; color:#999; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">
                            <?= htmlspecialchars($p['category_names']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="nk-product-price"><?= $price ?></div>
                </div>
            </a>

            <?php if ($index === 1): ?>
                <div class="nk-mood-block nk-span-3">
                    <p class="nk-mood-text">"La matière commande, la main obéit."</p>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>
</div>