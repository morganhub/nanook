<?php
// public/admin/api/product_images/delete.php
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
    'SELECT product_id, file_path, is_main
     FROM nanook_product_images
     WHERE id = :id'
);
$stmt->execute([':id' => $id]);
$image = $stmt->fetch();

if (!$image) {
    jsonResponse(['error' => 'not_found'], 404);
}

$productId = (int)$image['product_id'];

$baseDir = dirname(__DIR__, 3) . '/storage/product_images';
$filePath = $baseDir . '/' . $image['file_path'];

$del = $pdo->prepare('DELETE FROM nanook_product_images WHERE id = :id');
$del->execute([':id' => $id]);

if (is_file($filePath)) {
    @unlink($filePath);
}

if ((int)$image['is_main'] === 1) {
    $stmt2 = $pdo->prepare(
        'SELECT id
         FROM nanook_product_images
         WHERE product_id = :pid
         ORDER BY display_order ASC, id ASC
         LIMIT 1'
    );
    $stmt2->execute([':pid' => $productId]);
    $newMain = $stmt2->fetch();
    if ($newMain) {
        $upd = $pdo->prepare(
            'UPDATE nanook_product_images
             SET is_main = 1
             WHERE id = :id'
        );
        $upd->execute([':id' => $newMain['id']]);
    }
}

logAdminActivity($pdo, $admin['id'], 'product_image_delete', 'product', $productId, [
    'image_id' => $id,
]);

jsonResponse(['success' => true]);
