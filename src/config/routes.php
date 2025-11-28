<?php


$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


$pageTitle = 'Nanook Paris | Maroquinerie Artisanale & Objets Uniques';
$metaDescription = 'Découvrez Nanook Paris : des créations artisanales en cuir, fabriquées à la main avec passion. Sacs, accessoires et objets lifestyle.';
$ogImage = '/assets/img/hero-nanook.jpg'; 
$canonicalUrl = 'https://nanook.paris' . $requestUri;
$jsonLd = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => "Nanook Paris",
    "url" => "https://nanook.paris"
];


$viewPath = __DIR__ . '/../views/pages/';
$layoutPath = __DIR__ . '/../views/layouts/base.php';
$pageContent = null; 





if ($requestUri === '/api/stats.php') {
    require_once __DIR__ . '/../services/StatsService.php';
    recordVisit($pdo); 
    exit; 
}


if ($requestUri === '/' || $requestUri === '/index.php') {
    $pageContent = $viewPath . 'home.php';
    require $layoutPath;
    exit;
}


if (preg_match('#^/c/([a-z0-9-]+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $_GET['slug'] = $slug;

    
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

if (preg_match('#^/i/([a-z0-9-]+)$#', $requestUri, $matches)) {
    $slug = $matches[1];


    $stmt = $pdo->prepare("SELECT * FROM nanook_pages WHERE slug = :slug AND is_active = 1");
    $stmt->execute([':slug' => $slug]);
    $cmsPage = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cmsPage) {
        
        $pageTitle = htmlspecialchars($cmsPage['title']) . ' | Nanook Paris';
        $metaDescription = $cmsPage['chapeau'] ? substr(strip_tags($cmsPage['chapeau']), 0, 160) : 'Nanook Paris - Page d\'information';

        
        $stmtImg = $pdo->prepare("SELECT file_path FROM nanook_page_images WHERE page_id = :pid ORDER BY display_order ASC");
        $stmtImg->execute([':pid' => $cmsPage['id']]);
        $cmsPage['images'] = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

        $pageContent = $viewPath . 'cms_page.php';
        require $layoutPath;
        exit;
    }
}


if (preg_match('#^/p/([a-z0-9-]+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $_GET['slug'] = $slug;

    
    $product = getProductBySlug($pdo, $slug); 

    if ($product) {
        
        $pageTitle = htmlspecialchars($product['name']) . ' | Nanook Paris';
        $descRaw = !empty($product['short_description']) ? $product['short_description'] : ($product['long_description'] ?? '');
        $metaDescription = substr(strip_tags($descRaw), 0, 160) . '...';

        if (!empty($product['images'][0]['file_path'])) {
            $ogImage = '/storage/product_images/' . $product['images'][0]['file_path'];
        }

        

        
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
    processCheckout($_POST); 
    exit;
}

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


http_response_code(404);
$pageTitle = 'Page Introuvable';
echo "<h1>Erreur 404</h1><p>Cette page n'existe pas.</p>"; 