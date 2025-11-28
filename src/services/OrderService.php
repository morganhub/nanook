<?php
// src/services/OrderService.php
declare(strict_types=1);

require_once __DIR__ . '/CartService.php';
require_once __DIR__ . '/../config/database.php';
if (file_exists(__DIR__ . '/../Mailer.php')) {
    require_once __DIR__ . '/../Mailer.php';
}

function processCheckout(array $postData)
{
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

        // --- Données Livraison ---
        $deliveryMethod = $postData['delivery_method'] ?? 'shipping';
        $addr1 = ($deliveryMethod === 'pickup') ? '' : ($postData['address1'] ?? '');
        $addr2 = ($deliveryMethod === 'pickup') ? '' : ($postData['address2'] ?? '');
        $zip   = ($deliveryMethod === 'pickup') ? '' : ($postData['zip'] ?? '');
        $city  = ($deliveryMethod === 'pickup') ? '' : ($postData['city'] ?? '');
        $pref  = $postData['shipping_pref'] ?? 'no_preference';

        // 1. Création Commande
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

        // 2. Création Lignes & Mouvement Stock
        $stmtItem = $pdo->prepare("
            INSERT INTO nanook_order_items 
            (order_id, product_id, variant_id, product_name, variant_name, variant_sku, unit_price, quantity, line_total, is_preorder, customizations_json)
            VALUES 
            (:oid, :pid, :vid, :pname, :vname, :sku, :uprice, :qty, :ltotal, :is_preorder, :cust_json)
        ");

        // Préparation requêtes Stock
        $stmtStockVar = $pdo->prepare("UPDATE nanook_product_variants SET stock_quantity = stock_quantity - :qty WHERE id = :id");
        $stmtStockProd = $pdo->prepare("UPDATE nanook_products SET stock_quantity = stock_quantity - :qty WHERE id = :id");

        // Préparation récupération SKU frais
        $stmtGetSku = $pdo->prepare("SELECT sku FROM nanook_product_variants WHERE id = ?");

        foreach ($cart['items'] as $item) {
            $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
            $qty = (int)$item['quantity'];

            // Récupération SKU à la source si variante
            $realSku = null;
            if ($variantId) {
                $stmtGetSku->execute([$variantId]);
                $realSku = $stmtGetSku->fetchColumn();
            }
            // Si toujours pas de SKU, on regarde si c'était dans le panier (fallback)
            if (!$realSku && isset($item['sku'])) {
                $realSku = $item['sku'];
            }

            $custJson = !empty($item['customizations']) ? json_encode($item['customizations']) : null;
            $isPreorder = !empty($item['is_preorder']) ? 1 : 0;

            $stmtItem->execute([
                ':oid' => $orderId,
                ':pid' => $item['product_id'],
                ':vid' => $variantId,
                ':pname' => $item['name'],
                ':vname' => $item['variant_name'],
                ':sku' => $realSku,
                ':uprice' => $item['unit_price'],
                ':qty' => $qty,
                ':ltotal' => $item['line_total'],
                ':is_preorder' => $isPreorder,
                ':cust_json' => $custJson
            ]);

            // Décrémentation Stock
            if ($variantId) {
                $stmtStockVar->execute([':qty' => $qty, ':id' => $variantId]);
            } else {
                $stmtStockProd->execute([':qty' => $qty, ':id' => $item['product_id']]);
            }
        }

        // 3. Log Email Admin (Interne)
        $pdo->prepare("INSERT INTO nanook_email_logs (order_id, recipient_email, subject, sent_at) VALUES (?, ?, ?, NOW())")
            ->execute([$orderId, $postData['email'], "Confirmation de commande $orderNumber"]);

        $pdo->commit();

        // 4. Envoi Emails (Client + Admin)
        try {
            sendOrderEmails($postData, $cart, $orderNumber, $orderId, $deliveryMethod);
        } catch (Exception $e) {
            error_log("Mail error: " . $e->getMessage());
        }

        $cartService->clear();
        header('Location: /confirmation?order=' . $orderNumber);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Checkout Error: " . $e->getMessage());
        die("Une erreur est survenue lors de la validation. (Ref: " . date('His') . ")");
    }
}

