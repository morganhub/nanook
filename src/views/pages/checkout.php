<?php
// src/views/pages/checkout.php
require_once __DIR__ . '/../../services/CartService.php';
$cartService = new CartService();
$cartData = $cartService->getCartDetails();

if (empty($cartData['items'])) {
    echo "<div class='nk-container' style='padding:100px 0; text-align:center;'>
            <h2 class='nk-title-lg'>Votre panier est vide</h2>
            <a href='/' class='nk-btn-primary' style='display:inline-block; width:auto; margin-top:20px;'>Retourner à la boutique</a>
          </div>";
    return;
}

// --- LOGIQUE DE DATES & TRI ---
$currentDate = date('Y-m-d');
$cutoffDate = '2025-12-20'; // Date limite pour garantir Noël
$showShippingOptions = ($currentDate <= $cutoffDate);

$christmasLimit = '2025-12-25';
$hasPreorder = $cartData['has_preorder'];

$jsItems = [];
foreach ($cartData['items'] as $item) {
    $isAvailableForXmas = true;
    $dateLabel = 'En stock';

    // Logique : Un item est "précommande" si le stock physique est insuffisant
    if ($item['is_preorder']) {
        // Si pas de date ou date après Noël -> Pas dispo pour Noël
        if (!$item['availability_date'] || $item['availability_date'] >= $christmasLimit) {
            $isAvailableForXmas = false;
        }

        if ($item['availability_date']) {
            $dt = new DateTime($item['availability_date']);
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE);
            $formatter->setPattern('MMMM yyyy');
            $dateLabel = ucfirst($formatter->format($dt));
        } else {
            $dateLabel = 'Début 2026';
        }
    }

    $jsItems[] = [
        'id' => $item['key'],
        'is_xmas' => $isAvailableForXmas,
        'date_label' => $dateLabel,
        'is_preorder' => $item['is_preorder']
    ];
}
?>

