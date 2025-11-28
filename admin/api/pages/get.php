<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'method'], 405);
$pdo = getPdo();
requireAdmin($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) jsonResponse(['error' => 'no_id'], 400);

$stmt = $pdo->prepare("SELECT * FROM nanook_pages WHERE id = :id");
$stmt->execute([':id' => $id]);
$page = $stmt->fetch();

if (!$page) jsonResponse(['error' => 'not_found'], 404);

$page['is_active'] = (int)$page['is_active'];

jsonResponse(['success' => true, 'data' => $page]);