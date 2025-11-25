<?php
// public/admin/api/product_images/set_main.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
if ($id <= 0) {
    jsonResponse(['error' => 'invalid_id'], 400);
}

$stmt = $pdo->prepare(
    'SELECT product_id
     FROM nanook_product_images
     WHERE id = :id'
);
$stmt->execute([':id' => $id]);
$image = $stmt->fetch();

if (!$image) {
    jsonResponse(['error' => 'not_found'], 404);
}

$productId = (int)$image['product_id'];

$reset = $pdo->prepare(
    'UPDATE nanook_product_images
     SET is_main = 0
     WHERE product_id = :pid'
);
$reset->execute([':pid' => $productId]);

$set = $pdo->prepare(
    'UPDATE nanook_product_images
     SET is_main = 1
     WHERE id = :id'
);
$set->execute([':id' => $id]);

logAdminActivity($pdo, $admin['id'], 'product_image_set_main', 'product', $productId, [
    'image_id' => $id,
]);

jsonResponse(['success' => true]);
