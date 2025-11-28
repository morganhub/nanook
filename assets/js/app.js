document.addEventListener('DOMContentLoaded', () => {

    /* --- 1. Variables Globales UI --- */
    const cartDrawer = document.getElementById('cartDrawer');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartTrigger = document.getElementById('cartTrigger');
    const cartClose = document.getElementById('cartClose');
    const cartCountEl = document.getElementById('cartCount');
    const cartBody = document.getElementById('cartBody');
    const cartTotal = document.getElementById('cartTotal');

    /* --- 2. Fonctions du Panier (Drawer) --- */
    function openCart() {
        if (cartDrawer && cartOverlay) {
            cartDrawer.classList.add('is-open');
            cartOverlay.classList.add('is-open');
        }
    }

    function closeCart() {
        if (cartDrawer && cartOverlay) {
            cartDrawer.classList.remove('is-open');
            cartOverlay.classList.remove('is-open');
        }
    }

    if (cartTrigger) cartTrigger.addEventListener('click', openCart);
    if (cartClose) cartClose.addEventListener('click', closeCart);
    if (cartOverlay) cartOverlay.addEventListener('click', closeCart);

    /* --- 3. Logique API (Communication Serveur) --- */
    async function updateCart(action, payload = {}) {
        try {
            // Appel AJAX vers le fichier PHP à la racine
            const res = await fetch('/api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...payload })
            });
            const data = await res.json();

            if (data.success) {
                renderCart(data.cart); // Mise à jour visuelle
                if (action === 'add') openCart(); // Ouvre le tiroir après un ajout
            } else {
                console.error("Erreur serveur:", data.message);
            }
        } catch (e) {
            console.error("Erreur réseau panier:", e);
        }
    }

    /* --- 4. Rendu Visuel du Panier (DOM) --- */
    function renderCart(cart) {
        // 1. Compteur Header
        if (cartCountEl) cartCountEl.innerText = cart.count;

        // 2. Liste des produits
        if (cartBody) {
            if (cart.items.length === 0) {
                cartBody.innerHTML = '<p style="text-align:center; color:#888; margin-top:50px;">Votre panier est vide.</p>';
            } else {
                cartBody.innerHTML = cart.items.map(item => {
                    // Logique d'image : Si image en base ? Sinon Placeholder.
                    const imgSrc = item.image
                        ? '/storage/product_images/' + item.image
                        : '/assets/img/placeholder.jpg';

                    return `
            <div style="display:flex; gap:10px; margin-bottom:15px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                <div style="width:60px; height:80px; background:#f9f9f9; overflow:hidden; flex-shrink:0;">
                   <img src="${imgSrc}" style="width:100%; height:100%; object-fit:cover;" alt="${item.name}">
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; font-size:0.9rem; margin-bottom:2px;">${item.name}</div>
                    ${item.variant_name ? `<div style="font-size:0.8rem; color:#888;">${item.variant_name}</div>` : ''}
                    <div style="font-size:0.8rem; color:#888; margin-top:4px;">Qté: ${item.quantity}</div>
                    <div style="margin-top:4px; font-weight:600;">${parseFloat(item.line_total).toFixed(2)} €</div>
                </div>
                <button onclick="window.removeItem('${item.key}')" style="color:#999; font-size:1.2rem; padding:0 10px; border:none; background:none; cursor:pointer;">&times;</button>
            </div>
            `;
                }).join('');
            }
        }

        // 3. Total
        if (cartTotal) cartTotal.innerText = parseFloat(cart.total).toFixed(2) + ' €';
    }

    // Exposer la fonction remove au scope global pour les boutons générés en HTML
    window.removeItem = function(key) {
        updateCart('remove', { key });
    };

    /* --- 5. Events : Ajout Rapide (Grille Homepage) --- */
    const quickButtons = document.querySelectorAll('.nk-quick-add-btn');
    quickButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.dataset.id;
            const hasVariants = btn.dataset.hasVariants === '1';

            if (hasVariants) {
                // Redirection vers la fiche produit si variantes
                window.location.href = btn.closest('a').href;
            } else {
                // Ajout direct (Quantité 1 par défaut)
                updateCart('add', {
                    product_id: productId,
                    quantity: 1
                });
            }
        });
    });

    /* --- 6. Events : Formulaire Fiche Produit --- */
    const addToCartForm = document.getElementById('addToCartForm');
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnAddToCart');
            const originalText = btn.innerText;
            btn.innerText = "Ajout...";
            btn.disabled = true;

            const formData = new FormData(addToCartForm);

            // Construction payload propre
            const payload = {
                product_id: formData.get('product_id'),
                variant_id: formData.get('variant_id') || null,
                quantity: formData.get('quantity')
                // customization: ... (si vous ajoutez des champs texte plus tard)
            };

            updateCart('add', payload).then(() => {
                // Reset UI
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });
    }

    /* --- 7. Scroll Header --- */
    const header = document.getElementById('mainHeader');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        });
    }

    // Initialisation : Charger le panier au démarrage
    updateCart('get');


    const burgerBtn = document.getElementById('burgerBtn');
    const menuDrawer = document.getElementById('menuDrawer');
    const menuOverlay = document.getElementById('menuOverlay');
    const menuClose = document.getElementById('menuClose');

    function openMenu() {
        if(menuDrawer && menuOverlay) {
            menuDrawer.classList.add('is-open');
            menuDrawer.style.transform = 'translateX(0)'; // Force override
            menuOverlay.classList.add('is-open');
        }
    }

    function closeMenu() {
        if(menuDrawer && menuOverlay) {
            menuDrawer.classList.remove('is-open');
            menuDrawer.style.transform = 'translateX(-100%)';
            menuOverlay.classList.remove('is-open');
        }
    }

    if(burgerBtn) burgerBtn.addEventListener('click', openMenu);
    if(menuClose) menuClose.addEventListener('click', closeMenu);
    if(menuOverlay) menuOverlay.addEventListener('click', closeMenu);


});




