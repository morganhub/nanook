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


$id = isset($input['id']) ? (int)$input['id'] : 0;
$name = isset($input['name']) ? trim((string)$input['name']) : '';
$slug = isset($input['slug']) ? trim((string)$input['slug']) : '';
$shortDescription = isset($input['short_description']) ? trim((string)$input['short_description']) : '';
$longDescription = isset($input['long_description']) ? trim((string)$input['long_description']) : '';


$priceRaw = isset($input['price']) ? trim((string)$input['price']) : '0';
$priceNormalized = str_replace(',', '.', $priceRaw);
$price = round((float)$priceNormalized, 2);
if ($price < 0) $price = 0;

$stockQuantity = isset($input['stock_quantity']) ? (int)$input['stock_quantity'] : 0;
if ($stockQuantity < 0) $stockQuantity = 0;

$allowPreorder = !empty($input['allow_preorder_when_oos']) ? 1 : 0;
$isActive = !empty($input['is_active']) ? 1 : 0;
$displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;
$availabilityDate = !empty($input['availability_date']) ? $input['availability_date'] : null;

$categoryIds = isset($input['category_ids']) && is_array($input['category_ids']) ? $input['category_ids'] : [];
$variantsInput = isset($input['variants']) && is_array($input['variants']) ? $input['variants'] : [];

if ($name === '' || $slug === '') {
    jsonResponse(['error' => 'name_and_slug_required'], 400);
}


$categoryIdsClean = [];
foreach ($categoryIds as $cid) {
    $cidInt = (int)$cid;
    if ($cidInt > 0) $categoryIdsClean[] = $cidInt;
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
        $actionLog = 'product_update';
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
        $actionLog = 'product_create';
    }

    
    $delStmt = $pdo->prepare('DELETE FROM nanook_product_category WHERE product_id = :pid');
    $delStmt->execute([':pid' => $productId]);

    if (!empty($categoryIdsClean)) {
        $values = [];
        $params = [];
        foreach ($categoryIdsClean as $cid) {
            $values[] = '(?, ?)';
            $params[] = $productId;
            $params[] = $cid;
        }
        $insSql = 'INSERT INTO nanook_product_category (product_id, category_id) VALUES ' . implode(',', $values);
        $pdo->prepare($insSql)->execute($params);
    }

    

    $processedVariantIds = [];

    
    $stmtInsertVar = $pdo->prepare(
        'INSERT INTO nanook_product_variants
        (product_id, sku, price, stock_quantity, allow_preorder_when_oos, is_active, availability_date, short_description, created_at, updated_at)
        VALUES (:pid, :sku, :price, :stock, :preco, :active, :avail, :sdesc, NOW(), NOW())'
    );

    $stmtUpdateVar = $pdo->prepare(
        'UPDATE nanook_product_variants
         SET sku = :sku,
             price = :price,
             stock_quantity = :stock,
             allow_preorder_when_oos = :preco,
             is_active = :active,
             availability_date = :avail,
             short_description = :sdesc,
             updated_at = NOW()
         WHERE id = :vid AND product_id = :pid'
    );

    
    $stmtDelCombos = $pdo->prepare('DELETE FROM nanook_product_variant_combinations WHERE variant_id = :vid');
    $stmtInsCombo = $pdo->prepare('INSERT INTO nanook_product_variant_combinations (variant_id, option_id) VALUES (:vid, :oid)');

    foreach ($variantsInput as $vData) {
        $vid = isset($vData['id']) ? (int)$vData['id'] : 0;

        
        $vSku = trim((string)($vData['sku'] ?? ''));
        $vStock = (int)($vData['stock'] ?? 0);
        $vPreco = !empty($vData['allow_preorder']) ? 1 : 0;
        $vActive = !empty($vData['is_active']) ? 1 : 0;
        $vAvail = !empty($vData['availability_date']) ? $vData['availability_date'] : null;
        $vSDesc = trim((string)($vData['short_description'] ?? ''));

        
        $vPriceVal = $vData['price']; 
        $vPrice = ($vPriceVal !== '' && $vPriceVal !== null) ? (float)$vPriceVal : null;

        if ($vid > 0) {
            
            $stmtUpdateVar->execute([
                ':sku' => $vSku,
                ':price' => $vPrice,
                ':stock' => $vStock,
                ':preco' => $vPreco,
                ':active' => $vActive,
                ':avail' => $vAvail,
                ':sdesc' => $vSDesc,
                ':vid' => $vid,
                ':pid' => $productId
            ]);
            $currentVariantId = $vid;
        } else {
            
            $stmtInsertVar->execute([
                ':pid' => $productId,
                ':sku' => $vSku,
                ':price' => $vPrice,
                ':stock' => $vStock,
                ':preco' => $vPreco,
                ':active' => $vActive,
                ':avail' => $vAvail,
                ':sdesc' => $vSDesc
            ]);
            $currentVariantId = (int)$pdo->lastInsertId();
        }

        $processedVariantIds[] = $currentVariantId;

        
        
        $stmtDelCombos->execute([':vid' => $currentVariantId]);

        
        if (!empty($vData['option_ids']) && is_array($vData['option_ids'])) {
            foreach ($vData['option_ids'] as $oid) {
                $oid = (int)$oid;
                if ($oid > 0) {
                    $stmtInsCombo->execute([':vid' => $currentVariantId, ':oid' => $oid]);
                }
            }
        }
    }

    
    if (!empty($processedVariantIds)) {
        $placeholders = implode(',', array_fill(0, count($processedVariantIds), '?'));
        $sqlDelVars = "DELETE FROM nanook_product_variants WHERE product_id = ? AND id NOT IN ($placeholders)";
        $paramsDel = array_merge([$productId], $processedVariantIds);
        $pdo->prepare($sqlDelVars)->execute($paramsDel);
    } else {
        
        $pdo->prepare("DELETE FROM nanook_product_variants WHERE product_id = ?")->execute([$productId]);
    }

    $pdo->commit();

    logAdminActivity($pdo, $admin['id'], $actionLog, 'product', $productId, [
        'name' => $name,
        'variant_count' => count($processedVariantIds)
    ]);

    jsonResponse([
        'success' => true,
        'data' => ['id' => $productId]
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