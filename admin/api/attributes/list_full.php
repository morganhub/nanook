<?php
// public/admin/api/attributes/list_full.php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'method_not_allowed'], 405);
}

$pdo = getPdo();
$admin = requireAdmin($pdo);

try {
    // 1. Récupérer tous les attributs (ex: Format, Couleur)
    $stmtAttr = $pdo->query('SELECT * FROM nanook_attributes ORDER BY display_order ASC, id ASC');
    $attributes = $stmtAttr->fetchAll();

    // 2. Récupérer toutes les options (ex: Grand, Petit, Rouge, Bleu)
    $stmtOpt = $pdo->query('SELECT * FROM nanook_attribute_options ORDER BY display_order ASC, id ASC');
    $allOptions = $stmtOpt->fetchAll();

    // 3. Organiser les options par ID d'attribut pour l'envoyer proprement au JS
    $optionsByAttr = [];
    foreach ($allOptions as $opt) {
        $attrId = (int)$opt['attribute_id'];
        if (!isset($optionsByAttr[$attrId])) {
            $optionsByAttr[$attrId] = [];
        }

        $optionsByAttr[$attrId][] = [
            'id' => (int)$opt['id'],
            'name' => $opt['name'],
            'value' => $opt['value'], // Hexa ou autre
            'display_order' => (int)$opt['display_order']
        ];
    }

    // 4. Assemblage final
    $data = [];
    foreach ($attributes as $attr) {
        $id = (int)$attr['id'];
        $data[] = [
            'id' => $id,
            'name' => $attr['name'],        // Nom interne (Admin)
            'public_name' => $attr['public_name'], // Nom public (Front)
            'type' => $attr['type'],
            'display_order' => (int)$attr['display_order'],
            'options' => $optionsByAttr[$id] ?? [] // Tableau des options ou vide
        ];
    }

    jsonResponse(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    jsonResponse(['error' => 'db_error', 'message' => $e->getMessage()], 500);
}