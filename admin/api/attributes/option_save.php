<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

$input = json_decode(file_get_contents('php://input'), true);


if (isset($input['action']) && $input['action'] === 'disable') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) jsonResponse(['error' => 'invalid_id'], 400);
    $pdo->prepare("DELETE FROM nanook_attribute_options WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
}


$id = isset($input['id']) ? (int)$input['id'] : 0;
$attrId = isset($input['attribute_id']) ? (int)$input['attribute_id'] : 0;
$name = trim((string)($input['name'] ?? ''));
$value = isset($input['value']) ? trim((string)$input['value']) : null; 

if ($name === '') {
    jsonResponse(['error' => 'name_required'], 400);
}

try {
    if ($id > 0) {
        
        $stmt = $pdo->prepare("UPDATE nanook_attribute_options SET name = :name, value = :val WHERE id = :id");
        $stmt->execute([':name' => $name, ':val' => $value, ':id' => $id]);
        $newId = $id;
    } else {
        
        if ($attrId <= 0) jsonResponse(['error' => 'attribute_id_required'], 400);
        $stmt = $pdo->prepare("INSERT INTO nanook_attribute_options (attribute_id, name, value, is_active) VALUES (:aid, :name, :val, 1)");
        $stmt->execute([':aid' => $attrId, ':name' => $name, ':val' => $value]);
        $newId = (int)$pdo->lastInsertId();
    }

    jsonResponse(['success' => true, 'data' => ['id' => $newId]]);

} catch (Exception $e) {
    jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
}