<?php
// src/services/OrderService.php
declare(strict_types=1);

require_once __DIR__ . '/CartService.php';
require_once __DIR__ . '/../config/database.php';

function processCheckout(array $postData)
{
    $cartService = new CartService();
    $cart = $cartService->getCartDetails();

    if (empty($cart['items'])) {
        header('Location: /panier');
        exit;
    }

    $pdo = getPdo();

    try {
        $pdo->beginTransaction();

        // Référence unique
        $orderNumber = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // 1. Insertion Commande
        // Attention : Si vos colonnes s'appellent encore total_amount_cents en base, renommez-les en total_amount
        // ou assurez-vous qu'elles acceptent les DECIMAL. Ici je pars du principe que c'est DECIMAL (Euros).
        $stmt = $pdo->prepare("
            INSERT INTO nanook_orders 
            (order_number, status, total_amount_cents, customer_first_name, customer_last_name, customer_email, shipping_address_line1, shipping_address_line2, shipping_postal_code, shipping_city, shipping_preference, created_at, updated_at)
            VALUES 
            (:num, 'pending', :total, :fname, :lname, :email, :add1, :add2, :zip, :city, :pref, NOW(), NOW())
        ");

        // Note: Le champ s'appelle total_amount_cents dans votre dump initial.
        // Si vous l'avez passé en DECIMAL pour stocker des euros, c'est bon.
        // Sinon, il faudrait faire $cart['total'] * 100 si c'est resté un INT.
        // Vu votre demande, je considère que c'est DECIMAL.

        $stmt->execute([
            ':num' => $orderNumber,
            ':total' => $cart['total'],
            ':fname' => $postData['firstname'],
            ':lname' => $postData['lastname'],
            ':email' => $postData['email'],
            ':add1' => $postData['address1'],
            ':add2' => $postData['address2'] ?? '',
            ':zip' => $postData['zip'],
            ':city' => $postData['city'],
            ':pref' => $postData['shipping_pref']
        ]);

        $orderId = $pdo->lastInsertId();

        // 2. Insertion Items
        $stmtItem = $pdo->prepare("
            INSERT INTO nanook_order_items 
            (order_id, product_id, variant_id, product_name, variant_name, unit_price, quantity, line_total_cents)
            VALUES 
            (:oid, :pid, :vid, :pname, :vname, :uprice, :qty, :ltotal)
        ");

        foreach ($cart['items'] as $item) {
            $stmtItem->execute([
                ':oid' => $orderId,
                ':pid' => $item['product_id'],
                ':vid' => $item['variant_id'] ?? null,
                ':pname' => $item['name'],
                ':vname' => $item['variant_name'],
                ':uprice' => $item['unit_price'],
                ':qty' => $item['quantity'],
                ':ltotal' => $item['line_total']
            ]);
        }

        // 3. Log de l'email (pour historique admin)
        $stmtLog = $pdo->prepare("INSERT INTO nanook_email_logs (order_id, recipient_email, subject, sent_at) VALUES (?, ?, ?, NOW())");
        $stmtLog->execute([$orderId, $postData['email'], "Confirmation de commande $orderNumber"]);

        $pdo->commit();

        // 4. Envoi Réel des Emails
        sendOrderEmails($postData, $cart, $orderNumber);

        // 5. Reset Panier
        $cartService->clear();

        // 6. Redirection
        header('Location: /confirmation?order=' . $orderNumber);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Erreur technique lors de la commande : " . $e->getMessage());
    }
}

function sendOrderEmails($customer, $cart, $orderNumber)
{
    $toClient = $customer['email'];
    $toAdmin = 'morgan@hotmail.com'; // Votre email

    $subject = "Confirmation de commande Nanook - $orderNumber";

    $itemsHtml = "<ul>";
    foreach ($cart['items'] as $item) {
        $itemsHtml .= "<li><strong>{$item['name']}</strong> " . ($item['variant_name'] ? "({$item['variant_name']})" : "") . " x{$item['quantity']} - " . number_format($item['line_total'], 2) . " €</li>";
    }
    $itemsHtml .= "</ul>";

    $deliveryText = ($customer['shipping_pref'] === 'christmas') ? 'Avant Noël' : 'Début 2026';

    $message = "
    <html>
    <body style='font-family:sans-serif; color:#333;'>
        <h1 style='color:#C18C5D;'>Merci pour votre commande !</h1>
        <p>Bonjour {$customer['firstname']},</p>
        <p>Votre commande <strong>$orderNumber</strong> est bien validée.</p>
        
        <div style='background:#f9f9f9; padding:15px; margin:20px 0;'>
            <h3>Récapitulatif</h3>
            $itemsHtml
            <p style='font-size:1.2em; font-weight:bold;'>Total : " . number_format($cart['total'], 2) . " €</p>
            <p><strong>Livraison souhaitée :</strong> $deliveryText</p>
            <p><strong>Adresse :</strong><br>
            {$customer['address1']}<br>
            {$customer['zip']} {$customer['city']}
            </p>
        </div>
        
        <p>À très vite,<br>L'équipe Nanook</p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Nanook Paris <no-reply@nanook.paris>" . "\r\n";

    // Envoi Client
    mail($toClient, $subject, $message, $headers);

    // Envoi Admin (Copie)
    $subjectAdmin = "[Nouvelle Commande] $orderNumber - " . number_format($cart['total'], 2) . " €";
    mail($toAdmin, $subjectAdmin, $message, $headers);
}