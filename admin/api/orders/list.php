<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
$shippingPreference = isset($_GET['shipping_preference']) ? trim((string)$_GET['shipping_preference']) : '';
$dateFrom = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
if ($perPage < 1) {
    $perPage = 20;
}
if ($perPage > 100) {
    $perPage = 100;
}
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(o.order_number LIKE :q
        OR CONCAT(o.customer_first_name, " ", o.customer_last_name) LIKE :q_like
        OR o.customer_email LIKE :q_like)';
    $params[':q'] = '%' . $q . '%';
    $params[':q_like'] = '%' . $q . '%';
}

if ($status !== '') {
    $where[] = 'o.status = :status';
    $params[':status'] = $status;
}

if ($shippingPreference !== '') {
    $where[] = 'o.shipping_preference = :shipping_preference';
    $params[':shipping_preference'] = $shippingPreference;
}

if ($dateFrom !== '') {
    $where[] = 'o.created_at >= :date_from';
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}

if ($dateTo !== '') {
    $where[] = 'o.created_at <= :date_to';
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$countSql = '
    SELECT COUNT(*) AS total
    FROM nanook_orders o
    ' . $whereSql;

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRow = $countStmt->fetch();
$totalItems = $totalRow ? (int)$totalRow['total'] : 0;

$totalPages = $totalItems > 0 ? (int)ceil($totalItems / $perPage) : 1;
if ($totalPages < 1) {
    $totalPages = 1;
}
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$listSql = '
    SELECT
        o.id,
        o.order_number,
        o.status,
        o.total_amount,
        o.customer_first_name,
        o.customer_last_name,
        o.customer_email,
        o.shipping_city,
        o.shipping_country,
        o.shipping_preference,
        o.created_at,
        COUNT(oi.id) AS items_count
    FROM nanook_orders o
    LEFT JOIN nanook_order_items oi ON oi.order_id = o.id
    ' . $whereSql . '
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT :limit OFFSET :offset
';

$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();

$orders = $listStmt->fetchAll();

foreach ($orders as &$order) {
    $order['id'] = (int)$order['id'];
    $order['total_amount'] = (int)$order['total_amount'];
    $order['items_count'] = (int)$order['items_count'];
}
unset($order);

jsonResponse([
    'success' => true,
    'data' => [
        'items' => $orders,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
        ],
    ],
]);
