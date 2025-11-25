<?php
// public/admin/createAdminUser.php
declare(strict_types=1);

/**
 * Script manuel pour créer un admin avec feedback très verbeux.
 *
 * 1. Adapte $username / $email / $plainPassword ci-dessous.
 * 2. Mets APP_ENV=dev dans ton .env le temps du debug.
 * 3. Appelle ce script dans le navigateur OU en CLI : php createAdminUser.php
 * 4. Supprime-le ensuite.
 */

// chemin à adapter si besoin selon ton arbo
require __DIR__ . '/../src/config/env.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!headers_sent()) {
    header('Content-Type: text/plain; charset=utf-8');
}

$username = 'admin';
$email = 'admin@nanook.paris';
$plainPassword = 'change-me-123';

echo "=== createAdminUser.php ===\n\n";

echo "APP_ENV: " . APP_ENV . "\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_PORT: " . DB_PORT . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n\n";

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    DB_HOST,
    DB_PORT,
    DB_NAME
);

echo "DSN: {$dsn}\n\n";

try {
    echo "Connexion PDO...\n";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "OK: connexion DB réussie.\n\n";
} catch (Throwable $e) {
    echo "ERREUR CONNEXION DB:\n";
    echo "Type    : " . get_class($e) . "\n";
    echo "Message : " . $e->getMessage() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

try {
    $pdo->beginTransaction();

    echo "Vérification existence utilisateur...\n";

    $stmt = $pdo->prepare(
        'SELECT id
         FROM nanook_admin_users
         WHERE username = :username OR email = :email
         LIMIT 1'
    );
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
    ]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->rollBack();
        echo "ATTENTION: un utilisateur existe déjà avec ce username ou cet email.\n";
        echo "ID existant: " . (int)$existing['id'] . "\n";
        exit(0);
    }

    echo "Création du hash de mot de passe...\n";
    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('password_hash() a échoué');
    }

    echo "Insertion en base...\n";

    $insert = $pdo->prepare(
        'INSERT INTO nanook_admin_users
        (username, email, password_hash, is_super_admin, is_active, created_at, updated_at)
        VALUES
        (:username, :email, :password_hash, 1, 1, NOW(), NOW())'
    );
    $insert->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $passwordHash,
    ]);

    $id = (int)$pdo->lastInsertId();
    $pdo->commit();

    echo "\n=== ADMIN CRÉÉ ===\n";
    echo "ID       : {$id}\n";
    echo "Username : {$username}\n";
    echo "Email    : {$email}\n";
    echo "Password : {$plainPassword}\n\n";
    echo "Pense à supprimer ce fichier createAdminUser.php.\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "ERREUR LORS DE LA CRÉATION DE L’ADMIN:\n";
    echo "Type    : " . get_class($e) . "\n";
    echo "Message : " . $e->getMessage() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