<div class="nk-container" style="padding: 60px 20px;">
    <h1 class="nk-title-lg" style="margin-bottom: 40px; text-align:center;">Validation de commande</h1>

    <form action="/checkout/process" method="POST" id="checkoutForm" style="display: grid; grid-template-columns: 1fr; gap: 40px; @media(min-width:900px){ grid-template-columns: 1.5fr 1fr; }">

        <div>
            <div style="background: #fff; padding: 30px; border: 1px solid var(--nk-border); margin-bottom: 30px;">
                <h3 class="nk-title-md" style="margin-bottom: 20px;">Mode de réception</h3>

                <label class="nk-radio-box js-method-option">
                    <input type="radio" name="delivery_method" value="shipping" checked>
                    <div>
                        <div style="font-weight: 700;">Livraison à domicile</div>
                        <div style="font-size: 0.9rem; color: #666;">Expédition par Colissimo</div>
                    </div>
                </label>

                <label class="nk-radio-box js-method-option">
                    <input type="radio" name="delivery_method" value="pickup">
                    <div>
                        <div style="font-weight: 700;">Remise en mains propres (Paris/Boulogne)</div>
                        <div style="font-size: 0.9rem; color: #666;">Sur rendez-vous</div>
                    </div>
                </label>
            </div>

            <div style="background: #fff; padding: 30px; border: 1px solid var(--nk-border); margin-bottom: 30px;">
                <h3 class="nk-title-md" style="margin-bottom: 20px;">Vos coordonnées</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div><label class="nk-label">Prénom</label><input type="text" name="firstname" class="nk-input" required></div>
                    <div><label class="nk-label">Nom</label><input type="text" name="lastname" class="nk-input" required></div>
                </div>
                <div style="margin-bottom: 0;">
                    <label class="nk-label">Email</label><input type="email" name="email" class="nk-input" required>
                </div>

                <div id="addressBlock" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h4 class="nk-title-md" style="  margin-bottom: 15px;">Adresse d'expédition</h4>
                    <div style="margin-bottom: 20px;">
                        <label class="nk-label">Adresse</label>
                        <input type="text" name="address1" class="nk-input js-addr-req" placeholder="Numéro et rue" required>
                        <input type="text" name="address2" class="nk-input" placeholder="Complément" style="margin-top:10px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div><label class="nk-label">Code Postal</label><input type="text" name="zip" class="nk-input js-addr-req" required></div>
                        <div><label class="nk-label">Ville</label><input type="text" name="city" class="nk-input js-addr-req" required></div>
                    </div>
                </div>
            </div>

            <?php if ($showShippingOptions): ?>
                <div style="background: #fff; padding: 30px; border: 1px solid var(--nk-border);">
                    <h3 class="nk-title-md" style="margin-bottom: 20px;">Délai souhaité ?</h3>

                    <label class="nk-radio-box js-shipping-option" data-mode="christmas">
                        <input type="radio" name="shipping_pref" value="christmas" required checked>
                        <div>
                            <div style="font-weight: 700;">Pour Noël 🎄</div>
                            <div style="font-size: 0.9rem; color: #666;">ce qui est prêt partira, le reste suivra.</div>
                        </div>
                    </label>

                    <label class="nk-radio-box js-shipping-option" data-mode="later">
                        <input type="radio" name="shipping_pref" value="no_preference">
                        <div>
                            <div style="font-weight: 700;">Pas d'urgence (début 2026)</div>
                        </div>
                    </label>
                </div>
            <?php else: ?>
                <input type="hidden" name="shipping_pref" value="no_preference">
            <?php endif; ?>
        </div>

        <div>
            <div style="background: #F9F9F9; padding: 30px; position: sticky; top: 120px;">
                <h3 class="nk-title-md" style="margin-bottom: 20px;">Résumé</h3>

                <div class="nk-cart-list" id="cartList">
                    <?php foreach ($cartData['items'] as $item): ?>
                        <div class="nk-cart-item js-cart-item" id="item-<?= $item['key'] ?>">
                            <button type="button" class="nk-remove-item js-remove-btn" data-key="<?= $item['key'] ?>" aria-label="Retirer">&times;</button>

                            <div class="nk-cart-thumb">
                                <a href="/p/<?= htmlspecialchars($item['slug']) ?>">
                                    <?php $img = $item['image'] ? '/storage/product_images/'.$item['image'] : '/assets/img/placeholder.jpg'; ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                </a>
                            </div>

                            <div style="flex:1; padding-right: 20px;">
                                <a href="/p/<?= htmlspecialchars($item['slug']) ?>" style="text-decoration: none; color: inherit;">
                                    <div style="font-weight:600;"><?= htmlspecialchars($item['name']) ?></div>
                                </a>

                                <?php if(!empty($item['variant_name'])): ?>
                                    <div style="font-size: 0.8rem; color: #666; margin-top:2px;"><?= htmlspecialchars($item['variant_name']) ?></div>
                                <?php endif; ?>

                                <div style="font-size: 0.8rem; color: #888; margin-top:4px;">Qté: <?= $item['quantity'] ?></div>
                            </div>

                            <div style="text-align:right;">
                                <div><?= number_format($item['line_total'], 2, ',', ' ') ?> €</div>
                                <div class="js-date-badge" style="font-size:0.75rem; margin-top:4px;"></div>
                            </div>

                            <div class="nk-item-overlay js-unavailable-overlay" style="display:none;">
                                <span>Dispo ultérieure</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 1px solid #ddd; padding-top: 20px; display: flex; justify-content: space-between; font-weight: 700; font-size: 1.2rem;">
                    <span>Total</span>
                    <span id="checkoutTotal"><?= number_format($cartData['total'], 2, ',', ' ') ?> €</span>
                </div>

                <button type="submit" class="nk-btn-primary" style="margin-top: 20px; width: 100%;">Valider la commande</button>
                <p style="font-size: 0.8rem; color: #888; text-align: center; margin-top: 10px;">le paiement s'effectuera par Wero, Paypal ou virement bancaire</p>
            </div>
        </div>

    </form>
</div>

