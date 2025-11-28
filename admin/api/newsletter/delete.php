<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';
$pdo = getPdo(); $admin = requireAdmin($pdo);

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

if ($id) {
    $pdo->prepare("DELETE FROM nanook_newsletter_subscribers WHERE id = :id")->execute([':id' => $id]);
    logAdminActivity($pdo, $admin['id'], 'newsletter_delete', 'subscriber', $id);
}
jsonResponse(['success' => true]);