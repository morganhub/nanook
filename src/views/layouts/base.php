<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title><?= $pageTitle ?? 'Nanook Paris' ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? '') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl ?? '') ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl ?? '') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? '') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription ?? '') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage ?? '') ?>">
    <meta property="og:site_name" content="Nanook Paris">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle ?? '') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage ?? '') ?>">

    <?php if (isset($jsonLd)): ?>
        <script type="application/ld+json">
        <?= json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
    </script>
    <?php endif; ?>

    <link rel="icon" type="image/png" href="/favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">

    <?php if(isset($ogImage) && $ogImage !== '/assets/img/hero-nanook.jpg'): ?>
        <link rel="preload" as="image" href="<?= $ogImage ?>">
    <?php endif; ?>
</head>
<body>

<?php require __DIR__ . '/../partials/header.php'; ?>

<main id="mainContent">
    <?php require $pageContent; ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<?php require __DIR__ . '/../partials/cart-drawer.php'; ?>

<script src="/assets/js/app.js" defer></script>

</body>
</html>