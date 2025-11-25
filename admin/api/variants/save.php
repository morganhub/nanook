<?php
// public/admin/api/variants/save.php
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
$name = isset($input['name']) ? trim((string)$input['name']) : '';
$sku = isset($input['sku']) ? trim((string)$input['sku']) : '';
$material = isset($input['material']) ? trim((string)$input['material']) : '';
$color = isset($input['color']) ? trim((string)$input['color']) : '';
$priceRaw = isset($input['price']) ? trim((string)$input['price']) : '0';

// accepte "45,9" ou "45.9"
$priceNormalized = str_replace(',', '.', $priceRaw);

// on convertit et on arrondit à 2 décimales
$price = round((float)$priceNormalized, 2);
$stockQuantity = isset($input['stock_quantity']) ? (int)$input['stock_quantity'] : 0;
$allowPreorder = !empty($input['allow_preorder_when_oos']) ? 1 : 0;
$isActive = !empty($input['is_active']) ? 1 : 0;
$displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;

if ($name === '') {
    jsonResponse(['error' => 'name_required'], 400);
}
if ($stockQuantity < 0) {
    $stockQuantity = 0;
}
if ($price !== null && $price < 0) {
    $price = 0;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE nanook_product_variants
             SET name = :name,
                 sku = :sku,
                 material = :material,
                 color = :color,
                 price = :price,
                 stock_quantity = :stock_quantity,
                 allow_preorder_when_oos = :allow_preorder_when_oos,
                 is_active = :is_active,
                 display_order = :display_order,
                 updated_at = NOW()
             WHERE id = :id AND product_id = :product_id'
        );
        $stmt->execute([
            ':name' => $name,
            ':sku' => $sku !== '' ? $sku : null,
            ':material' => $material !== '' ? $material : null,
            ':color' => $color !== '' ? $color : null,
            ':price' => $price,
            ':stock_quantity' => $stockQuantity,
            ':allow_preorder_when_oos' => $allowPreorder,
            ':is_active' => $isActive,
            ':display_order' => $displayOrder,
            ':id' => $id,
            ':product_id' => $productId,
        ]);

        logAdminActivity($pdo, $admin['id'], 'variant_update', 'product', $productId, [
            'variant_id' => $id,
        ]);

        jsonResponse(['success' => true, 'data' => ['id' => $id]]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO nanook_product_variants
            (product_id, name, sku, material, color, price, stock_quantity,
             allow_preorder_when_oos, is_active, display_order, created_at, updated_at)
            VALUES
            (:product_id, :name, :sku, :material, :color, :price, :stock_quantity,
             :allow_preorder_when_oos, :is_active, :display_order, NOW(), NOW())'
        );
        $stmt->execute([
            ':product_id' => $productId,
            ':name' => $name,
            ':sku' => $sku !== '' ? $sku : null,
            ':material' => $material !== '' ? $material : null,
            ':color' => $color !== '' ? $color : null,
            ':price' => $price,
            ':stock_quantity' => $stockQuantity,
            ':allow_preorder_when_oos' => $allowPreorder,
            ':is_active' => $isActive,
            ':display_order' => $displayOrder,
        ]);

        $newId = (int)$pdo->lastInsertId();

        logAdminActivity($pdo, $admin['id'], 'variant_create', 'product', $productId, [
            'variant_id' => $newId,
        ]);

        jsonResponse(['success' => true, 'data' => ['id' => $newId]]);
    }
} catch (Throwable $e) {
    jsonResponse([
        'error' => 'save_failed',
        'message' => APP_ENV === 'dev' ? $e->getMessage() : null,
    ], 500);
}
