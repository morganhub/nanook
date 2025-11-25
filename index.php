<?php
// index.php
declare(strict_types=1);

// 1. Chargement de la configuration
require_once __DIR__ . '/src/config/env.php';
require_once __DIR__ . '/src/config/database.php';

// 2. Démarrage de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Connexion DB
$pdo = getPdo();

// 4. Chargement des services
require_once __DIR__ . '/src/services/ProductService.php';

// 5. Routage
require_once __DIR__ . '/src/config/routes.php';