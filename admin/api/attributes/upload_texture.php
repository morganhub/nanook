<?php
// admin/api/attributes/upload_texture.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

if (empty($_FILES['texture']) || !is_uploaded_file($_FILES['texture']['tmp_name'])) {
    jsonResponse(['error' => 'no_file'], 400);
}

$optionId = isset($_POST['option_id']) ? (int)$_POST['option_id'] : 0;
if ($optionId <= 0) {
    jsonResponse(['error' => 'invalid_option_id'], 400);
}

// 1. Gestion du fichier
$file = $_FILES['texture'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($ext, $allowed)) {
    jsonResponse(['error' => 'invalid_extension'], 400);
}

// Dossier de stockage : /storage/attributes/
$baseDir = __DIR__ . '/../../../storage/attributes';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

// Nom unique
$filename = 'tex_' . $optionId . '_' . time() . '.' . $ext;
$targetPath = $baseDir . '/' . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // 2. Mise à jour en base
    // On stocke le chemin relatif dans 'value'
    $relativePath = 'attributes/' . $filename;

    $stmt = $pdo->prepare("UPDATE nanook_attribute_options SET value = :val WHERE id = :id");
    $stmt->execute([':val' => $relativePath, ':id' => $optionId]);

    jsonResponse([
        'success' => true,
        'data' => [
            'file_path' => $relativePath,
            'full_url' => '/storage/' . $relativePath
        ]
    ]);
} else {
    jsonResponse(['error' => 'move_failed'], 500);
}