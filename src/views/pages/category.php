<?php

require_once __DIR__ . '/../../services/ProductService.php';

$slug = $_GET['slug'] ?? '';
$pdo = getPdo();


$stmt = $pdo->prepare("SELECT * FROM nanook_categories WHERE slug = :slug");
$stmt->execute([':slug' => $slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    echo "<div class='nk-container' style='padding:100px; text-align:center;'>Catégorie introuvable.</div>";
    return;
}


$products = getProductsByCategory($pdo, $slug);
?>

<div class="nk-container" style="padding-top: 60px; padding-bottom: 80px;">

    <div style="text-align:center; max-width:800px; margin: 0 auto 60px;">
        <h1 class="nk-title-lg"><?= htmlspecialchars($category['name']) ?></h1>
        <div class="nk-text-body" style="margin-top:10px;">
            <?= count($products) ?> pièce<?= count($products) > 1 ? 's' : '' ?> trouvée<?= count($products) > 1 ? 's' : '' ?>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div style="text-align:center; color:#888; padding: 40px;">
            Aucun produit dans cette catégorie pour le moment.
        </div>
    <?php else: ?>
        <div class="nk-grid">
            <?php foreach ($products as $p): ?>
                <?php
                $imgSrc = $p['image_path'] ? '/storage/product_images/' . $p['image_path'] : '/assets/img/placeholder.jpg';
                
                $price = number_format((float)$p['price'], 2, ',', ' ') . ' €';
                ?>

                <a href="/p/<?= htmlspecialchars($p['slug']) ?>" class="nk-product-card nk-span-3">
                    <div class="nk-product-img-wrapper">
                        <img src="<?= htmlspecialchars($imgSrc) ?>" class="nk-product-img" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">

                        <button class="nk-quick-add-btn" data-id="<?= $p['id'] ?>" data-has-variants="0">
                            Voir le détail
                        </button>
                    </div>
                    <div class="nk-product-info">
                        <div class="nk-product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="nk-product-price"><?= $price ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>