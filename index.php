<?php
// public/index.php
declare(strict_types=1);

// 1. Chargement de l'environnement et des configs
require_once __DIR__ . '/src/config/env.php';
require_once __DIR__ . '/admin/api/_bootstrap.php'; // Pour récupérer getPdo() facilement

// 2. Démarrage de session (pour le panier plus tard)
session_start();

// 3. Connexion DB disponible pour tout le script
$pdo = getPdo();

// 4. Chargement des services nécessaires (pour récupérer les produits/SEO)
require_once __DIR__ . '/src/services/ProductService.php';

// 5. Lancement du Routeur
require_once __DIR__ . '/src/config/routes.php';