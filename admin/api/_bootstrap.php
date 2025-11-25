<?php
// public/admin/api/_bootstrap.php
declare(strict_types=1);

require __DIR__ . '/../../src/config/env.php';

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'dev' ? '1' : '0');

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Connexion DB avec erreurs parlantes (json en dev)
 */
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
        $payload = [
            'success' => false,
            'error' => 'db_connection_failed',
        ];

        if (APP_ENV === 'dev') {
            $payload['exception'] = get_class($e);
            $payload['message'] = $e->getMessage();
            $payload['dsn'] = $dsn;
            $payload['db_host'] = DB_HOST;
            $payload['db_name'] = DB_NAME;
            $payload['db_port'] = DB_PORT;
            $payload['db_user'] = DB_USER;
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(500);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    return $pdo;
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getClientIp(): ?string
{
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', (string)$_SERVER[$key]);
            return trim($ipList[0]);
        }
    }

    return null;
}

function ipToBinary(?string $ip): ?string
{
    if ($ip === null || $ip === '') {
        return null;
    }
    $packed = @inet_pton($ip);
    if ($packed === false) {
        return null;
    }
    return $packed;
}

function generateSessionId(): string
{
    return bin2hex(random_bytes(32));
}

function setSessionCookie(string $sessionId, int $ttlSeconds = 604800): void
{
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie(
        'admin_session',
        $sessionId,
        [
            'expires' => time() + $ttlSeconds,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

function clearSessionCookie(): void
{
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie(
        'admin_session',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

function logAdminActivity(
    PDO $pdo,
    ?int $adminUserId,
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    ?array $details = null
): void {
    $ip = ipToBinary(getClientIp());
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $pdo->prepare(
        'INSERT INTO nanook_admin_activity_logs
        (admin_user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
        VALUES (:admin_user_id, :action, :entity_type, :entity_id, :details, :ip_address, :user_agent, NOW())'
    );

    $stmt->execute([
        ':admin_user_id' => $adminUserId,
        ':action' => $action,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':details' => $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        ':ip_address' => $ip,
        ':user_agent' => $userAgent,
    ]);
}

function getAuthenticatedAdmin(PDO $pdo): ?array
{
    if (empty($_COOKIE['admin_session'])) {
        return null;
    }

    $sessionId = (string)$_COOKIE['admin_session'];

    $stmt = $pdo->prepare(
        'SELECT s.*, u.id AS admin_id, u.email
         FROM nanook_admin_sessions s
         INNER JOIN nanook_admin_users u ON u.id = s.admin_user_id
         WHERE s.id = :id
           AND s.expires_at > NOW()'
    );
    $stmt->execute([':id' => $sessionId]);
    $session = $stmt->fetch();

    if (!$session) {
        return null;
    }

    $updateStmt = $pdo->prepare(
        'UPDATE nanook_admin_sessions
         SET last_seen_at = NOW()
         WHERE id = :id'
    );
    $updateStmt->execute([':id' => $sessionId]);

    return [
        'id' => (int)$session['admin_id'],
        'email' => $session['email'],
        'session_id' => $sessionId,
    ];
}

function requireAdmin(PDO $pdo): array
{
    $admin = getAuthenticatedAdmin($pdo);
    if ($admin === null) {
        jsonResponse(['error' => 'unauthorized'], 401);
    }
    return $admin;
}

// CORS + préflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
