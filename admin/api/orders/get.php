<?php
// public/admin/api/orders/get.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(['error' => 'invalid_id'], 400);
}

$stmt = $pdo->prepare(
    'SELECT
         id,
         order_number,
         status,
         total_amount,
         customer_first_name,
         customer_last_name,
         customer_email,
         shipping_address_line1,
         shipping_address_line2,
         shipping_postal_code,
         shipping_city,
         shipping_country,
         shipping_preference,
         customer_comment,
         created_at,
         updated_at
     FROM nanook_orders
     WHERE id = :id'
);
$stmt->execute([':id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    jsonResponse(['error' => 'not_found'], 404);
}

$order['id'] = (int)$order['id'];
$order['total_amount'] = (int)$order['total_amount'];

$itemStmt = $pdo->prepare(
    'SELECT
         id,
         product_id,
         variant_id,
         product_name,
         variant_name,
         variant_sku,
         unit_price,
         quantity,
         is_preorder,
         customizations_json,
         line_total_cents
     FROM nanook_order_items
     WHERE order_id = :oid
     ORDER BY id ASC'
);
$itemStmt->execute([':oid' => $id]);
$items = $itemStmt->fetchAll();

foreach ($items as &$item) {
    $item['id'] = (int)$item['id'];
    $item['product_id'] = $item['product_id'] !== null ? (int)$item['product_id'] : null;
    $item['variant_id'] = $item['variant_id'] !== null ? (int)$item['variant_id'] : null;
    $item['unit_price'] = (int)$item['unit_price'];
    $item['quantity'] = (int)$item['quantity'];
    $item['is_preorder'] = (int)$item['is_preorder'];
    $item['line_total_cents'] = (int)$item['line_total_cents'];
    if ($item['customizations_json'] !== null) {
        $decoded = json_decode($item['customizations_json'], true);
        if (is_array($decoded)) {
            $item['customizations'] = $decoded;
        } else {
            $item['customizations'] = null;
        }
    } else {
        $item['customizations'] = null;
    }
    unset($item['customizations_json']);
}
unset($item);

jsonResponse([
    'success' => true,
    'data' => [
        'order' => $order,
        'items' => $items,
    ],
]);
