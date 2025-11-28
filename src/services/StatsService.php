<?php

declare(strict_types=1);

function recordVisit(PDO $pdo): void
{
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) return;

    $pageType = $input['type'] ?? 'unknown';
    $entityId = isset($input['id']) ? (int)$input['id'] : null;

    
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $today = date('Y-m-d');

    $visitorHash = hash('sha256', $ip . $userAgent . $today);

    try {
        
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

        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false]);
    }
}