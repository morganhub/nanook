<?php
// public/admin/api/products/list.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
if ($perPage < 1) $perPage = 20;
if ($perPage > 100) $perPage = 100;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE :q OR p.slug LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

if ($categoryId > 0) {
    $where[] = 'EXISTS (
        SELECT 1
        FROM nanook_product_category pc2
        WHERE pc2.product_id = p.id
          AND pc2.category_id = :category_id
    )';
    $params[':category_id'] = $categoryId;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Count total items (Products only)
$countSql = 'SELECT COUNT(DISTINCT p.id) AS total FROM nanook_products p ' . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRow = $countStmt->fetch();
$totalItems = $totalRow ? (int)$totalRow['total'] : 0;

$totalPages = $totalItems > 0 ? (int)ceil($totalItems / $perPage) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Get Products
$listSql = '
    SELECT
        p.id,
        p.name,
        p.slug,
        p.price,
        p.stock_quantity,
        p.allow_preorder_when_oos,
        p.availability_date,
        p.is_active,
        p.display_order,
        p.created_at
    FROM nanook_products p
    ' . $whereSql . '
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
';

$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$products = $listStmt->fetchAll();

// --- Récupération des Variantes & Catégories ---
$productIds = array_column($products, 'id');
$categoriesByProduct = [];
$variantsByProduct = [];

if (!empty($productIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($productIds), '?'));

    // 1. Categories
    $catStmt = $pdo->prepare(
        'SELECT pc.product_id, c.id, c.name
         FROM nanook_product_category pc
         INNER JOIN nanook_categories c ON c.id = pc.category_id
         WHERE pc.product_id IN (' . $inPlaceholders . ')
         ORDER BY c.display_order ASC, c.name ASC'
    );
    $catStmt->execute($productIds);
    while ($row = $catStmt->fetch()) {
        $pid = (int)$row['product_id'];
        if (!isset($categoriesByProduct[$pid])) $categoriesByProduct[$pid] = [];
        $categoriesByProduct[$pid][] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }

    // 2. Variants
    $varStmt = $pdo->prepare(
        'SELECT 
            v.id, v.product_id, v.name, v.sku, v.price, v.stock_quantity, 
            v.allow_preorder_when_oos, v.availability_date, v.is_active
         FROM nanook_product_variants v
         WHERE v.product_id IN (' . $inPlaceholders . ')
         ORDER BY v.display_order ASC, v.id ASC'
    );
    $varStmt->execute($productIds);
    while ($row = $varStmt->fetch()) {
        $pid = (int)$row['product_id'];
        if (!isset($variantsByProduct[$pid])) $variantsByProduct[$pid] = [];

        // Formatage
        $row['id'] = (int)$row['id'];
        $row['price'] = ($row['price'] !== null) ? (float)$row['price'] : null;
        $row['stock_quantity'] = (int)$row['stock_quantity'];
        $row['allow_preorder_when_oos'] = (int)$row['allow_preorder_when_oos'];
        $row['is_active'] = (int)$row['is_active'];

        $variantsByProduct[$pid][] = $row;
    }
}

// Assemblage final
foreach ($products as &$product) {
    $pid = (int)$product['id'];
    $product['price'] = (float)$product['price'];
    $product['stock_quantity'] = (int)$product['stock_quantity'];
    $product['allow_preorder_when_oos'] = (int)$product['allow_preorder_when_oos'];
    $product['is_active'] = (int)$product['is_active'];
    $product['display_order'] = (int)$product['display_order'];

    $product['categories'] = $categoriesByProduct[$pid] ?? [];
    $product['variants'] = $variantsByProduct[$pid] ?? [];
}
unset($product);

jsonResponse([
    'success' => true,
    'data' => [
        'items' => $products,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
        ],
    ],
]);