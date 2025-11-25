<?php
// public/admin/api/customizations/options_save.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    jsonResponse(['error' => 'invalid_payload'], 400);
}

$customizationId = isset($input['customization_id']) ? (int)$input['customization_id'] : 0;
if ($customizationId <= 0) {
    jsonResponse(['error' => 'invalid_customization_id'], 400);
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
$label = isset($input['label']) ? trim((string)$input['label']) : '';
$description = isset($input['description']) ? trim((string)$input['description']) : '';
$priceDelta = isset($input['price_delta_cents']) ? (int)$input['price_delta_cents'] : 0;
$displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;

if ($label === '') {
    jsonResponse(['error' => 'label_required'], 400);
}

if ($priceDelta < 0) {
    $priceDelta = 0;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE nanook_product_customization_options
             SET label = :label,
                 description = :description,
                 price_delta_cents = :price_delta_cents,
                 display_order = :display_order
             WHERE id = :id AND customization_id = :cid'
        );
        $stmt->execute([
            ':label' => $label,
            ':description' => $description !== '' ? $description : null,
            ':price_delta_cents' => $priceDelta,
            ':display_order' => $displayOrder,
            ':id' => $id,
            ':cid' => $customizationId,
        ]);

        jsonResponse(['success' => true, 'data' => ['id' => $id]]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO nanook_product_customization_options
            (customization_id, label, description, price_delta_cents, display_order)
            VALUES
            (:cid, :label, :description, :price_delta_cents, :display_order)'
        );
        $stmt->execute([
            ':cid' => $customizationId,
            ':label' => $label,
            ':description' => $description !== '' ? $description : null,
            ':price_delta_cents' => $priceDelta,
            ':display_order' => $displayOrder,
        ]);

        $newId = (int)$pdo->lastInsertId();
        jsonResponse(['success' => true, 'data' => ['id' => $newId]]);
    }
} catch (Throwable $e) {
    jsonResponse([
        'error' => 'save_failed',
        'message' => APP_ENV === 'dev' ? $e->getMessage() : null,
    ], 500);
}
