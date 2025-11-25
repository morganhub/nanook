<header class="nk-header" id="mainHeader">
    <div class="nk-container nk-nav-flex">
        <button class="nk-burger" id="burgerBtn" aria-label="Ouvrir le menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <a href="/" class="nk-logo" aria-label="Retour à l'accueil">Nanook</a>

        <nav class="nk-menu-desktop">
            <?php
            $currentUri = $_SERVER['REQUEST_URI'];
            $sacsActive = strpos($currentUri, '/c/sacs') !== false ? 'active' : '';
            // Note: Assure-toi que les catégories "sacs", "accessoires", "vide-poche" existent en DB avec ces slugs
            ?>
            <a href="/c/bracelet" class="<?= strpos($currentUri, 'bracelet') !== false ? 'nk-active-link' : '' ?>">Bracelets</a>
            <a href="/c/porte-carte" class="<?= strpos($currentUri, 'porte-carte') !== false ? 'nk-active-link' : '' ?>">Porte-Cartes</a>
            <a href="/c/vide-poche" class="<?= strpos($currentUri, 'vide-poche') !== false ? 'nk-active-link' : '' ?>">Vide-Poches</a>
        </nav>

        <div class="nk-nav-actions">
            <button class="nk-cart-trigger" id="cartTrigger" aria-label="Voir le panier">
                Panier (<span id="cartCount">0</span>)
            </button>
        </div>
    </div>
</header>