<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'method'], 405);
$pdo = getPdo();
requireAdmin($pdo);

$stmt = $pdo->query("SELECT id, username, email, is_super_admin, is_active, report_frequency, report_hour, updated_at FROM nanook_admin_users ORDER BY username ASC");
$users = $stmt->fetchAll();


foreach ($users as &$u) {
    $u['id'] = (int)$u['id'];
    $u['is_active'] = (int)$u['is_active'];
    $u['is_super_admin'] = (int)$u['is_super_admin'];
    $u['report_hour'] = (int)$u['report_hour'];
}

jsonResponse(['success' => true, 'data' => $users]);