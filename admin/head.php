<?php
// public/admin/head.php
declare(strict_types=1);

if (!isset($pageTitle) || $pageTitle === '') {
    $pageTitle = 'Nanook · Admin';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>