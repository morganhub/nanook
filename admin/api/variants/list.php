<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($productId <= 0) {
    jsonResponse(['error' => 'invalid_product_id'], 400);
}

$stmt = $pdo->prepare(
    'SELECT
         id,
         name,
         sku,
         material,
         color,
         price,
         stock_quantity,
         allow_preorder_when_oos,
         availability_date,
         is_active,
         display_order,
         created_at,
         updated_at
     FROM nanook_product_variants
     WHERE product_id = :pid
     ORDER BY display_order ASC, id ASC'
);
$stmt->execute([':pid' => $productId]);
$rows = $stmt->fetchAll();

foreach ($rows as &$row) {
    $row['id'] = (int)$row['id'];
    $row['price'] = $row['price'] !== null ? (float)$row['price'] : null;
    $row['stock_quantity'] = (int)$row['stock_quantity'];
    $row['allow_preorder_when_oos'] = (int)$row['allow_preorder_when_oos'];
    $row['is_active'] = (int)$row['is_active'];
    $row['display_order'] = (int)$row['display_order'];
}
unset($row);

jsonResponse(['success' => true, 'data' => $rows]);