<?php
// src/views/pages/success.php

$orderNumber = $_GET['order'] ?? null;

// Si quelqu'un arrive ici sans numéro de commande, on le renvoie à l'accueil
if (!$orderNumber) {
    echo "<script>window.location.href='/';</script>";
    exit;
}
?>

<div class="nk-container" style="padding: 120px 20px; text-align: center; max-width: 700px;">

    <div style="margin-bottom: 40px; color: #C18C5D;">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
    </div>

    <h1 class="nk-title-lg" style="margin-bottom: 20px;">Merci pour votre commande</h1>

    <p class="nk-text-body" style="font-size: 1.1rem; margin-bottom: 40px; line-height: 1.6;">
        Votre commande <strong><?= htmlspecialchars($orderNumber) ?></strong> a bien été validée.<br>
        Un email récapitulatif vient de vous être envoyé.
    </p>

    <div style="background: #fff; border: 1px solid #E5E5E5; padding: 40px; margin-bottom: 50px;">
        <h3 class="nk-title-md" style="font-size: 1.2rem; margin-bottom: 15px;">La suite ?</h3>
        <p class="nk-text-body">
            Je vous contacterai pour les modalités de règlement par Wero, Paypal ou virement bancaire
        </p>
    </div>

    <a href="/" class="nk-btn-primary" style="display: inline-block; width: auto; padding: 15px 50px; background-color: #1A1A2E; color: #fff; text-decoration: none;">
        Retourner à la boutique
    </a>
</div>