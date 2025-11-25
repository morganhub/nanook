document.addEventListener('DOMContentLoaded', () => {

    /* --- 1. Gestion du Panier Tiroir --- */
    const cartDrawer = document.getElementById('cartDrawer');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartTrigger = document.getElementById('cartTrigger');
    const cartClose = document.getElementById('cartClose');
    const cartCountEl = document.getElementById('cartCount');
    const cartBody = document.getElementById('cartBody');

    function openCart() {
        if(cartDrawer && cartOverlay) {
            cartDrawer.classList.add('is-open');
            cartOverlay.classList.add('is-open');
        }
    }

    function closeCart() {
        if(cartDrawer && cartOverlay) {
            cartDrawer.classList.remove('is-open');
            cartOverlay.classList.remove('is-open');
        }
    }

    if(cartTrigger) cartTrigger.addEventListener('click', openCart);
    if(cartClose) cartClose.addEventListener('click', closeCart);
    if(cartOverlay) cartOverlay.addEventListener('click', closeCart);


    /* --- 2. Quick Add (Ajout Rapide depuis la Grille) --- */
    const quickButtons = document.querySelectorAll('.nk-quick-add-btn');

    quickButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.dataset.id;
            const hasVariants = btn.dataset.hasVariants === '1';

            if (hasVariants) {
                // Pour l'instant, simple alerte. Idéalement : rediriger vers produit ou ouvrir modal
                alert("Ce produit a des variantes. Veuillez consulter la fiche produit pour choisir.");
                window.location.href = btn.closest('a').href; // Redirection vers la fiche
            } else {
                // Simulation Ajout direct
                simulateCartUpdate(productId, "Produit " + productId, "45.00 €", 1);
            }
        });
    });


    /* --- 3. Header Scroll Effect --- */
    const header = document.getElementById('mainHeader');
    if (header) {
        window.addEventListener('scroll', () => {
            if(window.scrollY > 50) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        });
    }


    /* --- 4. Gestion Formulaire Page Produit (Add to Cart complet) --- */
    const addToCartForm = document.getElementById('addToCartForm');

    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnAddToCart');
            const originalText = btn.innerText;
            btn.innerText = "Ajout en cours...";
            btn.disabled = true;

            // Récupération des données du formulaire
            const formData = new FormData(addToCartForm);
            const quantity = formData.get('quantity');

            // Récupération infos visuelles pour la simulation (titre/prix affichés sur la page)
            const productNameEl = document.querySelector('.nk-title-lg');
            const priceEl = document.querySelector('.nk-price');
            const productName = productNameEl ? productNameEl.innerText : "Produit";
            const price = priceEl ? priceEl.innerText : "-- €";

            // Simulation d'envoi serveur (Plus tard: fetch('/api/cart/add', ...))
            setTimeout(() => {
                simulateCartUpdate(null, productName, price, quantity);

                // Reset bouton
                btn.innerText = originalText;
                btn.disabled = false;
            }, 600);
        });
    }


    /* --- Fonction utilitaire pour mettre à jour le HTML du panier (Simulation) --- */
    function simulateCartUpdate(id, name, price, qty) {
        // Enlever le message "Vide" s'il existe
        if(cartBody.querySelector('p')) cartBody.innerHTML = '';

        const itemHtml = `
            <div style="display:flex; gap:10px; margin-bottom:15px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                <div style="width:60px; height:80px; background:#eee; display:flex; align-items:center; justify-content:center; font-size:0.7rem; color:#888;">IMG</div>
                <div>
                    <div style="font-weight:700; font-size:0.9rem;">${name}</div>
                    <div style="color:#888; font-size:0.8rem;">Quantité: ${qty}</div>
                    <div style="margin-top:5px; font-weight:600;">${price}</div>
                </div>
            </div>
        `;
        cartBody.insertAdjacentHTML('beforeend', itemHtml);

        // Incrément compteur
        if (cartCountEl) {
            let count = parseInt(cartCountEl.innerText) || 0;
            cartCountEl.innerText = count + parseInt(qty);
        }

        // Ouvrir le panier pour feedback
        openCart();
    }

});