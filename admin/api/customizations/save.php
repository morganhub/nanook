<?php

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

$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
if ($productId <= 0) {
    jsonResponse(['error' => 'invalid_product_id'], 400);
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
$label = isset($input['label']) ? trim((string)$input['label']) : '';
$fieldName = isset($input['field_name']) ? trim((string)$input['field_name']) : '';
$fieldType = isset($input['field_type']) ? trim((string)$input['field_type']) : '';
$isRequired = !empty($input['is_required']) ? 1 : 0;
$allowFreeText = !empty($input['allow_free_text']) ? 1 : 0;
$freeTextLabel = isset($input['free_text_label']) ? trim((string)$input['free_text_label']) : '';
$freeTextMaxLength = isset($input['free_text_max_length']) && $input['free_text_max_length'] !== null
    ? (int)$input['free_text_max_length']
    : null;
$displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;

if ($label === '' || $fieldName === '' || $fieldType === '') {
    jsonResponse(['error' => 'missing_fields'], 400);
}

if (!in_array($fieldType, ['text', 'textarea', 'select', 'checkbox'], true)) {
    jsonResponse(['error' => 'invalid_field_type'], 400);
}

if ($freeTextMaxLength !== null && $freeTextMaxLength < 1) {
    $freeTextMaxLength = null;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE nanook_product_customizations
             SET label = :label,
                 field_name = :field_name,
                 field_type = :field_type,
                 is_required = :is_required,
                 allow_free_text = :allow_free_text,
                 free_text_label = :free_text_label,
                 free_text_max_length = :free_text_max_length,
                 display_order = :display_order,
                 updated_at = NOW()
             WHERE id = :id AND product_id = :product_id'
        );
        $stmt->execute([
            ':label' => $label,
            ':field_name' => $fieldName,
            ':field_type' => $fieldType,
            ':is_required' => $isRequired,
            ':allow_free_text' => $allowFreeText,
            ':free_text_label' => $freeTextLabel !== '' ? $freeTextLabel : null,
            ':free_text_max_length' => $freeTextMaxLength,
            ':display_order' => $displayOrder,
            ':id' => $id,
            ':product_id' => $productId,
        ]);

        logAdminActivity($pdo, $admin['id'], 'customization_update', 'product', $productId, [
            'customization_id' => $id,
        ]);

        jsonResponse(['success' => true, 'data' => ['id' => $id]]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO nanook_product_customizations
            (product_id, label, field_name, field_type,
             is_required, allow_free_text, free_text_label, free_text_max_length,
             display_order, created_at, updated_at)
            VALUES
            (:product_id, :label, :field_name, :field_type,
             :is_required, :allow_free_text, :free_text_label, :free_text_max_length,
             :display_order, NOW(), NOW())'
        );
        $stmt->execute([
            ':product_id' => $productId,
            ':label' => $label,
            ':field_name' => $fieldName,
            ':field_type' => $fieldType,
            ':is_required' => $isRequired,
            ':allow_free_text' => $allowFreeText,
            ':free_text_label' => $freeTextLabel !== '' ? $freeTextLabel : null,
            ':free_text_max_length' => $freeTextMaxLength,
            ':display_order' => $displayOrder,
        ]);

        $newId = (int)$pdo->lastInsertId();

        logAdminActivity($pdo, $admin['id'], 'customization_create', 'product', $productId, [
            'customization_id' => $newId,
        ]);

        jsonResponse(['success' => true, 'data' => ['id' => $newId]]);
    }
} catch (Throwable $e) {
    jsonResponse([
        'error' => 'save_failed',
        'message' => APP_ENV === 'dev' ? $e->getMessage() : null,
    ], 500);
}
