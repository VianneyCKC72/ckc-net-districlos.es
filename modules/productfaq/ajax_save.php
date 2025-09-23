<?php
// Script AJAX pour sauvegarder les FAQ
require_once('../../config/config.inc.php');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'error: Méthode non autorisée';
    exit;
}

// Vérifier les paramètres
$action = Tools::getValue('action');
$id_product = (int)Tools::getValue('id_product');
$faq_id = (int)Tools::getValue('faq_id');

// Vérifier le token CSRF (optionnel mais recommandé)
if (!Tools::getToken(false)) {
    echo 'error: Token de sécurité manquant';
    exit;
}

if (!$id_product) {
    echo 'error: ID produit manquant';
    exit;
}

// Gérer la suppression
if ($action === 'delete_faq') {
    if ($faq_id <= 0) {
        echo 'error: ID FAQ manquant';
        exit;
    }
    
    // Supprimer la FAQ de la base
    $result = Db::getInstance()->delete('product_faq', 'id = ' . (int)$faq_id . ' AND id_product = ' . (int)$id_product);
    
    if ($result) {
        echo 'success: FAQ supprimée';
    } else {
        echo 'error: Erreur suppression';
    }
    exit;
}

// Gérer l'activation/désactivation
if ($action === 'toggle_faq') {
    if ($faq_id <= 0) {
        echo 'error: ID FAQ manquant';
        exit;
    }
    
    // Récupérer l'état actuel
    $current = Db::getInstance()->getRow('SELECT active FROM ' . _DB_PREFIX_ . 'product_faq WHERE id = ' . (int)$faq_id . ' AND id_product = ' . (int)$id_product);
    
    if (!$current) {
        echo 'error: FAQ non trouvée';
        exit;
    }
    
    // Toggle l'état
    $new_active = $current['active'] ? 0 : 1;
    $result = Db::getInstance()->update('product_faq', [
        'active' => $new_active
    ], 'id = ' . (int)$faq_id . ' AND id_product = ' . (int)$id_product);
    
    if ($result) {
        echo 'success: FAQ ' . ($new_active ? 'activée' : 'désactivée');
    } else {
        echo 'error: Erreur toggle';
    }
    exit;
}

// Gérer la sauvegarde
$question = Tools::getValue('question');
$answer = Tools::getValue('answer');

if (empty($question) || empty($answer)) {
    echo 'error: Question et réponse requises';
    exit;
}

// Charger le module
$module = Module::getInstanceByName('productfaq');
if (!$module) {
    echo 'error: Module non trouvé';
    exit;
}

// Sauvegarder la FAQ
if ($faq_id > 0) {
    // Mettre à jour une FAQ existante
    $result = Db::getInstance()->update('product_faq', [
        'question' => pSQL($question),
        'answer' => pSQL($answer, true),
    ], 'id = ' . (int)$faq_id . ' AND id_product = ' . (int)$id_product);
    
    if ($result) {
        echo 'success: FAQ mise à jour';
    } else {
        echo 'error: Erreur mise à jour';
    }
} else {
    // Créer une nouvelle FAQ
    $result = Db::getInstance()->insert('product_faq', [
        'id_product' => (int)$id_product,
        'active' => 1,
        'question' => pSQL($question),
        'answer' => pSQL($answer, true),
    ]);

    if ($result) {
        $new_id = Db::getInstance()->Insert_ID();
        echo 'success: FAQ créée id:' . $new_id;
    } else {
        echo 'error: Erreur création';
    }
}
?>
