<?php



$pdoNav = getPdo();

$stmtNav = $pdoNav->query("SELECT name, slug, is_active FROM nanook_categories ORDER BY display_order ASC");
$navCategories = $stmtNav->fetchAll(PDO::FETCH_ASSOC);
?>

<header class="nk-header" id="mainHeader">
    <div class="nk-container nk-nav-flex">
        <button class="nk-burger" id="burgerBtn" aria-label="Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <a href="/" class="nk-logo" aria-label="Accueil">Nanook</a>

        <nav class="nk-menu-desktop">
            <?php foreach ($navCategories as $cat): ?>
                <?php if ((int)$cat['is_active'] === 1): ?>
                    <a href="/c/<?= htmlspecialchars($cat['slug']) ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>

            <a href="/i/a-propos">À Propos</a>
        </nav>

        <div class="nk-nav-actions">
            <button class="nk-cart-trigger" id="cartTrigger" aria-label="Panier">
                Panier (<span id="cartCount">0</span>)
            </button>
        </div>
    </div>
</header>

<div class="nk-cart-overlay" id="menuOverlay"></div>
<div class="nk-cart-drawer" id="menuDrawer" style="left:0; right:auto; transform:translateX(-100%);">
    <div class="nk-cart-header">
        <span class="nk-title-md">Menu</span>
        <button id="menuClose" style="font-size:1.5rem;">&times;</button>
    </div>
    <div class="nk-cart-body" style="display:flex; flex-direction:column; gap:20px; padding-top:40px;">
        <a href="/" class="nk-title-lg" style="font-size:1.5rem;">Accueil</a>

        <?php foreach ($navCategories as $cat): ?>
            <a href="/c/<?= htmlspecialchars($cat['slug']) ?>" class="nk-title-lg" style="font-size:1.5rem;">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>

        <hr style="border:0; border-top:1px solid #eee; width:100%;">

        <a href="/i/a-propos" class="nk-title-lg" style="font-size:1.5rem;">À Propos</a>

    </div>
</div>