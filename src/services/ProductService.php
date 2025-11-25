<?php
require_once __DIR__ . '/../../services/ProductService.php';

$slug = $_GET['slug'] ?? '';
$pdo = getPdo();
$product = getProductBySlug($pdo, $slug);

function getHomeProducts(PDO $pdo, int $limit = 8): array
{
    // On récupère le produit + son image principale + ses catégories concaténées
    $sql = "
        SELECT 
            p.*, 
            pi.file_path as image_path,
            GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
        FROM nanook_products p
        LEFT JOIN nanook_product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        LEFT JOIN nanook_product_category pc ON p.id = pc.product_id
        LEFT JOIN nanook_categories c ON pc.category_id = c.id
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY p.display_order ASC, p.created_at DESC
        LIMIT :limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductBySlug(PDO $pdo, string $slug): ?array
{
    // 1. Infos produit
    $stmt = $pdo->prepare("SELECT * FROM nanook_products WHERE slug = :slug AND is_active = 1");
    $stmt->execute([':slug' => $slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) return null;

    $pid = (int)$product['id'];

    // 2. Images
    $stmtImg = $pdo->prepare("SELECT * FROM nanook_product_images WHERE product_id = :pid ORDER BY is_main DESC, display_order ASC");
    $stmtImg->execute([':pid' => $pid]);
    $product['images'] = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

    // 3. Variantes (Déclinaisons)
    $stmtVar = $pdo->prepare("SELECT * FROM nanook_product_variants WHERE product_id = :pid AND is_active = 1 ORDER BY display_order ASC");
    $stmtVar->execute([':pid' => $pid]);
    $product['variants'] = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

    // 4. Customizations (Personnalisation)
    $stmtCust = $pdo->prepare("SELECT * FROM nanook_product_customizations WHERE product_id = :pid ORDER BY display_order ASC");
    $stmtCust->execute([':pid' => $pid]);
    $product['customizations'] = $stmtCust->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque custom, si c'est un SELECT, on veut les options
    foreach ($product['customizations'] as &$cust) {
        if ($cust['field_type'] === 'select') {
            $stmtOpt = $pdo->prepare("SELECT * FROM nanook_product_customization_options WHERE customization_id = :cid ORDER BY display_order ASC");
            $stmtOpt->execute([':cid' => $cust['id']]);
            $cust['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return $product;
}

if (!$product) {
    http_response_code(404);
    echo "<div class='nk-container' style='padding:100px;'>Produit introuvable.</div>";
    return;
}

// CORRECTION PRIX : On utilise directement 'price' sans diviser par 100
$price = number_format((float)$product['price'], 2, ',', ' ') . ' €';
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
                    <div class="nk-product-price"><?= $price ?></div>
                </div>

                <div class="nk-desktop-header">
                    <h1 class="nk-product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="nk-product-price"><?= $price ?></div>
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
                            Disponible en précommande (délai env. 2 semaines).
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

    <div class="nk-container nk-cross-sell">
        <h3 class="nk-story-title" style="text-align:left; margin-bottom:30px;">Le compagnon idéal</h3>
        <div class="nk-grid">
            <div class="nk-product-card nk-span-3">
                <div class="nk-product-img-wrapper" style="background:#f9f9f9;"></div>
                <div class="nk-product-info">
                    <div class="nk-product-name">Porte-clés</div>
                    <div class="nk-product-price">25.00 €</div>
                </div>
            </div>
        </div>
    </div>
</div>