function sendOrderEmails($customer, $cart, $orderNumber, $orderId, $deliveryMethod)
{
    $toClient = $customer['email'];
    $toAdmin = 'ornella1984@hotmail.com';

    // Templates HTML
    $addressHtml = "";
    $adminAddrInfo = "";

    if ($deliveryMethod === 'shipping') {
        $fullAddress = htmlspecialchars($customer['address1'] . ' ' . $customer['address2'] . ' ' . $customer['zip'] . ' ' . $customer['city']);
        $addressHtml = "<p><strong>Adresse de livraison :</strong><br>" . $fullAddress . "</p>";
        $adminAddrInfo = "<strong>Livraison à :</strong><br>" . $fullAddress;
    } else {
        $addressHtml = "<p><strong>Mode de réception :</strong> Remise en mains propres sur rendez-vous (Paris).</p>";
        $adminAddrInfo = "<strong>RETRAIT MAINS PROPRES</strong>";
    }

    // Liste Client
    $itemsClient = "<ul style='padding-left:20px;'>";
    foreach ($cart['items'] as $item) {
        $varTxt = $item['variant_name'] ? " (" . htmlspecialchars($item['variant_name']) . ")" : "";
        $preTxt = (!empty($item['is_preorder'])) ? " <em style='color:#C18C5D'>[Précommande]</em>" : "";
        $itemsClient .= "<li>{$item['quantity']}x <strong>" . htmlspecialchars($item['name']) . "</strong>{$varTxt}{$preTxt} - " . number_format((float)$item['line_total'], 2) . " €</li>";
    }
    $itemsClient .= "</ul>";

    // Liste Admin
    $itemsAdmin = "<table style='width:100%; border-collapse:collapse;'>";
    foreach ($cart['items'] as $item) {
        $status = (!empty($item['is_preorder'])) ? "<b style='color:orange'>PRECO</b>" : "<b style='color:green'>STOCK</b>";
        $itemsAdmin .= "<tr><td style='padding:5px; border-bottom:1px solid #eee;'>{$item['quantity']}x</td><td style='padding:5px; border-bottom:1px solid #eee;'>" . htmlspecialchars($item['name']) . " " . htmlspecialchars($item['variant_name'] ?? '') . "</td><td style='padding:5px; border-bottom:1px solid #eee;'>{$status}</td></tr>";
    }
    $itemsAdmin .= "</table>";

    // Sujets & Corps
    $subjectClient = "Confirmation de commande Nanook - $orderNumber";
    $bodyClient = "<html><body style='font-family:sans-serif; color:#333;'>
        <h1 style='color:#1A1A2E; border-bottom:2px solid #C18C5D;'>Merci !</h1>
        <p>Bonjour " . htmlspecialchars($customer['firstname']) . ",</p>
        <p>Votre commande <strong>$orderNumber</strong> est validée.</p>
        <div style='background:#f9f9f9; padding:15px; margin:15px 0;'>$itemsClient</div>
        <p>Total : <strong>" . number_format((float)$cart['total'], 2) . " €</strong></p>
        $addressHtml
        <p>À très vite,<br>Nanook</p>
    </body></html>";

    $subjectAdmin = "[Nouvelle Commande] $orderNumber - " . number_format((float)$cart['total'], 2) . " €";
    $bodyAdmin = "<html><body style='font-family:sans-serif;'>
        <h2>$orderNumber</h2>
        <p>Client : " . htmlspecialchars($customer['firstname'] . ' ' . $customer['lastname']) . "</p>
        $adminAddrInfo
        <h3>Panier</h3>
        $itemsAdmin
    </body></html>";

    if (class_exists('Mailer')) {
        $mailer = new Mailer();
        $mailer->send($toClient, $subjectClient, $bodyClient);
        $mailer->send($toAdmin, $subjectAdmin, $bodyAdmin);
    } else {
        // Fallback
        $headers = "Content-type:text/html;charset=UTF-8\r\nFrom: Nanook Paris <contact@nanook.paris>";
        @mail($toClient, $subjectClient, $bodyClient, $headers);
        @mail($toAdmin, $subjectAdmin, $bodyAdmin, $headers);
    }
}