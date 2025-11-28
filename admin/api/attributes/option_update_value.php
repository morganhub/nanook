<?php

declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => '405'], 405);
$pdo = getPdo();
requireAdmin($pdo);

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$val = $input['value'] ?? '';

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE nanook_attribute_options SET value = :v WHERE id = :id");
    $stmt->execute([':v' => $val, ':id' => $id]);
    jsonResponse(['success' => true]);
}
jsonResponse(['error' => 'invalid'], 400);