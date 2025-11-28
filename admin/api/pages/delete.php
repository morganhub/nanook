<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'method'], 405);
$pdo = getPdo();
$admin = requireAdmin($pdo);
$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
if (!$id) jsonResponse(['error' => 'no_id'], 400);

$pdo->prepare("DELETE FROM nanook_pages WHERE id = :id")->execute([':id' => $id]);
logAdminActivity($pdo, $admin['id'], 'page_delete', 'page', $id);
jsonResponse(['success' => true]);