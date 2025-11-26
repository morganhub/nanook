<?php
// src/services/OrderService.php
declare(strict_types=1);

require_once __DIR__ . '/CartService.php';
require_once __DIR__ . '/../config/database.php';
// On inclut le Mailer s'il existe
if (file_exists(__DIR__ . '/../Mailer.php')) {
    require_once __DIR__ . '/../Mailer.php';
}

function processCheckout(array $postData)
{
    // Activer les logs d'erreurs temporairement pour le debug
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../../php-error.log');

    $cartService = new CartService();
    $cart = $cartService->getCartDetails();

    if (empty($cart['items'])) {
        header('Location: /panier');
        exit;
    }

    $pdo = getPdo();

    try {
        $pdo->beginTransaction();

        $orderNumber = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // --- Récupération du mode de livraison ---
        $deliveryMethod = $postData['delivery_method'] ?? 'shipping';

        // Nettoyage des champs adresse
        $addr1 = ($deliveryMethod === 'pickup') ? '' : ($postData['address1'] ?? '');
        $addr2 = ($deliveryMethod === 'pickup') ? '' : ($postData['address2'] ?? '');
        $zip   = ($deliveryMethod === 'pickup') ? '' : ($postData['zip'] ?? '');
        $city  = ($deliveryMethod === 'pickup') ? '' : ($postData['city'] ?? '');
        $pref  = $postData['shipping_pref'] ?? 'no_preference';

        // 1. Insertion Commande
        $stmt = $pdo->prepare("
            INSERT INTO nanook_orders 
            (order_number, status, total_amount, delivery_method, customer_first_name, customer_last_name, customer_email, shipping_address_line1, shipping_address_line2, shipping_postal_code, shipping_city, shipping_preference, created_at, updated_at)
            VALUES 
            (:num, 'pending', :total, :method, :fname, :lname, :email, :add1, :add2, :zip, :city, :pref, NOW(), NOW())
        ");

        $stmt->execute([
            ':num' => $orderNumber,
            ':total' => $cart['total'],
            ':method' => $deliveryMethod,
            ':fname' => $postData['firstname'],
            ':lname' => $postData['lastname'],
            ':email' => $postData['email'],
            ':add1' => $addr1,
            ':add2' => $addr2,
            ':zip' => $zip,
            ':city' => $city,
            ':pref' => $pref
        ]);

        $orderId = (int)$pdo->lastInsertId();

        // 2. Insertion Items & Stock Update
        $stmtItem = $pdo->prepare("
            INSERT INTO nanook_order_items 
            (order_id, product_id, variant_id, product_name, variant_name, variant_sku, unit_price, quantity, line_total, is_preorder, customizations_json)
            VALUES 
            (:oid, :pid, :vid, :pname, :vname, :sku, :uprice, :qty, :ltotal, :is_preorder, :cust_json)
        ");

        // Requêtes de mise à jour de stock (négatif autorisé)
        $stmtUpdateVariantStock = $pdo->prepare("
            UPDATE nanook_product_variants 
            SET stock_quantity = stock_quantity - :qty 
            WHERE id = :id
        ");

        $stmtUpdateProductStock = $pdo->prepare("
            UPDATE nanook_products 
            SET stock_quantity = stock_quantity - :qty 
            WHERE id = :id
        ");

        foreach ($cart['items'] as $item) {
            $custJson = !empty($item['customizations']) ? json_encode($item['customizations']) : null;
            $isPreorder = !empty($item['is_preorder']) ? 1 : 0;
            $sku = $item['sku'] ?? null;

            $stmtItem->execute([
                ':oid' => $orderId,
                ':pid' => $item['product_id'],
                ':vid' => $item['variant_id'] ?? null,
                ':pname' => $item['name'],
                ':vname' => $item['variant_name'],
                ':sku' => $sku,
                ':uprice' => $item['unit_price'],
                ':qty' => $item['quantity'],
                ':ltotal' => $item['line_total'],
                ':is_preorder' => $isPreorder,
                ':cust_json' => $custJson
            ]);

            $qtyToDeduct = (int)$item['quantity'];

            if (!empty($item['variant_id'])) {
                $stmtUpdateVariantStock->execute([':qty' => $qtyToDeduct, ':id' => $item['variant_id']]);
            } else {
                $stmtUpdateProductStock->execute([':qty' => $qtyToDeduct, ':id' => $item['product_id']]);
            }
        }

        // 3. Log Email
        $stmtLog = $pdo->prepare("INSERT INTO nanook_email_logs (order_id, recipient_email, subject, sent_at) VALUES (?, ?, ?, NOW())");
        $stmtLog->execute([$orderId, $postData['email'], "Confirmation de commande $orderNumber"]);

        $pdo->commit();

        // 4. Envoi Emails (Sécurisé par try/catch)
        try {
            sendOrderEmails($postData, $cart, $orderNumber, $orderId, $deliveryMethod);
        } catch (Exception $mailEx) {
            error_log("Erreur envoi email commande $orderNumber : " . $mailEx->getMessage());
        }

        // 5. Reset Panier
        $cartService->clear();

        // 6. Redirection
        header('Location: /confirmation?order=' . $orderNumber);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erreur Checkout : " . $e->getMessage());
        // Affichage simple pour l'utilisateur
        die("Une erreur technique est survenue lors de la validation. (Erreur: " . htmlspecialchars($e->getMessage()) . ")");
    }
}

function sendOrderEmails($customer, $cart, $orderNumber, $orderId, $deliveryMethod)
{
    $toClient = $customer['email'];
    $toAdmin = 'ornella1984@hotmail.com';

    // --- Construction Variables ---
    $addressHtml = "";
    $adminAddrInfo = "";

    if ($deliveryMethod === 'shipping') {
        $addr1 = $customer['address1'] ?? '';
        $addr2 = $customer['address2'] ?? '';
        $zip = $customer['zip'] ?? '';
        $city = $customer['city'] ?? '';

        $fullAddress = $addr1 . "<br>" . ($addr2 ? $addr2 . "<br>" : "") . $zip . " " . $city;
        $addressHtml = "<p><strong>Adresse de livraison :</strong><br>" . $fullAddress . "</p>";
        $adminAddrInfo = "<strong>Livraison à :</strong><br>" . $fullAddress;
    } else {
        $addressHtml = "<p><strong>Mode de réception :</strong> Remise en mains propres sur rendez-vous (Paris).</p>";
        $adminAddrInfo = "<strong>RETRAIT MAINS PROPRES</strong> (Pas d'adresse d'expédition)";
    }

    // Liste Items Client
    $itemsHtmlClient = "<ul style='padding-left:20px;'>";
    foreach ($cart['items'] as $item) {
        $price = number_format((float)$item['line_total'], 2, ',', ' ');
        $name = htmlspecialchars($item['name']);
        $variant = $item['variant_name'] ? " (" . htmlspecialchars($item['variant_name']) . ")" : "";
        $preorderLabel = (!empty($item['is_preorder'])) ? " <em style='color:#C18C5D'>[Précommande]</em>" : "";
        $itemsHtmlClient .= "<li style='margin-bottom:5px;'><strong>{$name}</strong>{$variant}{$preorderLabel} x{$item['quantity']} - {$price} €</li>";
    }
    $itemsHtmlClient .= "</ul>";

    // Liste Items Admin (Tableau)
    $itemsHtmlAdmin = "<table style='width:100%; border-collapse:collapse; font-size:13px;'>";
    foreach ($cart['items'] as $item) {
        $qty = $item['quantity'];
        $name = htmlspecialchars($item['name']);
        $var = $item['variant_name'] ? htmlspecialchars($item['variant_name']) : "-";
        $status = (!empty($item['is_preorder'])) ? "<span style='color:orange; font-weight:bold;'>PRECO</span>" : "<span style='color:green;'>STOCK</span>";
        $itemsHtmlAdmin .= "
        <tr style='border-bottom:1px solid #eee;'>
            <td style='padding:5px;'>x{$qty}</td>
            <td style='padding:5px;'><strong>{$name}</strong></td>
            <td style='padding:5px;'>{$var}</td>
            <td style='padding:5px;'>{$status}</td>
        </tr>";
    }
    $itemsHtmlAdmin .= "</table>";

    $shippingPref = $customer['shipping_pref'] ?? 'no_preference';
    $deliveryText = ($shippingPref === 'christmas') ? 'Avant Noël 🎄' : 'Début 2026';
    $totalFormatted = number_format((float)$cart['total'], 2, ',', ' ');

    // Lien Admin dynamique
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'nanook.paris';
    $adminLink = $protocol . $host . "/admin/order_detail.php?id=" . $orderId;

    $prefStyle = ($shippingPref === 'christmas') ? "background:#dcfce7; color:#166534;" : "background:#f3f4f6; color:#1f2937;";

    // --- Email Client ---
    $subjectClient = "Confirmation de commande Nanook - $orderNumber";
    $messageClient = "
    <html>
    <body style='font-family:sans-serif; color:#333; line-height:1.6;'>
        <h1 style='color:#1A1A2E; border-bottom:2px solid #C18C5D; padding-bottom:10px;'>Merci pour votre commande !</h1>
        <p>Bonjour " . htmlspecialchars($customer['firstname']) . ",</p>
        <p>Votre commande <strong>$orderNumber</strong> est bien validée. Je vais m'en occuper avec le plus grand soin.</p>
        
        <div style='background:#F9F9F9; padding:20px; margin:20px 0; border-radius:5px;'>
            <h3 style='margin-top:0;'>Récapitulatif</h3>
            $itemsHtmlClient
            <p style='font-size:1.2em; font-weight:bold; margin-top:15px; border-top:1px solid #ddd; padding-top:10px;'>
                Total : {$totalFormatted} €
            </p>
            <p><strong>Préférence temporelle :</strong> $deliveryText</p>
            $addressHtml
        </div>
        
        <p>Vous recevrez bientôt les instructions pour le règlement (Virement, Wero ou Paypal).</p>
        <p>À très vite,<br><strong>Nanook</strong></p>
    </body>
    </html>
    ";

    // --- Email Admin ---
    $subjectAdmin = "[Commande] $orderNumber - {$customer['firstname']} {$customer['lastname']} - $totalFormatted €";
    $messageAdmin = "
    <html>
    <body style='font-family:system-ui, sans-serif; color:#111827; font-size:14px;'>
        <div style='padding:15px; border:1px solid #e5e7eb; border-radius:8px; max-width:600px;'>
            <div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;'>
                <h2 style='margin:0; font-size:18px;'>$orderNumber</h2>
                <div style='font-weight:bold; font-size:16px;'>$totalFormatted €</div>
            </div>

            <div style='margin-bottom:15px; padding:10px; background:#f9fafb; border-radius:6px;'>
                <strong>Client :</strong> " . htmlspecialchars($customer['firstname'] . ' ' . $customer['lastname']) . "<br>
                <strong>Email :</strong> <a href='mailto:{$customer['email']}'>{$customer['email']}</a><br>
                $adminAddrInfo
            </div>

            <div style='margin-bottom:15px;'>
                <span style='padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px; $prefStyle'>
                    DÉLAI : " . mb_strtoupper($deliveryText, "UTF-8") . "
                </span>
            </div>

            <div style='margin-bottom:20px;'>
                $itemsHtmlAdmin
            </div>

            <div style='text-align:center;'>
                <a href='$adminLink' style='display:inline-block; background:#111827; color:#ffffff; text-decoration:none; padding:10px 20px; border-radius:6px; font-weight:bold;'>
                    Gérer la commande
                </a>
            </div>
        </div>
    </body>
    </html>
    ";

    // ENVOI VIA CLASS MAILER (Instance) OU MAIL() NATIF
    if (class_exists('Mailer')) {
        $mailer = new Mailer();
        $mailer->send($toClient, $subjectClient, $messageClient);
        $mailer->send($toAdmin, $subjectAdmin, $messageAdmin);
    } else {
        // Fallback natif
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Nanook Paris <contact@nanook.paris>" . "\r\n";

        @mail($toClient, $subjectClient, $messageClient, $headers);
        @mail($toAdmin, $subjectAdmin, $messageAdmin, $headers);
    }
}