<?php
// public/admin/api/customizations/list.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($productId <= 0) {
    jsonResponse(['error' => 'invalid_product_id'], 400);
}

$stmt = $pdo->prepare(
    'SELECT
         id,
         label,
         field_name,
         field_type,
         is_required,
         allow_free_text,
         free_text_label,
         free_text_max_length,
         display_order,
         created_at,
         updated_at
     FROM nanook_product_customizations
     WHERE product_id = :pid
     ORDER BY display_order ASC, id ASC'
);
$stmt->execute([':pid' => $productId]);
$rows = $stmt->fetchAll();

foreach ($rows as &$row) {
    $row['id'] = (int)$row['id'];
    $row['is_required'] = (int)$row['is_required'];
    $row['allow_free_text'] = (int)$row['allow_free_text'];
    $row['free_text_max_length'] = $row['free_text_max_length'] !== null ? (int)$row['free_text_max_length'] : null;
    $row['display_order'] = (int)$row['display_order'];
}
unset($row);

if ($rows) {
    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $optStmt = $pdo->prepare(
        'SELECT
             id,
             customization_id,
             label,
             description,
             price_delta,
             display_order
         FROM nanook_product_customization_options
         WHERE customization_id IN (' . $placeholders . ')
         ORDER BY display_order ASC, id ASC'
    );
    $optStmt->execute($ids);
    $optionsByCustomization = [];
    while ($opt = $optStmt->fetch()) {
        $cid = (int)$opt['customization_id'];
        if (!isset($optionsByCustomization[$cid])) {
            $optionsByCustomization[$cid] = [];
        }
        $optionsByCustomization[$cid][] = [
            'id' => (int)$opt['id'],
            'label' => $opt['label'],
            'description' => $opt['description'],
            'price_delta' => (int)$opt['price_delta'],
            'display_order' => (int)$opt['display_order'],
        ];
    }
    foreach ($rows as &$row2) {
        $cid = $row2['id'];
        $row2['options'] = $optionsByCustomization[$cid] ?? [];
    }
    unset($row2);
}

jsonResponse(['success' => true, 'data' => $rows]);
