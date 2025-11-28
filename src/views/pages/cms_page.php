<?php
// src/views/pages/cms_page.php
if (!isset($cmsPage)) return;

// --- CONFIGURATION ---
$gridCols = 4; // Nombre de colonnes de la grille
$charsPerBlock = 150; // Nombre approx de caractères avant de couper un bloc pour insérer une image

// --- 1. PARSING DU CONTENU ---
$contentHtml = $cmsPage['content'] ?? '';
$images = $cmsPage['images'] ?? [];
$blocks = []; // Tableau qui contiendra [ 'html' => string, 'has_image' => bool, 'image' => array|null ]

if (!empty($contentHtml)) {
    $dom = new DOMDocument();
    // Hack UTF-8
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><body>' . $contentHtml . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//body/*');

    $currentHtml = '';
    $currentLength = 0;
    $imgIndex = 0;

    foreach ($nodes as $node) {
        $nodeHtml = $dom->saveHTML($node);
        $textLen = mb_strlen(strip_tags($nodeHtml));

        $currentHtml .= $nodeHtml;
        $currentLength += $textLen;

        // Condition de coupure : on a assez de texte ET ce n'est pas un titre (pour ne pas couper juste après un titre)
        // ET il nous reste des images à placer
        $tagName = strtolower($node->nodeName);
        $isTitle = in_array($tagName, ['h1','h2','h3','h4','h5','h6']);

        if ($currentLength >= $charsPerBlock && !$isTitle && isset($images[$imgIndex])) {
            // On valide ce bloc avec une image
            $blocks[] = [
                'html' => $currentHtml,
                'image' => $images[$imgIndex],
                'type' => 'mixed'
            ];
            $currentHtml = '';
            $currentLength = 0;
            $imgIndex++;
        }
    }

    // Reste du contenu (sans image associée si on a tout utilisé)
    if (!empty($currentHtml)) {
        $blocks[] = [
            'html' => $currentHtml,
            'image' => isset($images[$imgIndex]) ? $images[$imgIndex] : null,
            'type' => isset($images[$imgIndex]) ? 'mixed' : 'text_only'
        ];
        if (isset($images[$imgIndex])) $imgIndex++;
    }
}

// Images restantes (si le texte est trop court pour toutes les afficher)
$remainingImages = [];
for ($i = $imgIndex; $i < count($images); $i++) {
    $remainingImages[] = $images[$i];
}
?>