<style>
    .nk-label { display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
    .nk-input { width: 100%; padding: 12px; border: 1px solid #ddd; font-family: inherit; font-size: 1rem; background: #fff; }
    .nk-radio-box { display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid var(--nk-border); margin-bottom: 10px; cursor: pointer; transition:0.2s; }
    .nk-radio-box:hover { border-color: var(--nk-accent); }
    .nk-radio-box:has(input:checked) { border-color: var(--nk-accent); background: #FDFBF7; }
    .nk-cart-item { display: flex; gap: 15px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; position: relative; }
    .nk-cart-thumb { width: 50px; height: 50px; background: #fff; object-fit: cover; flex-shrink: 0; border:1px solid #eee; }
    .nk-cart-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .nk-badge-preorder { display: inline-block; background: #C18C5D; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; text-transform: uppercase; }
    .nk-item-overlay { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #999; font-size: 0.8rem; backdrop-filter: grayscale(1); z-index: 2; pointer-events: none; }
    .nk-item-overlay span { display: inline-block; background: #C18C5D; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; text-transform: uppercase; }
    .nk-remove-item { position: absolute; top: 10px; right: -30px; width: 20px; height: 20px; background: none; border: none; color: #999; font-size: 2.2rem; line-height: 1; cursor: pointer; z-index: 10; padding: 0; display: flex; align-items: center; justify-content: center; }
    .nk-remove-item:hover { color: #b91c1c; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let cartItems = <?= json_encode($jsItems) ?>;

        // Elements
        const methodRadios = document.querySelectorAll('input[name="delivery_method"]');
        const addressBlock = document.getElementById('addressBlock');
        const addressRequiredInputs = document.querySelectorAll('.js-addr-req');

        const radioOptions = document.querySelectorAll('input[name="shipping_pref"]');
        const checkoutTotalEl = document.getElementById('checkoutTotal');
        const removeButtons = document.querySelectorAll('.js-remove-btn');

        // --- GESTION AFFICHAGE ADRESSE (Toggle) ---
        function toggleAddress(method) {
            if (method === 'pickup') {
                addressBlock.style.display = 'none';
                addressRequiredInputs.forEach(input => input.required = false);
            } else {
                addressBlock.style.display = 'block';
                addressRequiredInputs.forEach(input => input.required = true);
            }
        }

        methodRadios.forEach(radio => {
            radio.addEventListener('change', (e) => { toggleAddress(e.target.value); });
        });

        const checkedMethod = document.querySelector('input[name="delivery_method"]:checked');
        if(checkedMethod) toggleAddress(checkedMethod.value);

        // --- GESTION FILTRE VISUEL NOEL/PLUS TARD ---
        function updateCartView(mode) {
            cartItems.forEach(item => {
                const el = document.getElementById('item-' + item.id);
                if(!el) return;

                const badgeEl = el.querySelector('.js-date-badge');
                const overlayEl = el.querySelector('.js-unavailable-overlay');

                badgeEl.innerHTML = '';
                overlayEl.style.display = 'none';
                el.style.opacity = '1';

                if (mode === 'christmas') {
                    if (!item.is_xmas) {
                        overlayEl.style.display = 'flex';
                        badgeEl.innerHTML = `<span style="color:#C18C5D">Dispo ${item.date_label}</span>`;
                    } else {
                        if (item.is_preorder) {
                            badgeEl.innerHTML = `<span class="nk-badge-preorder">Pour Noël</span>`;
                        }
                    }
                } else {
                    if (item.is_preorder) {
                        badgeEl.innerHTML = `<span class="nk-badge-preorder">Dispo ${item.date_label}</span>`;
                    } else {
                        badgeEl.innerHTML = `<span style="color:#15803d">En stock</span>`;
                    }
                }
            });
        }

        // --- GESTION SUPPRESSION ---
        removeButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const key = btn.dataset.key;
                btn.innerHTML = '...';
                try {
                    const res = await fetch('/api/cart.php', {
                        method: 'POST', headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ action: 'remove', key: key })
                    });
                    const data = await res.json();
                    if(data.success) {
                        const el = document.getElementById('item-' + key);
                        if(el) el.remove();
                        const formattedTotal = new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(data.cart.total) + ' €';
                        if(checkoutTotalEl) checkoutTotalEl.textContent = formattedTotal;
                        const headerCount = document.getElementById('cartCount');
                        if(headerCount) headerCount.textContent = data.cart.count;
                        cartItems = cartItems.filter(item => item.id !== key);
                        if(data.cart.count === 0) window.location.reload();
                    }
                } catch(error) { console.error('Erreur suppression:', error); }
            });
        });

        radioOptions.forEach(radio => {
            radio.addEventListener('change', (e) => { updateCartView(e.target.value); });
        });

        const checkedRadio = document.querySelector('input[name="shipping_pref"]:checked');
        if(checkedRadio) { updateCartView(checkedRadio.value); } else { updateCartView('no_preference'); }
    });
</script>