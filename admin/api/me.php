<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = getAuthenticatedAdmin($pdo);

if ($admin === null) {
    jsonResponse(['authenticated' => false]);
}

jsonResponse([
    'authenticated' => true,
    'admin' => [
        'id' => $admin['id'],
        'email' => $admin['email'],
    ],
]);
