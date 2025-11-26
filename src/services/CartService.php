<?php
// src/services/CartService.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class CartService
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    }

    public function add(int $productId, ?int $variantId, int $quantity, array $customizations = []): void
    {
        $key = md5((string)$productId . (string)$variantId . json_encode($customizations));

        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'customizations' => $customizations
            ];
        }
    }

    public function remove(string $key): void
    {
        unset($_SESSION['cart'][$key]);
    }

    public function clear(): void
    {
        $_SESSION['cart'] = [];
    }

    public function getCartDetails(): array
    {
        if (empty($_SESSION['cart'])) {
            return ['items' => [], 'total' => 0.0, 'count' => 0, 'has_preorder' => false];
        }

        $pdo = getPdo();
        $items = [];
        $total = 0.0;
        $count = 0;
        $globalHasPreorder = false;

        foreach ($_SESSION['cart'] as $key => $item) {
            // 1. Infos Produit
            $stmt = $pdo->prepare("
                SELECT p.name, p.price, p.slug, p.stock_quantity, p.allow_preorder_when_oos, p.availability_date,
                       pi.file_path as image
                FROM nanook_products p
                LEFT JOIN nanook_product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.id = :id
            ");
            $stmt->execute([':id' => $item['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) continue;

            $price = (float)$product['price'];
            $variantName = null;

            // Stock disponible physique
            $availableStock = (int)$product['stock_quantity'];
            $availabilityDate = $product['availability_date'];

            // 2. Infos Variante
            if ($item['variant_id']) {
                $stmtVar = $pdo->prepare("
                    SELECT name, price, stock_quantity, allow_preorder_when_oos, availability_date 
                    FROM nanook_product_variants 
                    WHERE id = :id
                ");
                $stmtVar->execute([':id' => $item['variant_id']]);
                $variant = $stmtVar->fetch(PDO::FETCH_ASSOC);
                if ($variant) {
                    $variantName = $variant['name'];
                    if ($variant['price'] !== null) $price = (float)$variant['price'];

                    // Surcharge avec les données variante
                    $availableStock = (int)$variant['stock_quantity'];
                    $availabilityDate = $variant['availability_date'];
                }
            }

            // --- CALCUL PRÉCOMMANDE ---
            $qtyRequested = (int)$item['quantity'];
            $preorderCount = 0; // Nombre d'items de cette ligne qui sont en précommande

            if ($availableStock <= 0) {
                // Cas 1: Tout est en précommande
                $preorderCount = $qtyRequested;
            } elseif ($qtyRequested > $availableStock) {
                // Cas 2: Mixte (ex: veut 5, reste 2 en stock -> 3 précos)
                $preorderCount = $qtyRequested - $availableStock;
            }

            // Flag global
            if ($preorderCount > 0) {
                $globalHasPreorder = true;
            }

            // Un item est "considéré précommande" pour le tri visuel s'il y en a au moins 1 en préco
            $isPreorderItem = ($preorderCount > 0);

            $lineTotal = $price * $qtyRequested;
            $total += $lineTotal;
            $count += $qtyRequested;

            $items[] = [
                'key' => $key,
                'product_id' => $item['product_id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'image' => $product['image'],
                'variant_id' => $item['variant_id'],
                'variant_name' => $variantName,
                'quantity' => $qtyRequested,
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'customizations' => $item['customizations'],

                // Métadonnées Précommande
                'is_preorder' => $isPreorderItem,
                'preorder_count' => $preorderCount, // Nouvelle info précise
                'available_stock' => $availableStock,
                'availability_date' => $availabilityDate
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'count' => $count,
            'has_preorder' => $globalHasPreorder
        ];
    }
}