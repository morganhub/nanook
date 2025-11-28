<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';
$pdo = getPdo(); $admin = requireAdmin($pdo);

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$email = trim($input['email'] ?? '');
$isActive = !empty($input['is_active']) ? 1 : 0;

if (!$id || !$email) jsonResponse(['error' => 'invalid_data'], 400);


$sql = "UPDATE nanook_newsletter_subscribers SET email = :email, is_active = :active";

if ($isActive === 0) {
    $sql .= ", unsubscribed_at = IF(unsubscribed_at IS NULL, NOW(), unsubscribed_at)";
} else {
    $sql .= ", unsubscribed_at = NULL";
}
$sql .= " WHERE id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email, ':active' => $isActive, ':id' => $id]);

logAdminActivity($pdo, $admin['id'], 'newsletter_update', 'subscriber', $id, ['email' => $email]);
jsonResponse(['success' => true]);