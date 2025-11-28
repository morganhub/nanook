<?php
declare(strict_types=1);

if (!function_exists('getPdo')) {
    require_once __DIR__ . '/../src/config/database.php';
}

try {
    $pdoStats = getPdo();
    $stats = ['day' => 0, 'month' => 0];

    $today = date('Y-m-d');
    $firstDay = date('Y-m-01');

    $stmt = $pdoStats->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN visit_date = :today THEN visitor_hash END) as day_count,
            COUNT(DISTINCT visitor_hash) as month_count
        FROM nanook_page_stats 
        WHERE visit_date >= :firstDay
    ");
    $stmt->execute([':today' => $today, ':firstDay' => $firstDay]);
    $res = $stmt->fetch();

    $stats['day'] = $res['day_count'] ?? 0;
    $stats['month'] = $res['month_count'] ?? 0;

} catch(Exception $e) {
    $stats = ['day' => 0, 'month' => 0];
}

require __DIR__ . '/head.php';
?>
<header class="admin-header">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Geist", Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 16px;
        }
        input, textarea, select {
            font-family: "Geist", Arial, sans-serif;
        }

        /* Layout */
        .page {
            width: 90%;
            max-width: 1250px;
            margin: 0 auto;
            padding: 16px;
        }

        .container {
            max-width: 420px;
            margin: 80px auto;
            background: #ffffff;
            padding: 24px 28px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .title {
            font-size: 20px;
            font-weight: 600;
        }

        .brand {
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .back-link {
            font-size: 13px;
            color: #111827;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card {
            background: #ffffff;
            border-radius: 10px;
            padding: 16px 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead {
            background: #f9fafb;
        }

        th,
        td {
            padding: 8px 10px;
            text-align: left;
        }

        th {
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
            color: #666;
        }

        td {
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .badge-red {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-gray {
            background: #f3f4f6;
            color: #4b5563;
        }

        .badge-status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-status-confirmed {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-status-shipped {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-status-delivered {
            background: #def7ec;
            color: #0f5132;
        }

        .badge-status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-pref-christmas {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-pref-no {
            background: #f3f4f6;
            color: #374151;
        }

        /* Buttons & actions */
        .btn-primary,
        .btn-secondary,
        .btn-danger {
            padding: 7px 12px;
            border-radius: 4px;
            border: none;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-primary {
            background: #111827;
            color: #ffffff;
            text-decoration: none;
        }

        .btn-secondary {
            background: #ffffff;
            color: #111827;
            border: 1px solid #d1d5db;
            text-decoration: none;
        }

        .btn-danger {
            background: #b91c1c;
            color: #ffffff;
        }

        .btn-primary[disabled],
        .btn-secondary[disabled],
        .btn-danger[disabled] {
            opacity: .6;
            cursor: default;
        }

        .btn-status {
            background: #111827;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 12px;
            cursor: pointer;
            margin-right: 6px;
        }

        .btn-status:hover {
            opacity: .92;
        }

        .action-btn {
            padding: 4px 8px;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
        }

        .action-btn:hover {
            background: #e5e7eb;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            margin-bottom: 8px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
            font-weight: 500;
            color: #374151;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 12px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 7px 9px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            margin-bottom: 8px;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #111827;
        }

        .filters input[type="text"],
        .filters input[type="date"],
        .filters select {
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 13px;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .checkbox-wrapper input {
            width: auto;
        }

        .help-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Messages */
        .message {
            margin-bottom: 8px;
            font-size: 13px;
        }

        .message.error {
            color: #b91c1c;
        }

        .message.info {
            color: #4b5563;
        }

        .message.success {
            color: #15803d;
        }

        .error {
            color: #b91c1c;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .success {
            color: #15803d;
            font-size: 13px;
            margin-bottom: 8px;
        }

        /* Links */
        a.link {
            color: #111827;
            text-decoration: none;
        }

        a.link:hover {
            text-decoration: underline;
        }

        /* Filters & pagination */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 13px;
        }

        .pagination-controls {
            display: flex;
            gap: 6px;
        }
        a {
            color:#1A1A2E;
        }
        a:hover {
            text-decoration: unset;
        }
        /* Login/admin layout */
        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }

        .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 18px;
        }

        button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 4px;
            border: none;
            background: #111827;
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
        }

        button[disabled] {
            opacity: 0.6;
            cursor: default;
        }

        .hidden {
            display: none;
        }

        .admin-area {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
        }
        #logoutButton {
            padding: 4px 6px;
        }
        .admin-header {
            width: calc( 90% - 32px);
            max-width:  calc( 1250px - 32px);
            margin: 0 auto 12px;
            padding: 10px 14px;
            background: #111827;
            color: #f9fafb;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .admin-header-left {
            display: flex;
            align-items: baseline;
            gap: 10px;
            width: 190px;
        }

        .admin-header-brand {
            font-weight: 700;
            letter-spacing: .08em;
            font-size: 14px;
            text-transform: uppercase;
        }

        .admin-header-title {
            font-size: 14px;
            color: #e5e7eb;
        }

        .admin-header-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 13px;
            user-select: none;
        }

        .admin-nav-link {
            padding: 5px 10px;
            border-radius: 999px;
            text-decoration: none;
            color: #e5e7eb;
            border: 1px solid transparent;
        }

        .admin-nav-link:hover {
            background: #1f2937;
            color:#FFF;
        }

        .admin-nav-link--active {
            background: #f9fafb;
            color: #111827;
        }

        .admin-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #9ca3af;
        }

        .admin-header-user {
            font-weight: 500;
        }

        .admin-stats {
            display: flex;
            gap: 12px;
            margin-right: 15px;
            padding-right: 15px;
            border-right: 1px solid #374151;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.2;
        }

        .stat-val {
            font-weight: 700;
            color: #fff;
            font-size: 13px;
        }

        .stat-label {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
        }

        .admin-email {
            font-weight: 500;
        }

        .logout-btn {
            /*background: #b91c1c;*/
            font-size:12px;
        }

        /* Overlay & modal */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.35);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 40;
        }

        .overlay.active {
            display: flex;
        }

        .modal {
            background: #ffffff;
            border-radius: 10px;
            max-width: 420px;
            width: 100%;
            padding: 16px 18px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.18);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            margin-top: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-header-nav {
                width: 100%;
            }
        }
    </style>

    <div class="admin-header-left">
        <div>
            <div class="admin-header-brand">NANOOK</div>
            <div class="admin-header-title"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>
    <nav class="admin-header-nav">
        <a href="/admin/orders.php"
           class="admin-nav-link<?php echo $activeMenu === 'orders' ? ' admin-nav-link--active' : ''; ?>">
            Commandes
        </a>
        <a href="/admin/products.php"
           class="admin-nav-link<?php echo $activeMenu === 'products' ? ' admin-nav-link--active' : ''; ?>">
            Produits
        </a>
        <a href="/admin/categories.php"
           class="admin-nav-link<?php echo $activeMenu === 'categories' ? ' admin-nav-link--active' : ''; ?>">
            Catégories
        </a>
        <a href="/admin/attributes.php"
           class="admin-nav-link<?php echo $activeMenu === 'attributes' ? ' admin-nav-link--active' : ''; ?>">
            Attributs
        </a>
        <a href="/admin/pages.php"
           class="admin-nav-link<?php echo $activeMenu === 'pages' ? ' admin-nav-link--active' : ''; ?>">
            Pages
        </a>
        <a href="/admin/users.php"
           class="admin-nav-link<?php echo $activeMenu === 'users' ? ' admin-nav-link--active' : ''; ?>">
            Utilisateurs
        </a>
    </nav>
    <div class="admin-header-right">
        <div class="admin-stats">

            <div class="stat-item">
                <span class="stat-val"><?= number_format($stats['day']) ?></span>
                <span class="stat-label">vis./24h</span>
            </div>
            <div class="stat-item">
                <span class="stat-val"><?= number_format($stats['month']) ?></span>
                <span class="stat-label">vis./mois</span>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;">
            <span class="admin-header-user">Admin</span>
            <button id="logoutBtn" class="logout-btn">Déconnexion</button>
        </div>
    </div>
</header>
<script>
    document.getElementById('logoutBtn').addEventListener('click', async () => {
        try {
            await fetch('/admin/api/logout.php', { method: 'POST' });
            window.location.href = '/admin/index.php';
        } catch (e) {
            console.error(e);
            window.location.href = '/admin/index.php'; // Fallback
        }
    });
</script>