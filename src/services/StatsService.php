<?php
// src/services/StatsService.php
declare(strict_types=1);

function recordVisit(PDO $pdo): void
{
    // On ne traite que les POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    // Récupération du JSON envoyé par JS
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) return;

    $pageType = $input['type'] ?? 'unknown';
    $entityId = isset($input['id']) ? (int)$input['id'] : null;

    // Création du hash unique (IP + UserAgent + Date)
    // Cela permet de compter un visiteur unique par jour sans stocker l'IP en clair (RGPD friendly-ish)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $today = date('Y-m-d');

    $visitorHash = hash('sha256', $ip . $userAgent . $today);

    try {
        // Insertion avec IGNORE pour éviter les erreurs de doublons (grâce à la clé UNIQUE en base)
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

        // Réponse JSON succès
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // En cas d'erreur, on ne casse rien, on log juste si besoin
        // error_log($e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false]);
    }
}