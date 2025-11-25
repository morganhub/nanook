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

    // On récupère le produit UNE SEULE FOIS ici
    $product = getProductBySlug($pdo, $slug); // J'ai renommé $productSEO en $product pour simplifier

    if ($product) {
        // Optimisation SEO
        $pageTitle = htmlspecialchars($product['name']) . ' | Nanook Paris';
        $descRaw = !empty($product['short_description']) ? $product['short_description'] : ($product['long_description'] ?? '');
        $metaDescription = substr(strip_tags($descRaw), 0, 160) . '...';

        if (!empty($product['images'][0]['file_path'])) {
            $ogImage = '/storage/product_images/' . $product['images'][0]['file_path'];
        }

        // JSON-LD... (Garder votre code existant ici)

        // On charge la vue qui utilisera la variable $product déjà remplie !
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