<?php
// public/admin/api/product_images/list.php
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
    'SELECT id, file_path, is_main, display_order, created_at
     FROM nanook_product_images
     WHERE product_id = :pid
     ORDER BY display_order ASC, id ASC'
);
$stmt->execute([':pid' => $productId]);
$images = $stmt->fetchAll();

foreach ($images as &$img) {
    $img['id'] = (int)$img['id'];
    $img['is_main'] = (int)$img['is_main'];
    $img['display_order'] = (int)$img['display_order'];
}
unset($img);

jsonResponse(['success' => true, 'data' => $images]);
