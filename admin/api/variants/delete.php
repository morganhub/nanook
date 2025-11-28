<?php

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

$stmt = $pdo->prepare('SELECT product_id FROM nanook_product_variants WHERE id = :id');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    jsonResponse(['error' => 'not_found'], 404);
}

$productId = (int)$row['product_id'];

$del = $pdo->prepare('DELETE FROM nanook_product_variants WHERE id = :id');
$del->execute([':id' => $id]);

logAdminActivity($pdo, $admin['id'], 'variant_delete', 'product', $productId, [
    'variant_id' => $id,
]);

jsonResponse(['success' => true]);
