<?php
declare(strict_types=1);
require __DIR__ . '/../../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'method'], 405);
$pdo = getPdo();
requireAdmin($pdo);

$stmt = $pdo->query("SELECT id, title, slug, is_active, created_at FROM nanook_pages ORDER BY created_at DESC");
$pages = $stmt->fetchAll();

// Casting
foreach ($pages as &$p) {
    $p['id'] = (int)$p['id'];
    $p['is_active'] = (int)$p['is_active'];
}

jsonResponse(['success' => true, 'data' => $pages]);