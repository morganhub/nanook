<?php
// public/admin/_header.php
declare(strict_types=1);




require __DIR__ . '/head.php';
?>
<header class="admin-header">
    <style>
        .admin-header {
            max-width: 1080px;
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
        @media (max-width: 768px) {
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
        <a href="/admin/products.php"
           class="admin-nav-link<?php echo $activeMenu === 'products' ? ' admin-nav-link--active' : ''; ?>">
            Produits
        </a>
        <a href="/admin/categories.php"
           class="admin-nav-link<?php echo $activeMenu === 'categories' ? ' admin-nav-link--active' : ''; ?>">
            Catégories
        </a>
        <a href="/admin/orders.php"
           class="admin-nav-link<?php echo $activeMenu === 'orders' ? ' admin-nav-link--active' : ''; ?>">
            Commandes
        </a>
        <!--
        <a href="/admin/dashboard.php"
           class="admin-nav-link<?php echo $activeMenu === 'dashboard' ? ' admin-nav-link--active' : ''; ?>">
            Dashboard
        </a>
        -->
    </nav>
    <div class="admin-header-right">
        <span class="admin-header-user">Admin</span>
        <!-- éventuellement plus tard : bouton logout -->
    </div>
</header>
