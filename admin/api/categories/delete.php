<?php
// public/admin/api/categories/delete.php
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

try {
    $stmt = $pdo->prepare('DELETE FROM nanook_categories WHERE id = :id');
    $stmt->execute([':id' => $id]);

    logAdminActivity($pdo, $admin['id'], 'category_delete', 'category', $id);

    jsonResponse(['success' => true]);
} catch (Throwable $e) {
    jsonResponse([
        'error' => 'delete_failed',
        'message' => APP_ENV === 'dev' ? $e->getMessage() : null,
    ], 500);
}
