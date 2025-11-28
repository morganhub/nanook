<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    jsonResponse(['error' => 'invalid_order_id'], 400);
}

$stmt = $pdo->prepare(
    'SELECT
         id,
         order_id,
         recipient_email,
         subject,
         sent_at
     FROM nanook_email_logs
     WHERE order_id = :oid
     ORDER BY sent_at DESC, id DESC'
);
$stmt->execute([':oid' => $orderId]);
$logs = $stmt->fetchAll();

foreach ($logs as &$log) {
    $log['id'] = (int)$log['id'];
    $log['order_id'] = $log['order_id'] !== null ? (int)$log['order_id'] : null;
}
unset($log);

jsonResponse([
    'success' => true,
    'data' => $logs,
]);
