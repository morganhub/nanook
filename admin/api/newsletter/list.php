<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';
$pdo = getPdo(); requireAdmin($pdo);

$stmt = $pdo->query("SELECT * FROM nanook_newsletter_subscribers ORDER BY created_at DESC");
$subs = $stmt->fetchAll();


foreach ($subs as &$s) {
    $s['id'] = (int)$s['id'];
    $s['is_active'] = (int)$s['is_active'];
}
jsonResponse(['success' => true, 'data' => $subs]);