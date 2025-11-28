<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(['error' => 'invalid_id'], 400);
}

$stmt = $pdo->prepare(
    'SELECT
         id,
         name,
         slug,
         short_description,
         long_description,
         price,
         stock_quantity,
         allow_preorder_when_oos,
         availability_date, 
         is_active,
         display_order,
         created_at,
         updated_at
     FROM nanook_products
     WHERE id = :id'
);
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    jsonResponse(['error' => 'not_found'], 404);
}

$product['price'] = (float)$product['price']; 
$product['stock_quantity'] = (int)$product['stock_quantity'];
$product['allow_preorder_when_oos'] = (int)$product['allow_preorder_when_oos'];
$product['is_active'] = (int)$product['is_active'];
$product['display_order'] = (int)$product['display_order'];


$catStmt = $pdo->prepare(
    'SELECT category_id
     FROM nanook_product_category
     WHERE product_id = :pid'
);
$catStmt->execute([':pid' => $id]);
$rows = $catStmt->fetchAll();

$categoryIds = [];
foreach ($rows as $row) {
    $categoryIds[] = (int)$row['category_id'];
}

$product['category_ids'] = $categoryIds;

jsonResponse([
    'success' => true,
    'data' => $product,
]);