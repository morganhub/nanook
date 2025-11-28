<?php

declare(strict_types=1);


ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


require_once __DIR__ . '/src/config/env.php';
require_once __DIR__ . '/src/config/database.php'; 


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$pdo = getPdo();


require_once __DIR__ . '/src/services/ProductService.php';


require_once __DIR__ . '/src/config/routes.php';