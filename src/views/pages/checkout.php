<?php
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
?>

<div class="nk-container" style="padding: 60px 20px;">
    <h1 class="nk-title-lg" style="margin-bottom: 40px; text-align:center;">Validation de commande</h1>

    <form action="/checkout/process" method="POST" style="display: grid; grid-template-columns: 1fr; gap: 40px; @media(min-width:900px){ grid-template-columns: 1.5fr 1fr; }">

        <div>
            <div style="background: #fff; padding: 30px; border: 1px solid var(--nk-border); margin-bottom: 30px;">
                <h3 class="nk-title-md" style="margin-bottom: 20px;">Adresse de livraison</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label class="nk-label">Prénom</label>
                        <input type="text" name="firstname" class="nk-input" required>
                    </div>
                    <div>
                        <label class="nk-label">Nom</label>
                        <input type="text" name="lastname" class="nk-input" required>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="nk-label">Email</label>
                    <input type="email" name="email" class="nk-input" required>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="nk-label">Adresse</label>
                    <input type="text" name="address1" class="nk-input" placeholder="Numéro et rue" required>
                    <input type="text" name="address2" class="nk-input" placeholder="Complément (bat, étage...)" style="margin-top:10px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label class="nk-label">Code Postal</label>
                        <input type="text" name="zip" class="nk-input" required>
                    </div>
                    <div>
                        <label class="nk-label">Ville</label>
                        <input type="text" name="city" class="nk-input" required>
                    </div>
                </div>
            </div>

            <div style="background: #fff; padding: 30px; border: 1px solid var(--nk-border);">
                <h3 class="nk-title-md" style="margin-bottom: 20px;">Quand souhaitez-vous être livré ?</h3>

                <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid var(--nk-border); margin-bottom: 10px; cursor: pointer;">
                    <input type="radio" name="shipping_pref" value="christmas" required checked>
                    <div>
                        <div style="font-weight: 700;">Avant Noël</div>
                        <div style="font-size: 0.9rem; color: #666;">Je ferai le maximum pour que ce soit sous le sapin 😊</div>
                    </div>
                </label>

                <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid var(--nk-border); cursor: pointer;">
                    <input type="radio" name="shipping_pref" value="no_preference">
                    <div>
                        <div style="font-weight: 700;">Début 2026 (Janvier)</div>
                        <div style="font-size: 0.9rem; color: #666;">Prenez votre temps, je ne suis pas pressé(e).</div>
                    </div>
                </label>
            </div>
        </div>

        <div>
            <div style="background: #F9F9F9; padding: 30px; position: sticky; top: 120px;">
                <h3 class="nk-title-md" style="margin-bottom: 20px;">Résumé</h3>

                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px;">
                    <?php foreach ($cartData['items'] as $item): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                            <div>
                                <div style="font-weight:600;"><?= htmlspecialchars($item['name']) ?></div>
                                <?php if($item['variant_name']): ?>
                                    <div style="font-size: 0.8rem; color: #888;"><?= htmlspecialchars($item['variant_name']) ?></div>
                                <?php endif; ?>
                                <div style="font-size: 0.8rem; color: #888;">Qté: <?= $item['quantity'] ?></div>
                            </div>
                            <div><?= number_format($item['line_total'], 2, ',', ' ') ?> €</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 1px solid #ddd; padding-top: 20px; display: flex; justify-content: space-between; font-weight: 700; font-size: 1.2rem;">
                    <span>Total</span>
                    <span><?= number_format($cartData['total'], 2, ',', ' ') ?> €</span>
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
    .nk-input:focus { outline: none; border-color: var(--nk-accent); }
</style>