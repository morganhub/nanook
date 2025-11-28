<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);


$stmt = $pdo->query(
    'SELECT id, name, slug, parent_id, display_order, is_active
     FROM nanook_categories
     ORDER BY display_order ASC, name ASC'
);

$categories = $stmt->fetchAll();


foreach ($categories as &$cat) {
    $cat['id'] = (int)$cat['id'];
    $cat['parent_id'] = $cat['parent_id'] !== null ? (int)$cat['parent_id'] : null;
    $cat['display_order'] = (int)$cat['display_order'];
    $cat['is_active'] = (int)$cat['is_active'];
}
unset($cat); 

jsonResponse([
    'success' => true,
    'data' => $categories,
]);