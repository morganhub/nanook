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
// Filtre : 'null' (parent uniquement), int (variante spécifique), ou absent (tout)
$variantFilter = $_GET['variant_id'] ?? null;

if ($productId <= 0) {
    jsonResponse(['error' => 'invalid_product_id'], 400);
}

$sql = 'SELECT id, file_path, is_main, display_order, variant_id, created_at
        FROM nanook_product_images
        WHERE product_id = :pid';
$params = [':pid' => $productId];

if ($variantFilter === 'null') {
    $sql .= ' AND variant_id IS NULL';
} elseif (is_numeric($variantFilter)) {
    $sql .= ' AND variant_id = :vid';
    $params[':vid'] = (int)$variantFilter;
}

$sql .= ' ORDER BY display_order ASC, id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$images = $stmt->fetchAll();

foreach ($images as &$img) {
    $img['id'] = (int)$img['id'];
    $img['is_main'] = (int)$img['is_main'];
    $img['variant_id'] = $img['variant_id'] !== null ? (int)$img['variant_id'] : null;
}
unset($img);

jsonResponse(['success' => true, 'data' => $images]);