<?php
// public/admin/api/categories/list.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$stmt = $pdo->query(
    'SELECT id, name, slug
     FROM nanook_categories
     ORDER BY display_order ASC, name ASC'
);

$categories = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'data' => $categories,
]);
