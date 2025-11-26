<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'method'], 405);
$pdo = getPdo();
requireAdmin($pdo);

$id = isset($_GET['id']) ? (int)($_GET['id']) : 0;
if (!$id) jsonResponse(['error' => 'no_id'], 400);

$stmt = $pdo->prepare("SELECT id, username, email, is_active, report_frequency, report_hour FROM nanook_admin_users WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) jsonResponse(['error' => 'not_found'], 404);

jsonResponse(['success' => true, 'data' => $user]);