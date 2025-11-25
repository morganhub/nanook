<?php
// index.php
declare(strict_types=1);

// AFFICHER LES ERREURS (Temporaire pour débugger l'erreur 500)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// 1. Chargement de l'environnement et de la base de données
require_once __DIR__ . '/src/config/env.php';
require_once __DIR__ . '/src/config/database.php'; // <-- On utilise le fichier PROPRE

// 2. Démarrage de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Connexion DB
$pdo = getPdo();

// 4. Services
require_once __DIR__ . '/src/services/ProductService.php';

// 5. Routeur
require_once __DIR__ . '/src/config/routes.php';