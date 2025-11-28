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
