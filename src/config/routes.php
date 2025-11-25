<?php
// src/config/routes.php

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- Valeurs SEO par défaut (Homepage / Fallback) ---
$pageTitle = 'Nanook Paris | Maroquinerie Artisanale & Objets Uniques';
$metaDescription = 'Découvrez Nanook Paris : des créations artisanales en cuir, fabriquées à la main avec passion. Sacs, accessoires et objets lifestyle.';
$ogImage = '/assets/img/hero-nanook.jpg'; // Image par défaut pour le partage
$canonicalUrl = 'https://nanook.paris' . $requestUri; // Adapter avec le vrai domaine
$pageContent = null; // Sera défini par les routes
$jsonLd = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => "Nanook Paris",
    "url" => "https://nanook.paris"
];

// Chemins
$viewPath = __DIR__ . '/../views/pages/';
$layoutPath = __DIR__ . '/../views/layouts/base.php';

// --- ROUTAGE ---

// 1. Homepage
if ($requestUri === '/' || $requestUri === '/index.php') {
    $pageContent = $viewPath . 'home.php';
}

// 2. Page Catégorie (Ex: /c/sacs)
elseif (preg_match('#^/c/([a-z0-9-]+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $_GET['slug'] = $slug;

    // Récupération infos catégorie pour SEO (SQL rapide ou Service)
    $stmt = $pdo->prepare("SELECT name FROM nanook_categories WHERE slug = :slug");
    $stmt->execute([':slug' => $slug]);
    $cat = $stmt->fetch();

    if ($cat) {
        $pageTitle = htmlspecialchars($cat['name']) . ' | Collection Nanook';
        $metaDescription = 'Explorez notre collection de ' . htmlspecialchars($cat['name']) . '. Fait main à Paris.';
        $pageContent = $viewPath . 'category.php';
    } else {
        // Catégorie inconnue => 404 gérée plus bas
        $pageContent = null;
    }
}

// 3. Fiche Produit (Ex: /p/vide-poche-cuir)
elseif (preg_match('#^/p/([a-z0-9-]+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $_GET['slug'] = $slug;

    // On utilise le service existant pour récupérer les infos SEO
    $productSEO = getProductBySlug($pdo, $slug);

    if ($productSEO) {
        $pageTitle = htmlspecialchars($productSEO['name']) . ' | Nanook Paris';
        // Description courte ou tronquée de la longue
        $descRaw = !empty($productSEO['short_description']) ? $productSEO['short_description'] : $productSEO['long_description'];
        $metaDescription = substr(strip_tags($descRaw), 0, 160) . '...';

        // Image principale pour Facebook/Twitter/WhatsApp
        if (!empty($productSEO['images'][0]['file_path'])) {
            $ogImage = '/storage/product_images/' . $productSEO['images'][0]['file_path'];
        }

        // Schema.org Produit (Crucial pour Google Shopping / Rich Snippets)
        $jsonLd = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $productSEO['name'],
            "image" => "https://nanook.paris" . $ogImage, // URL absolue requise
            "description" => $metaDescription,
            "brand" => [
                "@type" => "Brand",
                "name" => "Nanook"
            ],
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
    } else {
        $pageContent = null; // 404
    }
}

// 4. Panier & Checkout
elseif ($requestUri === '/panier') {
    $pageTitle = 'Votre Panier | Nanook';
    $pageContent = $viewPath . 'cart.php';
}
elseif ($requestUri === '/confirmation') {
    $pageTitle = 'Merci pour votre commande | Nanook';
    $pageContent = $viewPath . 'success.php';
}

// --- RENDU FINAL ---

if ($pageContent && file_exists($pageContent)) {
    require $layoutPath; // On charge le Layout global qui utilisera $pageTitle, $jsonLd...
} else {
    http_response_code(404);
    $pageTitle = 'Page Introuvable';
    // Créer un views/pages/404.php serait idéal
    echo "<div style='text-align:center; padding:100px; font-family:sans-serif;'>Erreur 404 : Cette page n'existe pas. <br><a href='/'>Retour à l'accueil</a></div>";
}