<?php
// Script AJAX pour sauvegarder les FAQ
require_once('../../config/config.inc.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'error: Méthode non autorisée';
    exit;
}

$action = Tools::getValue('action');
$id_product = (int) Tools::getValue('id_product');
$faq_id = (int) Tools::getValue('faq_id');
$id_lang = (int) Tools::getValue('id_lang', Context::getContext()->language->id);
if ($id_lang <= 0) {
    $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
}

if (!Tools::getToken(false)) {
    echo 'error: Token de sécurité manquant';
    exit;
}

if (!$id_product) {
    echo 'error: ID produit manquant';
    exit;
}

$module = Module::getInstanceByName('productfaq');
if (!$module) {
    echo 'error: Module non trouvé';
    exit;
}

if (method_exists('ProductFaq', 'ensureLangTable')) {
    ProductFaq::ensureLangTable();
}

if ($action === 'delete_faq') {
    if ($faq_id <= 0) {
        echo 'error: ID FAQ manquant';
        exit;
    }

    $result = Db::getInstance()->delete('product_faq', 'id = ' . (int) $faq_id . ' AND id_product = ' . (int) $id_product);
    if ($result) {
        Db::getInstance()->delete('product_faq_lang', 'id_faq = ' . (int) $faq_id);
        echo 'success: FAQ supprimée';
    } else {
        echo 'error: Erreur suppression';
    }
    exit;
}

if ($action === 'toggle_faq') {
    if ($faq_id <= 0) {
        echo 'error: ID FAQ manquant';
        exit;
    }

    $current = Db::getInstance()->getRow(
        'SELECT active FROM ' . _DB_PREFIX_ . 'product_faq WHERE id = ' . (int) $faq_id . ' AND id_product = ' . (int) $id_product
    );

    if (!$current) {
        echo 'error: FAQ non trouvée';
        exit;
    }

    $new_active = $current['active'] ? 0 : 1;
    $result = Db::getInstance()->update('product_faq', [
        'active' => $new_active,
    ], 'id = ' . (int) $faq_id . ' AND id_product = ' . (int) $id_product);

    if ($result) {
        echo 'success: FAQ ' . ($new_active ? 'activée' : 'désactivée');
    } else {
        echo 'error: Erreur toggle';
    }
    exit;
}

$question = Tools::getValue('question');
$answer = Tools::getValue('answer');

if (empty($question) || empty($answer)) {
    echo 'error: Question et réponse requises';
    exit;
}

if ($faq_id > 0) {
    $exists = (int) Db::getInstance()->getValue(
        'SELECT id FROM `' . _DB_PREFIX_ . 'product_faq`
         WHERE id = ' . (int) $faq_id . ' AND id_product = ' . (int) $id_product
    );
    if (!$exists) {
        echo 'error: FAQ non trouvée';
        exit;
    }

    $result = ProductFaq::saveFaqTexts($faq_id, $id_lang, $question, $answer);

    if ($result) {
        echo 'success: FAQ mise à jour';
    } else {
        echo 'error: Erreur mise à jour';
    }
} else {
    $result = Db::getInstance()->insert('product_faq', [
        'id_product' => (int) $id_product,
        'active' => 1,
        'question' => pSQL($question),
        'answer' => pSQL($answer, true),
    ]);

    if ($result) {
        $new_id = (int) Db::getInstance()->Insert_ID();
        if ($id_lang !== (int) Configuration::get('PS_LANG_DEFAULT')) {
            ProductFaq::saveFaqTexts($new_id, $id_lang, $question, $answer);
        }
        echo 'success: FAQ créée id:' . $new_id;
    } else {
        echo 'error: Erreur création';
    }
}
