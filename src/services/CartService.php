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
        // On crée une clé unique pour différencier les variantes/customs
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
            return ['items' => [], 'total' => 0.0, 'count' => 0];
        }

        $pdo = getPdo();
        $items = [];
        $total = 0.0;
        $count = 0;

        foreach ($_SESSION['cart'] as $key => $item) {
            // 1. Infos Produit + IMAGE (Join)
            $stmt = $pdo->prepare("
                SELECT p.name, p.price, p.slug, pi.file_path as image
                FROM nanook_products p
                LEFT JOIN nanook_product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.id = :id
            ");
            $stmt->execute([':id' => $item['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) continue;

            $price = (float)$product['price'];
            $variantName = null;

            // 2. Infos Variante
            if ($item['variant_id']) {
                $stmtVar = $pdo->prepare("SELECT name, price FROM nanook_product_variants WHERE id = :id");
                $stmtVar->execute([':id' => $item['variant_id']]);
                $variant = $stmtVar->fetch(PDO::FETCH_ASSOC);
                if ($variant) {
                    $variantName = $variant['name'];
                    if ($variant['price'] !== null) {
                        $price = (float)$variant['price'];
                    }
                }
            }

            $lineTotal = $price * $item['quantity'];
            $total += $lineTotal;
            $count += $item['quantity'];

            $items[] = [
                'key' => $key,
                'product_id' => $item['product_id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'image' => $product['image'], // On passe l'image au JSON
                'variant_id' => $item['variant_id'],
                'variant_name' => $variantName,
                'quantity' => $item['quantity'],
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'customizations' => $item['customizations']
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'count' => $count
        ];
    }
}