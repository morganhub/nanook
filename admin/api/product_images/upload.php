<?php
// public/admin/api/product_images/upload.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
// NOUVEAU : Récupération de l'ID variante (optionnel)
$variantId = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;

if ($productId <= 0) {
    jsonResponse(['error' => 'invalid_product_id'], 400);
}

if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    jsonResponse(['error' => 'no_file'], 400);
}

$file = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($file['size'] > $maxSize) {
    jsonResponse(['error' => 'file_too_large'], 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (!isset($allowed[$mime])) {
    jsonResponse(['error' => 'invalid_type'], 400);
}

$ext = $allowed[$mime];
$hash = bin2hex(random_bytes(16));
$dir1 = substr($hash, 0, 2);
$dir2 = substr($hash, 2, 2);

$baseDir = dirname(__DIR__, 3) . '/storage/product_images';
$targetDir = $baseDir . '/' . $dir1 . '/' . $dir2;

if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    jsonResponse(['error' => 'mkdir_failed'], 500);
}

$filename = $hash . '.' . $ext;
$targetPath = $targetDir . '/' . $filename;
$relativePath = $dir1 . '/' . $dir2 . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    jsonResponse(['error' => 'upload_failed'], 500);
}

// On vérifie s'il y a déjà des images pour définir "main" par défaut
// Si c'est une variante, on ne se préoccupe pas trop du "main" global, mais on le gère quand même
$stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM nanook_product_images WHERE product_id = :pid');
$stmt->execute([':pid' => $productId]);
$row = $stmt->fetch();
$hasImages = $row && (int)$row['cnt'] > 0;

$insert = $pdo->prepare(
    'INSERT INTO nanook_product_images
    (product_id, variant_id, file_path, is_main, display_order, created_at)
    VALUES
    (:product_id, :variant_id, :file_path, :is_main, :display_order, NOW())'
);
$insert->execute([
    ':product_id' => $productId,
    ':variant_id' => $variantId, // Peut être NULL
    ':file_path' => $relativePath,
    ':is_main' => (!$hasImages && $variantId === null) ? 1 : 0, // Main seulement si c'est la toute première image du produit parent
    ':display_order' => 0,
]);

$imageId = (int)$pdo->lastInsertId();

logAdminActivity($pdo, $admin['id'], 'product_image_upload', 'product', $productId, [
    'image_id' => $imageId,
    'variant_id' => $variantId,
    'file_path' => $relativePath,
]);

jsonResponse([
    'success' => true,
    'data' => [
        'id' => $imageId,
        'file_path' => $relativePath,
        'variant_id' => $variantId
    ],
]);