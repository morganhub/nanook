<?php
// public/admin/api/products/save.php
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

$id = isset($input['id']) ? (int)$input['id'] : 0;

$name = isset($input['name']) ? trim((string)$input['name']) : '';
$slug = isset($input['slug']) ? trim((string)$input['slug']) : '';
$shortDescription = isset($input['short_description']) ? trim((string)$input['short_description']) : '';
$longDescription = isset($input['long_description']) ? trim((string)$input['long_description']) : '';
$priceRaw = isset($input['price']) ? trim((string)$input['price']) : '0';

// Prix
$priceNormalized = str_replace(',', '.', $priceRaw);
$price = round((float)$priceNormalized, 2);

$stockQuantity = isset($input['stock_quantity']) ? (int)$input['stock_quantity'] : 0;
$allowPreorder = !empty($input['allow_preorder_when_oos']) ? 1 : 0;
$isActive = !empty($input['is_active']) ? 1 : 0;
$displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;
$categoryIds = isset($input['category_ids']) && is_array($input['category_ids']) ? $input['category_ids'] : [];

// Date de disponibilité (peut être null ou vide)
$availabilityDate = !empty($input['availability_date']) ? $input['availability_date'] : null;

if ($name === '' || $slug === '') {
    jsonResponse(['error' => 'name_and_slug_required'], 400);
}

if ($price < 0) $price = 0;
if ($stockQuantity < 0) $stockQuantity = 0;

$categoryIdsClean = [];
foreach ($categoryIds as $cid) {
    $cidInt = (int)$cid;
    if ($cidInt > 0) {
        $categoryIdsClean[] = $cidInt;
    }
}
$categoryIdsClean = array_values(array_unique($categoryIdsClean));

try {
    $pdo->beginTransaction();

    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE nanook_products
             SET name = :name,
                 slug = :slug,
                 short_description = :short_description,
                 long_description = :long_description,
                 price = :price,
                 stock_quantity = :stock_quantity,
                 allow_preorder_when_oos = :allow_preorder_when_oos,
                 availability_date = :availability_date,
                 is_active = :is_active,
                 display_order = :display_order,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':short_description' => $shortDescription,
            ':long_description' => $longDescription,
            ':price' => $price,
            ':stock_quantity' => $stockQuantity,
            ':allow_preorder_when_oos' => $allowPreorder,
            ':availability_date' => $availabilityDate,
            ':is_active' => $isActive,
            ':display_order' => $displayOrder,
            ':id' => $id,
        ]);

        $productId = $id;
        $action = 'product_update';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO nanook_products
            (name, slug, short_description, long_description, price, stock_quantity,
             allow_preorder_when_oos, availability_date, is_active, display_order, created_at, updated_at)
            VALUES
            (:name, :slug, :short_description, :long_description, :price, :stock_quantity,
             :allow_preorder_when_oos, :availability_date, :is_active, :display_order, NOW(), NOW())'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':short_description' => $shortDescription,
            ':long_description' => $longDescription,
            ':price' => $price,
            ':stock_quantity' => $stockQuantity,
            ':allow_preorder_when_oos' => $allowPreorder,
            ':availability_date' => $availabilityDate,
            ':is_active' => $isActive,
            ':display_order' => $displayOrder,
        ]);

        $productId = (int)$pdo->lastInsertId();
        $action = 'product_create';
    }

    // Gestion Catégories
    $delStmt = $pdo->prepare('DELETE FROM nanook_product_category WHERE product_id = :pid');
    $delStmt->execute([':pid' => $productId]);

    if (!empty($categoryIdsClean)) {
        $insSql = 'INSERT INTO nanook_product_category (product_id, category_id) VALUES ';
        $values = [];
        $params = [];
        foreach ($categoryIdsClean as $index => $cid) {
            $values[] = '(?, ?)';
            $params[] = $productId;
            $params[] = $cid;
        }
        $insSql .= implode(',', $values);
        $insStmt = $pdo->prepare($insSql);
        $insStmt->execute($params);
    }

    $pdo->commit();

    logAdminActivity($pdo, $admin['id'], $action, 'product', $productId, [
        'name' => $name,
        'product_id' => $productId,
    ]);

    jsonResponse([
        'success' => true,
        'data' => [
            'id' => $productId,
        ],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse([
        'error' => 'save_failed',
        'message' => APP_ENV === 'dev' ? $e->getMessage() : null,
    ], 500);
}