<?php
// public/admin/api/categories/list.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

// Ajout de parent_id, display_order et is_active pour gérer l'affichage complet
$stmt = $pdo->query(
    'SELECT id, name, slug, parent_id, display_order, is_active
     FROM nanook_categories
     ORDER BY display_order ASC, name ASC'
);

$categories = $stmt->fetchAll();

// On s'assure que les types sont corrects pour le JS
foreach ($categories as &$cat) {
    $cat['id'] = (int)$cat['id'];
    $cat['parent_id'] = $cat['parent_id'] !== null ? (int)$cat['parent_id'] : null;
    $cat['display_order'] = (int)$cat['display_order'];
    $cat['is_active'] = (int)$cat['is_active'];
}
unset($cat); // Rupture de la référence

jsonResponse([
    'success' => true,
    'data' => $categories,
]);