<div class="nk-container" style="padding-top: 60px; padding-bottom: 80px; max-width: 1300px;">

    <div class="cms-header">
        <h1 class="nk-title-lg"><?= htmlspecialchars($cmsPage['title']) ?></h1>
        <?php if (!empty($cmsPage['chapeau'])): ?>
            <div class="cms-chapeau">
                <?= nl2br(htmlspecialchars($cmsPage['chapeau'])) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="cms-layout">
        <?php
        $sideToggle = 0; // 0 = Image Gauche, 1 = Image Droite
        foreach ($blocks as $block):
            ?>

            <?php if ($block['type'] === 'mixed' && $block['image']): ?>
            <?php
            $imgSrc = '/storage/page_images/' . htmlspecialchars($block['image']['file_path']);
            // Alternance
            $rowClass = ($sideToggle % 2 === 0) ? 'row-img-left' : 'row-img-right';
            $sideToggle++;
            ?>
            <div class="cms-row <?= $rowClass ?>">
                <div class="cms-cell-image">
                    <img src="<?= $imgSrc ?>" alt="" loading="lazy">
                </div>
                <div class="cms-cell-text nk-text-body">
                    <?= $block['html'] ?>
                </div>
            </div>

        <?php else: ?>
            <div class="cms-row row-text-only">
                <div class="cms-cell-text nk-text-body">
                    <?= $block['html'] ?>
                </div>
            </div>
        <?php endif; ?>

        <?php endforeach; ?>
    </div>

    <?php if (!empty($remainingImages)): ?>
        <div class="nk-grid cms-gallery-fallback">
            <?php foreach ($remainingImages as $img): ?>
                <div class="nk-span-6">
                    <img src="/storage/page_images/<?= htmlspecialchars($img['file_path']) ?>"
                         alt="" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<style>
    /* --- STYLES GÉNÉRAUX --- */
    .cms-header {
        text-align: center;
        margin-bottom: 80px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    .cms-chapeau {
        font-size: 1.3rem;
        line-height: 1.6;
        color: var(--nk-accent);
        font-style: italic;
        font-family: var(--nk-font-serif);
        margin-top: 20px;
    }

    /* --- LAYOUT GRID MAGAZINE --- */
    .cms-layout {
        font-family: var(--nk-font-serif);
        display: flex;
        flex-direction: column;
        gap: 60px; /* Espace vertical entre les sections */
    }
    .cms-layout p {
        font-size: 3vw;
        line-height: 5vw;
        text-align: center;
    }
    .cms-row {
        display: grid;
        /* 4 Colonnes égales pour faciliter le placement 1/2 et 3/4 */
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 40px;
        align-items: center; /* Centrage vertical texte/image */
    }

    /* Styles Images */
    .cms-cell-image img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 2px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    /* Styles Texte */
    .cms-cell-text {
        font-size: 1.05rem;
        line-height: 1.9;
        color: #222;
    }
    .cms-cell-text h2 {
        font-family: var(--nk-font-serif); font-size: 2rem; margin-top: 0; margin-bottom: 20px;
    }
    .cms-cell-text h3 {
        font-family: var(--nk-font-serif); font-size: 1.5rem; margin-top: 20px; margin-bottom: 10px;
    }
    .cms-cell-text p { margin-bottom: 20px; }
    .cms-cell-text ul { margin-bottom: 20px; padding-left: 20px; }
    .cms-cell-text a { text-decoration: underline; color: var(--nk-text-main); }

    /* --- VARIANTES DE RANGÉES --- */

    /* 1. Image Gauche (Cols 1-2) / Texte Droite (Cols 3-4) */
    .row-img-left .cms-cell-image {
        grid-column: 1 / 3; /* Prend 50% gauche */
    }
    .row-img-left .cms-cell-text {
        grid-column: 3 / 5; /* Prend 50% droite */
        padding-left: 20px; /* Un peu d'air */
    }

    /* 2. Image Droite (Cols 3-4) / Texte Gauche (Cols 1-2) */
    .row-img-right .cms-cell-image {
        grid-column: 3 / 5; /* Prend 50% droite */
    }
    .row-img-right .cms-cell-text {
        grid-column: 1 / 3; /* Prend 50% gauche */
        padding-right: 20px; /* Un peu d'air */
        grid-row: 1; /* Force le texte à rester sur la même ligne que l'image */
    }

    /* 3. Texte Seul (Centré sur Cols 2-3 pour un confort de lecture) */
    .row-text-only .cms-cell-text {
        grid-column: 2 / 4; /* Colonnes centrales */
        text-align: left; /* Ou justify */
    }
    /* Optionnel : si le texte est très long sans image, on peut l'élargir un peu */
    /* .row-text-only .cms-cell-text { grid-column: 1 / 5; max-width: 800px; margin: 0 auto; } */


    /* --- RESPONSIVE (Tablette & Mobile) --- */
    @media (max-width: 991px) {
        .nk-container { padding-left: 20px; padding-right: 20px; }

        .cms-row {
            display: block; /* On casse la grille */
            margin-bottom: 60px;
        }

        .cms-cell-image {
            margin-bottom: 30px;
        }

        /* Effet "Full Bleed" sur mobile (image touche les bords) */
        .cms-cell-image img {
            width: calc(100% + 40px);
            margin-left: -20px;
            max-width: none;
            border-radius: 0;
        }

        .row-img-left .cms-cell-text,
        .row-img-right .cms-cell-text {
            padding: 0;
        }
    }

    .cms-gallery-fallback {
        margin-top: 80px;
        padding-top: 40px;
        border-top: 1px solid #eee;
    }
    .row-img-left .cms-cell-image img {
        -webkit-mask-image: linear-gradient(to right, black 85%, transparent 100%);
        mask-image: linear-gradient(to right, black 85%, transparent 100%);
    }

    /* Image à DROITE (row-img-right) : fondu vers la gauche */
    .row-img-right .cms-cell-image img {
        -webkit-mask-image: linear-gradient(to left, black 85%, transparent 100%);
        mask-image: linear-gradient(to left, black 85%, transparent 100%);
    }

    /* --- RESPONSIVE MOBILE --- */
    @media (max-width: 991px) {
        /* On désactive les masques sur mobile car les images sont en pleine largeur */
        .row-img-left .cms-cell-image img,
        .row-img-right .cms-cell-image img {
            -webkit-mask-image: none;
            mask-image: none;
        }
    }
</style>