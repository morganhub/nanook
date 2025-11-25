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
        // Note : On utilise bien 'total_amount' (DECIMAL)
        $stmt = $pdo->prepare("
            INSERT INTO nanook_orders 
            (order_number, status, total_amount, customer_first_name, customer_last_name, customer_email, shipping_address_line1, shipping_address_line2, shipping_postal_code, shipping_city, shipping_preference, created_at, updated_at)
            VALUES 
            (:num, 'pending', :total, :fname, :lname, :email, :add1, :add2, :zip, :city, :pref, NOW(), NOW())
        ");

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
        // Note : On utilise 'unit_price' et 'line_total' (DECIMAL) au lieu de _cents
        $stmtItem = $pdo->prepare("
            INSERT INTO nanook_order_items 
            (order_id, product_id, variant_id, product_name, variant_name, unit_price, quantity, line_total)
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

        // 3. Log de l'email
        $stmtLog = $pdo->prepare("INSERT INTO nanook_email_logs (order_id, recipient_email, subject, sent_at) VALUES (?, ?, ?, NOW())");
        $stmtLog->execute([$orderId, $postData['email'], "Confirmation de commande $orderNumber"]);

        $pdo->commit();

        // 4. Envoi Email
        sendOrderEmails($postData, $cart, $orderNumber);

        // 5. Reset Panier
        $cartService->clear();

        // 6. Redirection
        header('Location: /confirmation?order=' . $orderNumber);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        // En prod, afficher un message générique, mais ici on affiche l'erreur pour débugger si besoin
        die("Erreur technique lors de la commande : " . $e->getMessage());
    }
}

function sendOrderEmails($customer, $cart, $orderNumber)
{
    $toClient = $customer['email'];
    $toAdmin = 'ornella1984@hotmail.com'; // À personnaliser

    $subject = "Confirmation de commande Nanook - $orderNumber";

    $itemsHtml = "<ul>";
    foreach ($cart['items'] as $item) {
        $price = number_format((float)$item['line_total'], 2, ',', ' ');
        $name = htmlspecialchars($item['name']);
        $variant = $item['variant_name'] ? " (" . htmlspecialchars($item['variant_name']) . ")" : "";
        $itemsHtml .= "<li><strong>{$name}</strong>{$variant} x{$item['quantity']} - {$price} €</li>";
    }
    $itemsHtml .= "</ul>";

    $deliveryText = ($customer['shipping_pref'] === 'christmas') ? 'Avant Noël' : 'Début 2026';
    $total = number_format((float)$cart['total'], 2, ',', ' ');

    $message = "
    <html>
    <body style='font-family:sans-serif; color:#333;'>
        <h1 style='color:#C18C5D;'>Merci pour votre commande !</h1>
        <p>Bonjour {$customer['firstname']},</p>
        <p>Votre commande <strong>$orderNumber</strong> est bien validée.</p>
        
        <div style='background:#f9f9f9; padding:15px; margin:20px 0;'>
            <h3>Récapitulatif</h3>
            $itemsHtml
            <p style='font-size:1.2em; font-weight:bold;'>Total : {$total} €</p>
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

    // Envoi
    mail($toClient, $subject, $message, $headers);
    mail($toAdmin, "[Nouvelle Commande] $orderNumber - $total €", $message, $headers);
}