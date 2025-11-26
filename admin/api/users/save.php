<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'method'], 405);
$pdo = getPdo();
$currentAdmin = requireAdmin($pdo);

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? (int)$input['id'] : 0;
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? null; // Si null, on ne change pas
$isActive = !empty($input['is_active']) ? 1 : 0;
$freq = $input['report_frequency'] ?? 'daily';
$hour = (int)($input['report_hour'] ?? 8);

if (!$username || !$email) jsonResponse(['error' => 'missing_fields'], 400);

// Vérification unicité email
$check = $pdo->prepare("SELECT id FROM nanook_admin_users WHERE email = :email AND id != :id");
$check->execute([':email' => $email, ':id' => $id]);
if ($check->fetch()) jsonResponse(['error' => 'email_exists'], 400);

try {
    if ($id > 0) {
        // UPDATE
        $sql = "UPDATE nanook_admin_users SET username = :user, email = :email, is_active = :active, report_frequency = :freq, report_hour = :hour, updated_at = NOW()";
        $params = [':user' => $username, ':email' => $email, ':active' => $isActive, ':freq' => $freq, ':hour' => $hour, ':id' => $id];

        if ($password) {
            $sql .= ", password_hash = :pass";
            $params[':pass'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

    } else {
        // CREATE
        if (!$password) jsonResponse(['error' => 'password_required'], 400);

        $stmt = $pdo->prepare("INSERT INTO nanook_admin_users (username, email, password_hash, is_active, report_frequency, report_hour, created_at, updated_at) VALUES (:user, :email, :pass, :active, :freq, :hour, NOW(), NOW())");
        $stmt->execute([
            ':user' => $username,
            ':email' => $email,
            ':pass' => password_hash($password, PASSWORD_DEFAULT),
            ':active' => $isActive,
            ':freq' => $freq,
            ':hour' => $hour
        ]);
    }

    jsonResponse(['success' => true]);

} catch (Exception $e) {
    jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
}