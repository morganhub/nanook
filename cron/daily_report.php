<?php
// admin/cron/daily_report.php
declare(strict_types=1);

// 1. Bootstrap
require_once __DIR__ . '/../src/config/database.php';
// Chemin corrigé vers Mailer.php (il est dans src/Mailer.php selon votre structure)
require_once __DIR__ . '/../src/Mailer.php';

// Sécurité CLI (décommentez en prod si nécessaire)
// if (php_sapi_name() !== 'cli') { die('Access denied'); }

$pdo = getPdo();
$currentHour = (int)date('H');
$todayDate = date('Y-m-d');

echo "--- Début CRON Rapport : " . date('Y-m-d H:i:s') . " ---\n";

// 2. Récupération des admins éligibles à un rapport
// On sélectionne ceux qui veulent un rapport à cette heure-ci (ou jamais)
$stmtAdmins = $pdo->prepare("
    SELECT id, email, username, report_frequency 
    FROM nanook_admin_users 
    WHERE is_active = 1 
    AND report_frequency != 'never'
    AND report_hour = :hour
");
$stmtAdmins->execute([':hour' => $currentHour]);
$admins = $stmtAdmins->fetchAll();

if (empty($admins)) {
    echo "Aucun admin configuré pour un rapport à {$currentHour}h.\n";
    exit;
}

// --- Préparation des Données (Une seule fois pour tous) ---

// A. Commandes (KPI)
$pending = $pdo->query("SELECT count(*) as count FROM nanook_orders WHERE status IN ('pending', 'confirmed')")->fetch();
$shipped = $pdo->query("SELECT count(*) as count FROM nanook_orders WHERE status = 'shipped'")->fetch();
$delivered = $pdo->query("SELECT count(*) as count FROM nanook_orders WHERE status = 'delivered' AND updated_at >= DATE_SUB(NOW(), INTERVAL 2 WEEK)")->fetch();

// A2. Commandes (Tableau Détail - Nouveau)
$sqlOrdersList = "
    SELECT order_number, created_at, customer_first_name, customer_last_name, total_amount, status, delivery_method
    FROM nanook_orders
    WHERE status NOT IN ('cancelled', 'delivered')
    ORDER BY created_at DESC
";
$ordersList = $pdo->query($sqlOrdersList)->fetchAll();

$ordersTableHtml = '<table style="width:100%; border-collapse:collapse; margin-top:15px; font-size:12px; border:1px solid #eee; background:#fff;">
    <thead>
        <tr style="background:#f4f4f4;">
            <th style="text-align:left; padding:8px; color:#555;">Ref</th>
            <th style="text-align:left; padding:8px; color:#555;">Date</th>
            <th style="text-align:left; padding:8px; color:#555;">Client</th>
            <th style="text-align:right; padding:8px; color:#555;">Total</th>
            <th style="text-align:center; padding:8px; color:#555;">Statut</th>
        </tr>
    </thead>
    <tbody>';

if (empty($ordersList)) {
    $ordersTableHtml .= '<tr><td colspan="5" style="padding:15px; text-align:center; color:#999; font-style:italic;">Aucune commande en cours. Tout est à jour ! ☀️</td></tr>';
} else {
    foreach ($ordersList as $o) {
        $d = date('d/m', strtotime($o['created_at']));
        $amt = number_format((float)$o['total_amount'], 0, ',', ' ') . ' €';
        $st = $o['status'];

        // Couleurs de statut
        $color = '#333';
        $statusLabel = $st;
        if ($st === 'pending') { $color = '#d97706'; $statusLabel = 'En attente'; } // Orange
        if ($st === 'confirmed') { $color = '#16a34a'; $statusLabel = 'Confirmée'; } // Green
        if ($st === 'shipped') { $color = '#2563eb'; $statusLabel = 'Expédiée'; } // Blue

        // Petit indicateur pour Mains Propres
        $method = ($o['delivery_method'] === 'pickup') ? ' <span style="font-size:10px; background:#eee; padding:1px 4px; border-radius:3px;">MP</span>' : '';

        $ordersTableHtml .= "
        <tr style='border-bottom:1px solid #eee;'>
            <td style='padding:8px; font-family:monospace;'>{$o['order_number']}</td>
            <td style='padding:8px;'>{$d}</td>
            <td style='padding:8px;'>{$o['customer_first_name']} {$o['customer_last_name']}{$method}</td>
            <td style='padding:8px; text-align:right;'>{$amt}</td>
            <td style='padding:8px; text-align:center; color:{$color}; font-weight:bold; font-size:11px;'>".strtoupper($statusLabel)."</td>
        </tr>";
    }
}
$ordersTableHtml .= '</tbody></table>';


// B. Trafic
function getVisitCount($pdo, $start, $end) {
    $s = $pdo->prepare("SELECT COUNT(DISTINCT visitor_hash) as cnt FROM nanook_page_stats WHERE visit_date BETWEEN :start AND :end");
    $s->execute([':start' => $start, ':end' => $end]);
    return (int)$s->fetch()['cnt'];
}
$visitsWeek = getVisitCount($pdo, date('Y-m-d', strtotime('-7 days')), $todayDate);
$visitsPrevWeek = getVisitCount($pdo, date('Y-m-d', strtotime('-14 days')), date('Y-m-d', strtotime('-8 days')));
$visitsMonth = getVisitCount($pdo, date('Y-m-01'), $todayDate);
$visitsPrevMonth = getVisitCount($pdo, date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last month')));

function calcEvol($curr, $prev) {
    if ($prev == 0) return $curr > 0 ? '+100%' : '0%';
    $p = (($curr - $prev) / $prev) * 100;
    return ($p > 0 ? '+' : '') . round($p, 1) . '%';
}

// C. Finances
function getSales($pdo, $start, $end) {
    $s = $pdo->prepare("SELECT SUM(total_amount) as total, COUNT(*) as count FROM nanook_orders WHERE status != 'cancelled' AND created_at BETWEEN :start AND :end");
    $s->execute([':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59']);
    return $s->fetch();
}
$salesMonth = getSales($pdo, date('Y-m-01'), $todayDate);
$salesPrevMonth = getSales($pdo, date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last month')));

// D. Stock & Production (Logique adaptée)
// 1. Stock Négatif = À Produire
// 2. Commandes "En cours" (non expédiées) = À Envoyer (si stock dispo) ou À Produire (si pas de stock)
// Pour simplifier la vue "Atelier", on se base sur le stock physique en base.
// Si stock < 0, c'est qu'on a vendu plus que dispo -> À produire.

$sqlProducts = "
    SELECT 
        p.id, p.name, p.stock_quantity, 
        v.id as vid, v.name as vname, v.stock_quantity as vstock
    FROM nanook_products p
    LEFT JOIN nanook_product_variants v ON v.product_id = p.id AND v.is_active = 1
    WHERE p.is_active = 1
    ORDER BY p.name ASC, v.display_order ASC
";
$rows = $pdo->query($sqlProducts)->fetchAll();

// Regroupement Parent/Variantes
$productsTree = [];
foreach ($rows as $row) {
    $pid = $row['id'];
    if (!isset($productsTree[$pid])) {
        $productsTree[$pid] = [
            'name' => $row['name'],
            'stock' => (int)$row['stock_quantity'],
            'variants' => []
        ];
    }
    if ($row['vid']) {
        $productsTree[$pid]['variants'][] = [
            'name' => $row['vname'],
            'stock' => (int)$row['vstock']
        ];
    }
}

$inventoryHtml = "";
foreach ($productsTree as $p) {
    $hasVariants = !empty($p['variants']);

    // Si variantes, on affiche le titre parent puis les lignes variantes
    if ($hasVariants) {
        $inventoryHtml .= "<tr><td colspan='3' style='background:#f0f0f0; font-weight:bold; padding:5px;'>{$p['name']}</td></tr>";
        foreach ($p['variants'] as $v) {
            $stock = $v['stock'];
            $toProduce = ($stock < 0) ? abs($stock) : 0;
            // Si stock positif, "À produire" = 0. Si stock -2, "À produire" = 2.

            $stockDisplay = ($stock < 0) ? 0 : $stock; // Stock physique réel dispo pour envoi immédiat

            $alertStyle = ($toProduce > 0) ? "color:#C18C5D; font-weight:bold;" : "color:#ccc;";

            $inventoryHtml .= "
            <tr>
                <td style='padding:5px; padding-left:20px;'>↳ {$v['name']}</td>
                <td style='padding:5px; text-align:center;'>{$stockDisplay}</td>
                <td style='padding:5px; text-align:center; $alertStyle'>{$toProduce}</td>
            </tr>";
        }
    } else {
        // Produit simple
        $stock = $p['stock'];
        $toProduce = ($stock < 0) ? abs($stock) : 0;
        $stockDisplay = ($stock < 0) ? 0 : $stock;

        $alertStyle = ($toProduce > 0) ? "color:#C18C5D; font-weight:bold;" : "color:#ccc;";

        $inventoryHtml .= "
        <tr>
            <td style='padding:5px; font-weight:bold;'>{$p['name']}</td>
            <td style='padding:5px; text-align:center;'>{$stockDisplay}</td>
            <td style='padding:5px; text-align:center; $alertStyle'>{$toProduce}</td>
        </tr>";
    }
}

// --- E. GENERATION EMAIL ---

// Formatage de la date du mois (ex: "Novembre 2025")
$fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE);
$fmt->setPattern('MMMM yyyy');
$currentMonthLabel = ucfirst($fmt->format(time()));

$subjectBase = "📊 Rapport Nanook";
$css = "body { font-family: sans-serif; color: #333; } h2 { color: #1A1A2E; border-bottom: 1px solid #eee; margin-top:20px; } .box { display:flex; gap:10px; } .kpi { background:#f9f9f9; padding:10px; flex:1; text-align:center; border-radius:4px; } .val { font-size:16px; font-weight:bold; display:block; } .lbl { font-size:11px; color:#666; text-transform:uppercase; } table { width:100%; border-collapse:collapse; margin-top:10px; } th { text-align:left; background:#eee; padding:5px; font-size:12px; } td { border-bottom:1px solid #eee; padding:5px; font-size:13px; }";

$htmlBody = "
<html><head><style>$css</style></head><body>
    <h1 style='color:#C18C5D; margin-bottom:5px;'>Point de situation</h1>
    <p style='font-size:12px; color:#666; margin-top:0;'>Généré le " . date('d/m/Y H:i') . "</p>

    <h2>📦 Commandes</h2>
    <div class='box'>
        <div class='kpi'><span class='val' style='color:#C18C5D;'>{$pending['count']}</span><span class='lbl'>À traiter</span></div>
        <div class='kpi'><span class='val'>{$shipped['count']}</span><span class='lbl'>En cours</span></div>
        <div class='kpi'><span class='val' style='color:green;'>{$delivered['count']}</span><span class='lbl'>Livrées (15j)</span></div>
    </div>
    
    <!-- Nouveau tableau des commandes actives -->
    $ordersTableHtml

    <h2>📈 Trafic & Ventes ($currentMonthLabel)</h2>
    <div class='box'>
        <div class='kpi'><span class='val'>{$visitsMonth}</span><span class='lbl'>Visiteurs</span><span style='font-size:10px;'>".calcEvol($visitsMonth, $visitsPrevMonth)."</span></div>
        <div class='kpi'><span class='val'>".number_format((float)$salesMonth['total'], 0, ',', ' ')." €</span><span class='lbl'>CA Validé</span></div>
    </div>

    <h2>🧵 Atelier : Production</h2>
    <p style='font-size:11px; color:#666; margin-bottom:10px;'>
        <strong>En stock :</strong> Quantité disponible immédiatement.<br>
        <strong>À Produire :</strong> Déficit de stock (précommandes validées).
    </p>
    <table>
        <thead><tr><th>Produit</th><th style='text-align:center;'>En Stock</th><th style='text-align:center;'>À Produire</th></tr></thead>
        <tbody>$inventoryHtml</tbody>
    </table>
</body></html>
";

// --- F. BOUCLE D'ENVOI ---

$mailer = new Mailer();

foreach ($admins as $admin) {
    // Vérification unicité envoi
    // On vérifie si un job 'report_stats' a réussi pour cet email dans la période

    $periodSql = "";
    if ($admin['report_frequency'] === 'daily') {
        // Déjà envoyé aujourd'hui ?
        $checkSql = "SELECT id FROM nanook_cron_logs WHERE job_name = :job AND executed_at >= :date AND status = 'success'";
        $params = [':job' => 'report_stats_' . $admin['id'], ':date' => date('Y-m-d 00:00:00')];
    }
    elseif ($admin['report_frequency'] === 'weekly') {
        // Déjà envoyé cette semaine (Lundi->Dimanche) ?
        if (date('N') != 1) continue; // On envoie le Lundi seulement
        $checkSql = "SELECT id FROM nanook_cron_logs WHERE job_name = :job AND executed_at >= :date AND status = 'success'";
        $params = [':job' => 'report_stats_' . $admin['id'], ':date' => date('Y-m-d 00:00:00', strtotime('monday this week'))];
    }
    elseif ($admin['report_frequency'] === 'monthly') {
        // Déjà envoyé ce mois-ci (le 1er) ?
        if (date('j') != 1) continue; // On envoie le 1er du mois
        $checkSql = "SELECT id FROM nanook_cron_logs WHERE job_name = :job AND executed_at >= :date AND status = 'success'";
        $params = [':job' => 'report_stats_' . $admin['id'], ':date' => date('Y-m-01 00:00:00')];
    } else {
        continue;
    }

    $stmtCheck = $pdo->prepare($checkSql);
    $stmtCheck->execute($params);
    if ($stmtCheck->fetch()) {
        echo "Rapport déjà envoyé pour {$admin['email']} ({$admin['report_frequency']}). Skip.\n";
        continue;
    }

    // Envoi
    echo "Envoi du rapport à {$admin['email']}...\n";
    $sent = $mailer->send($admin['email'], $subjectBase . " - " . ucfirst($admin['report_frequency']), $htmlBody);

    // Log
    $stmtLog = $pdo->prepare("INSERT INTO nanook_cron_logs (job_name, executed_at, status, message) VALUES (:job, NOW(), :status, :msg)");
    $stmtLog->execute([
        ':job' => 'report_stats_' . $admin['id'],
        ':status' => $sent ? 'success' : 'error',
        ':msg' => $sent ? "Envoyé à {$admin['email']}" : "Echec envoi à {$admin['email']}"
    ]);
}

echo "--- Fin CRON ---\n";