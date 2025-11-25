<?php
// public/api/cart.php
declare(strict_types=1);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/services/CartService.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$cartService = new CartService();

try {
    if ($action === 'add') {
        $pid = (int)($input['product_id'] ?? 0);
        $vid = !empty($input['variant_id']) ? (int)$input['variant_id'] : null;
        $qty = (int)($input['quantity'] ?? 1);
        $cust = $input['customization'] ?? [];

        if ($pid > 0 && $qty > 0) {
            $cartService->add($pid, $vid, $qty, $cust);
        }
    }
    elseif ($action === 'remove') {
        $key = $input['key'] ?? '';
        if ($key) $cartService->remove($key);
    }
    elseif ($action === 'get') {
        // Juste récupérer l'état actuel
    }

    // On renvoie toujours l'état complet du panier pour mettre à jour le DOM
    echo json_encode([
        'success' => true,
        'cart' => $cartService->getCartDetails()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}