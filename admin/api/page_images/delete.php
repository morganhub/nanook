<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';
$pdo = getPdo(); requireAdmin($pdo);
$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

$stmt = $pdo->prepare("SELECT file_path FROM nanook_page_images WHERE id = :id");
$stmt->execute([':id' => $id]);
$img = $stmt->fetch();

if ($img) {
    $path = dirname(__DIR__, 3) . '/storage/page_images/' . $img['file_path'];
    if (file_exists($path)) @unlink($path);
    $pdo->prepare("DELETE FROM nanook_page_images WHERE id = :id")->execute([':id' => $id]);
}
jsonResponse(['success' => true]);