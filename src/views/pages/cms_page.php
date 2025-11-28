<?php

if (!isset($cmsPage)) return;
require_once __DIR__ . '/../../services/TextService.php';

$gridCols = 4; 
$charsPerBlock = 150; 


$contentHtml = $cmsPage['content'] ?? '';
$images = $cmsPage['images'] ?? [];
$blocks = []; 

if (!empty($contentHtml)) {
    $dom = new DOMDocument();
    
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><body>' . $contentHtml . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('

    $currentHtml = '';
    $currentLength = 0;
    $imgIndex = 0;

    foreach ($nodes as $node) {
        $nodeHtml = $dom->saveHTML($node);
        $textLen = mb_strlen(strip_tags($nodeHtml));

        $currentHtml .= $nodeHtml;
        $currentLength += $textLen;

        
        
        $tagName = strtolower($node->nodeName);
        $isTitle = in_array($tagName, ['h1','h2','h3','h4','h5','h6']);

        if ($currentLength >= $charsPerBlock && !$isTitle && isset($images[$imgIndex])) {
            
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

    
    if (!empty($currentHtml)) {
        $blocks[] = [
            'html' => $currentHtml,
            'image' => isset($images[$imgIndex]) ? $images[$imgIndex] : null,
            'type' => isset($images[$imgIndex]) ? 'mixed' : 'text_only'
        ];
        if (isset($images[$imgIndex])) $imgIndex++;
    }
}


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
        $sideToggle = 0; 
        foreach ($blocks as $block):
            $block['html'] = autoLinkContact($block['html'], $pdo);
            ?>

            <?php if ($block['type'] === 'mixed' && $block['image']): ?>
            <?php
            $imgSrc = '/storage/page_images/' . htmlspecialchars($block['image']['file_path']);
            
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