(function() {
    // Configuration
    const DELAY_BEFORE_TRACKING = 2000; // 2 secondes
    let hasTracked = false;

    // Détection du contexte de page
    // On essaie de deviner le type de page via l'URL ou des éléments du DOM
    function getPageContext() {
        const path = window.location.pathname;

        if (path === '/' || path === '/index.php') return { type: 'home' };
        if (path.startsWith('/c/')) return { type: 'category', id: null }; // Idéalement récupérer l'ID via un data-attribute caché
        if (path.startsWith('/p/')) {
            // Sur la page produit, on peut souvent trouver l'ID dans un input caché
            const input = document.querySelector('input[name="product_id"]');
            const pid = input ? input.value : null;
            return { type: 'product', id: pid };
        }
        if (path === '/checkout') return { type: 'checkout' };
        if (path === '/panier') return { type: 'cart' };

        return { type: 'other' };
    }

    function sendStat() {
        if (hasTracked) return;
        hasTracked = true;

        const context = getPageContext();

        // Envoi asynchrone silencieux (beacon ou fetch keepalive si possible, sinon fetch standard)
        fetch('/api/stats.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(context),
            keepalive: true
        }).catch(() => {}); // On ignore les erreurs silencieusement

        // Nettoyage des écouteurs
        removeListeners();
    }

    function initTracking() {
        // On ajoute les écouteurs d'interaction humaine
        ['mousemove', 'touchstart', 'scroll', 'keydown', 'click'].forEach(evt => {
            document.addEventListener(evt, onHumanInteraction, { passive: true, once: true });
        });
    }

    function onHumanInteraction() {
        // Dès qu'une interaction est détectée, on lance l'envoi
        sendStat();
    }

    function removeListeners() {
        ['mousemove', 'touchstart', 'scroll', 'keydown', 'click'].forEach(evt => {
            document.removeEventListener(evt, onHumanInteraction);
        });
    }


    const fltooltip_init = () => {
        let tooltipContainer = document.getElementById('fltooltip_container');
        if (!tooltipContainer) {
            tooltipContainer = document.createElement('div');
            tooltipContainer.id = 'fltooltip_container';
            tooltipContainer.innerHTML = '<div id="fltooltip_content"></div><div id="fltooltip_arrow"></div>';
            document.body.appendChild(tooltipContainer);
        }

        const tooltipContent = document.getElementById('fltooltip_content');
        let touchTimer = null;
        let checkInterval = null;
        let currentTriggerElement = null;
        let mousePos = { x: 0, y: 0 };

        // Suivi global de la souris pour la détection de sortie précise
        document.addEventListener('mousemove', (e) => {
            mousePos.x = e.clientX;
            mousePos.y = e.clientY;
        });

        const checkCursorOverlap = () => {
            if (!currentTriggerElement || !tooltipContainer.classList.contains('visible')) {
                return;
            }
            const rect = currentTriggerElement.getBoundingClientRect();
            // Marge de tolérance de 2px
            const isOver = (
                mousePos.x >= rect.left - 2 &&
                mousePos.x <= rect.right + 2 &&
                mousePos.y >= rect.top - 2 &&
                mousePos.y <= rect.bottom + 2
            );

            if (!isOver) {
                fltooltip_hide();
            }
        };

        const fltooltip_show = (element) => {
            const text = element.dataset.fltooltip;
            if (!text) return;

            currentTriggerElement = element;
            tooltipContent.innerHTML = text;

            const rect = element.getBoundingClientRect();
            tooltipContainer.classList.remove('arrow-right', 'arrow-left');

            // Calcul dimensions
            const tooltipRect = tooltipContainer.getBoundingClientRect();

            // Positionnement par défaut : Droite
            let left = rect.right + 10;
            const elementCenterY = rect.top + (rect.height / 2);
            let top = elementCenterY - (tooltipRect.height / 2);
            let positionIsLeft = false;

            // Si ça dépasse à droite, on passe à gauche
            if (left + tooltipRect.width > window.innerWidth - 10) {
                left = rect.left - tooltipRect.width - 10;
                positionIsLeft = true;
            }

            // Gestion débordement vertical
            if (top < 10) top = 10;
            else if (top + tooltipRect.height > window.innerHeight - 10) {
                top = window.innerHeight - 10 - tooltipRect.height;
            }

            // Application des classes de flèche
            if (positionIsLeft) {
                tooltipContainer.classList.add('arrow-right');
            } else {
                tooltipContainer.classList.add('arrow-left');
            }

            tooltipContainer.style.top = `${top}px`;
            tooltipContainer.style.left = `${left}px`;
            tooltipContainer.classList.add('visible');

            if (!checkInterval) {
                checkInterval = setInterval(checkCursorOverlap, 200);
            }
        };

        const fltooltip_hide = () => {
            tooltipContainer.classList.remove('visible');
            currentTriggerElement = null;
            if (checkInterval) {
                clearInterval(checkInterval);
                checkInterval = null;
            }
        };

        // Initialisation des triggers
        const attachTooltips = () => {
            document.querySelectorAll('[data-fltooltip]:not([data-fltooltip-attached])').forEach(el => {
                el.setAttribute('data-fltooltip-attached', 'true');

                // Désactivation du title natif pour éviter le doublon
                if(el.hasAttribute('title')) {
                    el.setAttribute('data-original-title', el.getAttribute('title'));
                    el.removeAttribute('title');
                }

                // Desktop (Souris)
                el.addEventListener('mouseenter', () => fltooltip_show(el));
                el.addEventListener('mouseleave', fltooltip_hide);

                // Mobile (Long Touch)
                el.addEventListener('touchstart', (e) => {
                    // On ne bloque pas le scroll, mais on prépare le tooltip
                    mousePos.x = e.touches[0].clientX;
                    mousePos.y = e.touches[0].clientY;

                    touchTimer = setTimeout(() => {
                        fltooltip_show(el);
                        // Petit retour haptique si supporté
                        if (window.navigator && window.navigator.vibrate) {
                            window.navigator.vibrate(50);
                        }
                    }, 500); // 500ms appui long
                }, { passive: true });

                el.addEventListener('touchend', () => {
                    if (touchTimer) {
                        clearTimeout(touchTimer); // Annule si on relâche trop vite (clic simple)
                        touchTimer = null;
                    }
                    fltooltip_hide(); // Cache au relâchement
                });

                el.addEventListener('touchmove', () => {
                    if (touchTimer) {
                        clearTimeout(touchTimer); // Annule si on scroll
                        touchTimer = null;
                    }
                    fltooltip_hide();
                });
            });
        };

        // Premier lancement
        attachTooltips();

        // (Optionnel) Observer pour les éléments chargés dynamiquement (AJAX)
        // const observer = new MutationObserver(attachTooltips);
        // observer.observe(document.body, { childList: true, subtree: true });
    };

    // Lancement du système
    fltooltip_init();


    // Démarrage différé "Anti-bot temporel"
    setTimeout(initTracking, DELAY_BEFORE_TRACKING);

})();