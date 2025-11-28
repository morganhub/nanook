<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config/database.php';


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit; 
}


$input = json_decode(file_get_contents('php://input'), true);
$pageType = $input['type'] ?? 'unknown';
$entityId = isset($input['id']) ? (int)$input['id'] : null;




$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$today = date('Y-m-d');

$visitorHash = hash('sha256', $ip . $userAgent . $today);

try {
    $pdo = getPdo();

    
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO nanook_page_stats 
        (page_type, entity_id, visitor_hash, visit_date, created_at) 
        VALUES 
        (:type, :eid, :hash, :date, NOW())
    ");

    $stmt->execute([
        ':type' => substr($pageType, 0, 50),
        ':eid'  => $entityId,
        ':hash' => $visitorHash,
        ':date' => $today
    ]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    
    echo json_encode(['ok' => false]);
}