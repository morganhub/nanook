<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'method'], 405);
$pdo = getPdo();
$admin = requireAdmin($pdo);

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? (int)$input['id'] : 0;
$title = trim($input['title'] ?? '');
$slug = trim($input['slug'] ?? '');
$chapeau = trim($input['chapeau'] ?? '');
$content = $input['content'] ?? ''; 
$isActive = !empty($input['is_active']) ? 1 : 0;

if (!$title || !$slug) jsonResponse(['error' => 'missing_fields'], 400);

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE nanook_pages SET title=:t, slug=:s, chapeau=:c, content=:cnt, is_active=:a, updated_at=NOW() WHERE id=:id");
        $stmt->execute([':t'=>$title, ':s'=>$slug, ':c'=>$chapeau, ':cnt'=>$content, ':a'=>$isActive, ':id'=>$id]);
        logAdminActivity($pdo, $admin['id'], 'page_update', 'page', $id, ['title' => $title]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO nanook_pages (title, slug, chapeau, content, is_active, created_at, updated_at) VALUES (:t, :s, :c, :cnt, :a, NOW(), NOW())");
        $stmt->execute([':t'=>$title, ':s'=>$slug, ':c'=>$chapeau, ':cnt'=>$content, ':a'=>$isActive]);
        $id = (int)$pdo->lastInsertId();
        logAdminActivity($pdo, $admin['id'], 'page_create', 'page', $id, ['title' => $title]);
    }
    jsonResponse(['success' => true, 'data' => ['id' => $id]]);
} catch (Exception $e) {
    jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
}