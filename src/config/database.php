<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

function getPdo(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        
        if (APP_ENV === 'dev') {
            die("Erreur SQL : " . $e->getMessage());
        } else {
            die("Une erreur est survenue. Veuillez réessayer plus tard.");
        }
    }

    return $pdo;
}