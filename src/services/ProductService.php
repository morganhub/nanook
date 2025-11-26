<?php
// src/services/ProductService.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function getProductsByCategory(PDO $pdo, string $slug): array
{
    // Pas de changement majeur ici, on récupère * (donc availability_date inclus)
    $sql = "
        SELECT 
            p.*, 
            pi.file_path as image_path,
            GROUP_CONCAT(c.name SEPARATOR ', ') as category_names
        FROM nanook_products p
        INNER JOIN nanook_product_category pc ON p.id = pc.product_id
        INNER JOIN nanook_categories cat ON pc.category_id = cat.id
        LEFT JOIN nanook_product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        LEFT JOIN nanook_categories c ON pc.category_id = c.id
        WHERE p.is_active = 1 
          AND cat.slug = :slug
        GROUP BY p.id
        ORDER BY p.display_order ASC, p.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':slug' => $slug]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getHomeProducts(PDO $pdo, int $limit = 8): array
{
    // Pas de changement majeur ici
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
    // 1. Infos produit (récupère availability_date automatiquement via *)
    $stmt = $pdo->prepare("SELECT * FROM nanook_products WHERE slug = :slug AND is_active = 1");
    $stmt->execute([':slug' => $slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) return null;

    $pid = (int)$product['id'];

    // 2. Images
    $stmtImg = $pdo->prepare("SELECT * FROM nanook_product_images WHERE product_id = :pid ORDER BY is_main DESC, display_order ASC");
    $stmtImg->execute([':pid' => $pid]);
    $product['images'] = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

    // 3. Variantes (récupère availability_date automatiquement via *)
    $stmtVar = $pdo->prepare("SELECT * FROM nanook_product_variants WHERE product_id = :pid AND is_active = 1 ORDER BY display_order ASC");
    $stmtVar->execute([':pid' => $pid]);
    $product['variants'] = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

    // 4. Customizations
    $stmtCust = $pdo->prepare("SELECT * FROM nanook_product_customizations WHERE product_id = :pid ORDER BY display_order ASC");
    $stmtCust->execute([':pid' => $pid]);
    $product['customizations'] = $stmtCust->fetchAll(PDO::FETCH_ASSOC);

    foreach ($product['customizations'] as &$cust) {
        if ($cust['field_type'] === 'select') {
            $stmtOpt = $pdo->prepare("SELECT * FROM nanook_product_customization_options WHERE customization_id = :cid ORDER BY display_order ASC");
            $stmtOpt->execute([':cid' => $cust['id']]);
            $cust['options'] = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    return $product;
}