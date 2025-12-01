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
$variantId = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;

if ($productId <= 0) {
    jsonResponse(['error' => 'invalid_product_id'], 400);
}

if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    jsonResponse(['error' => 'no_file'], 400);
}

$file = $_FILES['image'];
// On garde une limite haute de poids pour l'upload initial (ex: 10MB pour ne pas saturer la RAM lors du resize)
$maxSize = 10 * 1024 * 1024;

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

// --- DÉBUT LOGIQUE REDIMENSIONNEMENT ---

list($width, $height) = getimagesize($file['tmp_name']);
$maxWidth = 800;

// Si l'image est plus large que 800px, on redimensionne
if ($width > $maxWidth) {
    // Calcul de la nouvelle hauteur en gardant le ratio
    $ratio = $height / $width;
    $newWidth = $maxWidth;
    $newHeight = (int)($newWidth * $ratio);

    // Création de l'image source selon le type
    $src = null;
    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png': $src = imagecreatefrompng($file['tmp_name']); break;
        case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
    }

    if ($src) {
        // Création de l'image vide destination
        $dst = imagecreatetruecolor($newWidth, $newHeight);

        // Gestion de la transparence (PNG/WebP)
        if ($mime == 'image/png' || $mime == 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Redimensionnement
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Sauvegarde dans le dossier cible
        $saved = false;
        switch ($mime) {
            case 'image/jpeg': $saved = imagejpeg($dst, $targetPath, 85); break; // Qualité 85
            case 'image/png': $saved = imagepng($dst, $targetPath, 9); break; // Compression max (0-9)
            case 'image/webp': $saved = imagewebp($dst, $targetPath, 85); break; // Qualité 85
        }

        // Nettoyage mémoire
        imagedestroy($src);
        imagedestroy($dst);

        if (!$saved) {
            jsonResponse(['error' => 'resize_failed'], 500);
        }
    } else {
        // Fallback si GD échoue à ouvrir l'image : on déplace tel quel
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            jsonResponse(['error' => 'upload_failed'], 500);
        }
    }
} else {
    // Pas de redimensionnement nécessaire, on déplace simplement
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        jsonResponse(['error' => 'upload_failed'], 500);
    }
}

// --- FIN LOGIQUE REDIMENSIONNEMENT ---

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
    ':variant_id' => $variantId,
    ':file_path' => $relativePath,
    ':is_main' => (!$hasImages && $variantId === null) ? 1 : 0,
    ':display_order' => 0,
]);

$imageId = (int)$pdo->lastInsertId();

logAdminActivity($pdo, $admin['id'], 'product_image_upload', 'product', $productId, [
    'image_id' => $imageId,
    'variant_id' => $variantId,
    'file_path' => $relativePath,
    'resized' => ($width > $maxWidth)
]);

jsonResponse([
    'success' => true,
    'data' => [
        'id' => $imageId,
        'file_path' => $relativePath,
        'variant_id' => $variantId
    ],
]);