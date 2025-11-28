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
    jsonResponse(['error' => 'invalid_payload'], 400);
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
$name = isset($input['name']) ? trim((string)$input['name']) : '';
$slug = isset($input['slug']) ? trim((string)$input['slug']) : '';
$parentId = isset($input['parent_id']) ? (int)$input['parent_id'] : 0;
$displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;

if ($name === '' || $slug === '') {
    jsonResponse(['error' => 'name_and_slug_required'], 400);
}

if ($parentId <= 0) {
    $parentId = null;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE nanook_categories
             SET name = :name,
                 slug = :slug,
                 parent_id = :parent_id,
                 display_order = :display_order,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':parent_id' => $parentId,
            ':display_order' => $displayOrder,
            ':id' => $id,
        ]);

        logAdminActivity($pdo, $admin['id'], 'category_update', 'category', $id, [
            'name' => $name,
            'slug' => $slug,
        ]);

        jsonResponse(['success' => true, 'data' => ['id' => $id]]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO nanook_categories
            (name, slug, parent_id, display_order, created_at, updated_at)
            VALUES
            (:name, :slug, :parent_id, :display_order, NOW(), NOW())'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':parent_id' => $parentId,
            ':display_order' => $displayOrder,
        ]);

        $newId = (int)$pdo->lastInsertId();

        logAdminActivity($pdo, $admin['id'], 'category_create', 'category', $newId, [
            'name' => $name,
            'slug' => $slug,
        ]);

        jsonResponse(['success' => true, 'data' => ['id' => $newId]]);
    }
} catch (Throwable $e) {
    jsonResponse([
        'error' => 'save_failed',
        'message' => APP_ENV === 'dev' ? $e->getMessage() : null,
    ], 500);
}
