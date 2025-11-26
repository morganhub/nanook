<?php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../../src/Mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
jsonResponse(['success' => false, 'error' => 'invalid_payload'], 400);
}

$orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$status = isset($input['status']) ? trim((string)$input['status']) : '';
$hasTracking = !empty($input['has_tracking']);
$trackingNumber = isset($input['tracking_number']) ? trim((string)$input['tracking_number']) : '';
$trackingCarrier = isset($input['tracking_carrier']) ? trim((string)$input['tracking_carrier']) : '';

$allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
$allowedCarriers = ['colissimo', 'ups', 'fedex', 'dhl'];

if ($orderId <= 0) {
jsonResponse(['success' => false, 'error' => 'invalid_id'], 400);
}

if (!in_array($status, $allowedStatuses, true)) {
jsonResponse(['success' => false, 'error' => 'invalid_status'], 400);
}

$stmt = $pdo->prepare('SELECT id, order_number, customer_email, customer_first_name, customer_last_name, tracking_number, tracking_carrier FROM nanook_orders WHERE id = :id');
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
jsonResponse(['success' => false, 'error' => 'not_found'], 404);
}

$trackingNumberToSave = $order['tracking_number'] ?? null;
$trackingCarrierToSave = $order['tracking_carrier'] ?? null;

if ($status === 'shipped' && $hasTracking) {
if ($trackingNumber === '') {
jsonResponse(['success' => false, 'error' => 'tracking_number_required'], 400);
}
if (!in_array(strtolower($trackingCarrier), $allowedCarriers, true)) {
jsonResponse(['success' => false, 'error' => 'invalid_tracking_carrier'], 400);
}
$trackingNumberToSave = $trackingNumber;
$trackingCarrierToSave = strtolower($trackingCarrier);
}

$updateStmt = $pdo->prepare(
'UPDATE nanook_orders
SET status = :status,
tracking_number = :tracking_number,
tracking_carrier = :tracking_carrier,
updated_at = NOW()
WHERE id = :id'
);

$updateStmt->execute([
':status' => $status,
':tracking_number' => $trackingNumberToSave,
':tracking_carrier' => $trackingCarrierToSave,
':id' => $orderId,
]);

if ($status === 'shipped') {
sendShipmentEmail($order, $trackingCarrierToSave, $trackingNumberToSave);
} elseif ($status === 'delivered') {
sendDeliveredEmail($order);
}

logAdminActivity($pdo, $admin['id'], 'order_status_updated', 'order', $orderId, [
'status' => $status,
'tracking_carrier' => $trackingCarrierToSave,
]);

jsonResponse([
'success' => true,
'data' => [
'order_id' => $orderId,
'status' => $status,
'tracking_number' => $trackingNumberToSave,
'tracking_carrier' => $trackingCarrierToSave,
],
]);

function sendShipmentEmail(array $order, ?string $carrier, ?string $trackingNumber): void
{
if (empty($order['customer_email'])) {
return;
}

$subject = 'Votre commande ' . $order['order_number'] . ' est en cours de livraison';
$greetingName = trim(($order['customer_first_name'] ?? '') . ' ' . ($order['customer_last_name'] ?? ''));
$trackingHtml = '';

if ($carrier && $trackingNumber) {
$trackingHtml = '<p>Suivi : ' . htmlspecialchars(strtoupper($carrier)) . ' — ' . htmlspecialchars($trackingNumber) . '</p>';
} else {
$trackingHtml = '<p>Votre colis est en cours d\'expédition.</p>';
}

$message = "<html><body style='font-family:sans-serif;'>" .
'<p>Bonjour ' . htmlspecialchars($greetingName ?: 'cher client') . ',</p>' .
'<p>Votre commande <strong>' . htmlspecialchars($order['order_number']) . '</strong> est en cours de livraison.</p>' .
$trackingHtml .
'<p>Merci pour votre confiance.</p>' .
'</body></html>';

$headers = "MIME-Version: 1.0\r\n" .
"Content-type:text/html;charset=UTF-8\r\n" .
"From: Nanook Paris <contact@nanook.paris>\r\n";

    $mailer = new Mailer();

    $mailer->send($order['customer_email'], $subject, $message);
logEmail((int)$order['id'], $order['customer_email'], $subject);
}

function sendDeliveredEmail(array $order): void
{
if (empty($order['customer_email'])) {
return;
}

$subject = 'Votre commande ' . $order['order_number'] . ' est livrée';
$greetingName = trim(($order['customer_first_name'] ?? '') . ' ' . ($order['customer_last_name'] ?? ''));
$message = "<html><body style='font-family:sans-serif;'>" .
'<p>Bonjour ' . htmlspecialchars($greetingName ?: 'cher client') . ',</p>' .
'<p>Votre commande <strong>' . htmlspecialchars($order['order_number']) . '</strong> a été livrée.</p>' .
'<p>Nous espérons que tout est conforme. Merci pour votre confiance.</p>' .
'</body></html>';

$headers = "MIME-Version: 1.0\r\n" .
"Content-type:text/html;charset=UTF-8\r\n" .
"From: Nanook Paris <contact@nanook.paris>\r\n";

    $mailer = new Mailer();
    $mailer->send($order['customer_email'], $subject, $message);
logEmail((int)$order['id'], $order['customer_email'], $subject);
}

function logEmail(int $orderId, string $recipient, string $subject): void
{
$pdo = getPdo();
$stmt = $pdo->prepare('INSERT INTO nanook_email_logs (order_id, recipient_email, subject, sent_at) VALUES (:oid, :recipient, :subject, NOW())');
$stmt->execute([
':oid' => $orderId,
':recipient' => $recipient,
':subject' => $subject,
]);
}