<?php
// public/api/stats.php
declare(strict_types=1);

require_once __DIR__ . '/../src/config/database.php';

// Pas de session requise ici, on veut être le plus léger possible
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit; // Silence est d'or
}

// 1. Récupération des données
$input = json_decode(file_get_contents('php://input'), true);
$pageType = $input['type'] ?? 'unknown';
$entityId = isset($input['id']) ? (int)$input['id'] : null;

// 2. Création de l'empreinte visiteur (Hash)
// On combine IP, UserAgent et la date du jour.
// Cela signifie qu'un même utilisateur revenant demain sera compté comme une nouvelle visite unique "jour".
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$today = date('Y-m-d');

$visitorHash = hash('sha256', $ip . $userAgent . $today);

try {
    $pdo = getPdo();

    // 3. Insertion avec IGNORE pour gérer l'unicité sans erreur
    // Si le hash existe déjà pour ce jour/page/id, MySQL ignorera l'insertion silencieusement.
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
    // On ne veut jamais casser le front pour des stats
    echo json_encode(['ok' => false]);
}