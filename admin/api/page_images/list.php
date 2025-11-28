<?php
declare(strict_types=1);
require __DIR__ . '/../_bootstrap.php';
$pdo = getPdo(); requireAdmin($pdo);
$pid = (int)($_GET['page_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM nanook_page_images WHERE page_id = :pid ORDER BY display_order ASC, id ASC");
$stmt->execute([':pid' => $pid]);
jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);