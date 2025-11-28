<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'method'], 405);
$pdo = getPdo(); $admin = requireAdmin($pdo);

$pageId = (int)($_POST['page_id'] ?? 0);
if (!$pageId || empty($_FILES['image'])) jsonResponse(['error' => 'invalid'], 400);

$file = $_FILES['image'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$hash = bin2hex(random_bytes(16));

$dir1 = substr($hash, 0, 2); $dir2 = substr($hash, 2, 2);
$baseDir = dirname(__DIR__, 3) . '/storage/page_images';
$targetDir = "$baseDir/$dir1/$dir2";

if (!is_dir($targetDir)) mkdir($targetDir, 0775, true);

$filename = "$hash.$ext";
if (move_uploaded_file($file['tmp_name'], "$targetDir/$filename")) {
    $relativePath = "$dir1/$dir2/$filename";
    $pdo->prepare("INSERT INTO nanook_page_images (page_id, file_path, created_at) VALUES (?, ?, NOW())")->execute([$pageId, $relativePath]);
    jsonResponse(['success' => true]);
}
jsonResponse(['error' => 'upload_failed'], 500);