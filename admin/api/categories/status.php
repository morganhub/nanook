<?php
// admin/api/categories/status.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? (int)$input['id'] : 0;
// On s'assure d'avoir un booléen (0 ou 1)
$isActive = isset($input['is_active']) ? (int)$input['is_active'] : 0;

if ($id <= 0) {
    jsonResponse(['error' => 'invalid_id'], 400);
}

try {
    $stmt = $pdo->prepare("UPDATE nanook_categories SET is_active = :active, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':active' => $isActive, ':id' => $id]);

    logAdminActivity($pdo, $admin['id'], 'category_status_update', 'category', $id, [
        'is_active' => $isActive
    ]);

    jsonResponse(['success' => true]);

} catch (Exception $e) {
    jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
}