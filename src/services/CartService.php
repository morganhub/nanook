<?php
// src/services/CartService.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class CartService
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function add(int $productId, ?int $variantId, int $quantity, array $customizations = []): void
    {
        // Clé unique pour regrouper les produits identiques dans le panier
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
            $productId = (int)$item['product_id'];
            $variantId = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
            $qtyRequested = (int)$item['quantity'];

            // 1. Récupération Produit Parent
            $stmt = $pdo->prepare("
                SELECT id, name, price, slug, stock_quantity, allow_preorder_when_oos, availability_date
                FROM nanook_products 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si le produit n'existe plus, on le retire du panier
            if (!$product) {
                $this->remove($key);
                continue;
            }

            // Valeurs par défaut (Parent)
            $finalPrice = (float)$product['price'];
            $baseName = $product['name'];
            $variantName = null;
            $availableStock = (int)$product['stock_quantity'];
            $availabilityDate = $product['availability_date'];
            $imagePath = null;

            // 2. Récupération Variante (Nouveau Système)
            if ($variantId) {
                $stmtVar = $pdo->prepare("
                    SELECT id, price, stock_quantity, allow_preorder_when_oos, availability_date
                    FROM nanook_product_variants 
                    WHERE id = :id AND product_id = :pid
                ");
                $stmtVar->execute([':id' => $variantId, ':pid' => $productId]);
                $variant = $stmtVar->fetch(PDO::FETCH_ASSOC);

                if ($variant) {
                    // A. Logique Prix : Variante prioritaire SI > 0
                    $vPrice = (float)$variant['price'];
                    if ($vPrice > 0) {
                        $finalPrice = $vPrice;
                    }

                    // B. Stock & Dispo
                    $availableStock = (int)$variant['stock_quantity'];
                    $availabilityDate = $variant['availability_date'];

                    // C. Construction du Nom Variante (ex: "Grand - Rouge")
                    // On joint la table de pivot -> options -> attributs pour avoir l'ordre correct
                    $stmtName = $pdo->prepare("
                        SELECT o.name 
                        FROM nanook_product_variant_combinations pvc
                        JOIN nanook_attribute_options o ON pvc.option_id = o.id
                        JOIN nanook_attributes a ON o.attribute_id = a.id
                        WHERE pvc.variant_id = :vid
                        ORDER BY a.display_order ASC
                    ");
                    $stmtName->execute([':vid' => $variantId]);
                    $optionsNames = $stmtName->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($optionsNames)) {
                        $variantName = implode(' - ', $optionsNames);
                    } else {
                        $variantName = "Défaut"; // Fallback si variante sans options
                    }

                    // D. Image Spécifique Variante
                    $stmtImgV = $pdo->prepare("SELECT file_path FROM nanook_product_images WHERE variant_id = :vid ORDER BY display_order ASC LIMIT 1");
                    $stmtImgV->execute([':vid' => $variantId]);
                    $imgVar = $stmtImgV->fetch();
                    if ($imgVar) {
                        $imagePath = $imgVar['file_path'];
                    }
                }
            }

            // Fallback Image : Si pas d'image variante, on prend la principale du produit
            if (!$imagePath) {
                $stmtImgP = $pdo->prepare("SELECT file_path FROM nanook_product_images WHERE product_id = :pid AND is_main = 1 LIMIT 1");
                $stmtImgP->execute([':pid' => $productId]);
                $imgProd = $stmtImgP->fetch();
                if ($imgProd) {
                    $imagePath = $imgProd['file_path'];
                }
            }

            // --- CALCUL PRÉCOMMANDE ---
            $preorderCount = 0;
            if ($availableStock <= 0) {
                $preorderCount = $qtyRequested;
            } elseif ($qtyRequested > $availableStock) {
                $preorderCount = $qtyRequested - $availableStock;
            }

            if ($preorderCount > 0) {
                $globalHasPreorder = true;
            }

            // Calcul Totaux Ligne
            $lineTotal = $finalPrice * $qtyRequested;
            $total += $lineTotal;
            $count += $qtyRequested;

            $items[] = [
                'key' => $key,
                'product_id' => $productId,
                'name' => $baseName,
                'slug' => $product['slug'],
                'image' => $imagePath,
                'variant_id' => $variantId,
                'variant_name' => $variantName, // Maintenant correctement rempli !
                'quantity' => $qtyRequested,
                'unit_price' => $finalPrice,
                'line_total' => $lineTotal,
                'customizations' => $item['customizations'],

                // Métadonnées
                'is_preorder' => ($preorderCount > 0),
                'preorder_count' => $preorderCount,
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