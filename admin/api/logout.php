<?php
// public/admin/api/logout.php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = getAuthenticatedAdmin($pdo);

if ($admin !== null) {
    $stmt = $pdo->prepare(
        'DELETE FROM nanook_admin_sessions
         WHERE id = :id'
    );
    $stmt->execute([':id' => $admin['session_id']]);
    logAdminActivity($pdo, $admin['id'], 'admin_logout');
}

clearSessionCookie();

jsonResponse(['success' => true]);
