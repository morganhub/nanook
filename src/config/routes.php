<?php
// src/config/routes.php

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- 1. Variables SEO par défaut (Homepage) ---
$pageTitle = 'Nanook Paris | Maroquinerie Artisanale & Objets Uniques';
$metaDescription = 'Découvrez Nanook Paris : des créations artisanales en cuir, fabriquées à la main avec passion. Sacs, accessoires et objets lifestyle.';
$ogImage = '/assets/img/hero-nanook.jpg'; // Image par défaut pour le partage Facebook/WhatsApp
$canonicalUrl = 'https://nanook.paris' . $requestUri;
$jsonLd = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => "Nanook Paris",
    "url" => "https://nanook.paris"
];

// Chemins vers les vues
$viewPath = __DIR__ . '/../views/pages/';
$layoutPath = __DIR__ . '/../views/layouts/base.php';
$pageContent = null; // Sera défini ci-dessous

// --- 2. Routage & Logique ---

// Homepage
if ($requestUri === '/' || $requestUri === '/index.php') {
    $pageContent = $viewPath . 'home.php';
    require $layoutPath;
    exit;
}

// Page Catégorie (ex: /c/sacs)
if (preg_match('#^/c/([a-z0-9-]+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $_GET['slug'] = $slug;

    // Récupération rapide du nom de la catégorie pour le SEO
    $stmt = $pdo->prepare("SELECT name FROM nanook_categories WHERE slug = :slug");
    $stmt->execute([':slug' => $slug]);
    $cat = $stmt->fetch();

    if ($cat) {
        $pageTitle = htmlspecialchars($cat['name']) . ' | Collection Nanook';
        $metaDescription = 'Explorez notre collection de ' . htmlspecialchars($cat['name']) . '. Pièces uniques faites main à Paris.';
        $pageContent = $viewPath . 'category.php';
        require $layoutPath;
        exit;
    }
}

// Fiche Produit (ex: /p/vide-poche-cuir)
if (preg_match('#^/p/([a-z0-9-]+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $_GET['slug'] = $slug;

    // On utilise le service pour avoir toutes les infos (images, prix, stock)
    $productSEO = getProductBySlug($pdo, $slug);

    if ($productSEO) {
        // Optimisation SEO
        $pageTitle = htmlspecialchars($productSEO['name']) . ' | Nanook Paris';
        // Création d'une description propre sans HTML
        $descRaw = !empty($productSEO['short_description']) ? $productSEO['short_description'] : ($productSEO['long_description'] ?? '');
        $metaDescription = substr(strip_tags($descRaw), 0, 160) . '...';

        // Image pour les réseaux sociaux
        if (!empty($productSEO['images'][0]['file_path'])) {
            $ogImage = '/storage/product_images/' . $productSEO['images'][0]['file_path'];
        }

        // Schema.org Product (Indispensable pour Google Shopping)
        $jsonLd = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $productSEO['name'],
            "image" => "https://nanook.paris" . $ogImage,
            "description" => $metaDescription,
            "brand" => ["@type" => "Brand", "name" => "Nanook"],
            "offers" => [
                "@type" => "Offer",
                "priceCurrency" => "EUR",
                "price" => $productSEO['price_cents'] / 100,
                "availability" => ($productSEO['stock_quantity'] > 0 || $productSEO['allow_preorder_when_oos'])
                    ? "https://schema.org/InStock"
                    : "https://schema.org/OutOfStock"
            ]
        ];

        $pageContent = $viewPath . 'product.php';
        require $layoutPath;
        exit;
    }
}
if ($requestUri === '/checkout') {
    $pageTitle = 'Validation de commande | Nanook';
    $pageContent = $viewPath . 'checkout.php';
    require $layoutPath;
    exit;
}

if ($requestUri === '/checkout/process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../services/OrderService.php';
    processCheckout($_POST); // Gère la logique et redirige
    exit;
}
// Pages Statiques (Panier, Confirmation)
if ($requestUri === '/panier') {
    $pageTitle = 'Votre Panier | Nanook';
    $pageContent = $viewPath . 'cart.php';
    require $layoutPath;
    exit;
}
if ($requestUri === '/confirmation') {
    $pageTitle = 'Merci pour votre commande | Nanook';
    $pageContent = $viewPath . 'success.php';
    require $layoutPath;
    exit;
}

// 404 - Not Found
http_response_code(404);
$pageTitle = 'Page Introuvable';
echo "<h1>Erreur 404</h1><p>Cette page n'existe pas.</p>"; // À remplacer par une vraie vue 404