<?php

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

try {
    
    $stmt = $pdo->prepare(
        'SELECT id, sku, price, stock_quantity, allow_preorder_when_oos, is_active, availability_date, short_description 
         FROM nanook_product_variants 
         WHERE product_id = :pid 
         ORDER BY id ASC' 
    );
    $stmt->execute([':pid' => $productId]);
    $variants = $stmt->fetchAll();

    
    
    

    foreach ($variants as &$v) {
        $vid = (int)$v['id'];

        
        $sqlOpts = "
            SELECT o.id, o.name 
            FROM nanook_product_variant_combinations c
            JOIN nanook_attribute_options o ON c.option_id = o.id
            JOIN nanook_attributes a ON o.attribute_id = a.id
            WHERE c.variant_id = :vid
            ORDER BY a.display_order ASC
        ";
        $stmtOpt = $pdo->prepare($sqlOpts);
        $stmtOpt->execute([':vid' => $vid]);
        $options = $stmtOpt->fetchAll();

        $v['option_ids'] = array_column($options, 'id'); 
        $v['name'] = implode(' - ', array_column($options, 'name')); 

        
        $v['price'] = $v['price'] !== null ? (float)$v['price'] : null;
        $v['stock_quantity'] = (int)$v['stock_quantity'];
        $v['allow_preorder_when_oos'] = (bool)$v['allow_preorder_when_oos'];
        $v['is_active'] = (bool)$v['is_active'];
    }
    unset($v);

    jsonResponse(['success' => true, 'data' => $variants]);

} catch (Exception $e) {
    jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
}