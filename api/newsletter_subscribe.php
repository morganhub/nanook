<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Email invalide']);
    exit;
}

$pdo = getPdo();

try {
    
    $stmt = $pdo->prepare("SELECT id FROM nanook_newsletter_subscribers WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        
        $pdo->prepare("UPDATE nanook_newsletter_subscribers SET is_active = 1, unsubscribed_at = NULL WHERE id = :id")
            ->execute([':id' => $existing['id']]);
    } else {
        
        $pdo->prepare("INSERT INTO nanook_newsletter_subscribers (email, is_active, created_at) VALUES (:email, 1, NOW())")
            ->execute([':email' => $email]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur technique']);
}