<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$email = isset($input['email']) ? trim((string)$input['email']) : '';
$password = isset($input['password']) ? (string)$input['password'] : '';

if ($email === '' || $password === '') {
    jsonResponse(['error' => 'invalid_credentials'], 400);
}

$pdo = getPdo();

$stmt = $pdo->prepare('SELECT * FROM nanook_admin_users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    logAdminActivity($pdo, null, 'admin_login_failed', null, null, [
        'email' => $email,
    ]);
    jsonResponse(['error' => 'invalid_credentials'], 401);
}

if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare(
        'UPDATE nanook_admin_users
         SET password_hash = :hash, updated_at = NOW()
         WHERE id = :id'
    );
    $updateStmt->execute([
        ':hash' => $newHash,
        ':id' => $user['id'],
    ]);
}

$sessionId = generateSessionId();
$ttlSeconds = 604800;

$insert = $pdo->prepare(
    'INSERT INTO nanook_admin_sessions
    (id, admin_user_id, user_agent, ip_address, expires_at, created_at, last_seen_at)
    VALUES (:id, :admin_user_id, :user_agent, :ip_address,
            DATE_ADD(NOW(), INTERVAL :ttl SECOND), NOW(), NOW())'
);

$insert->execute([
    ':id' => $sessionId,
    ':admin_user_id' => $user['id'],
    ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ':ip_address' => ipToBinary(getClientIp()),
    ':ttl' => $ttlSeconds,
]);

setSessionCookie($sessionId, $ttlSeconds);

logAdminActivity($pdo, (int)$user['id'], 'admin_login');

jsonResponse([
    'success' => true,
    'admin' => [
        'id' => (int)$user['id'],
        'email' => $user['email'],
    ],
]);
