<?php
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

$id_product = (int)Tools::getValue('id_product');
$end_of_life_data = Tools::getValue('end_of_life', []);


if (!$id_product) {
    echo json_encode(['success' => false, 'message' => 'ID produit manquant']);
    exit;
}

$saved_count = 0;
$saved_end_of_life_count = 0;
$deleted_count = 0;
$errors = [];

// Traiter uniquement les déclinaisons envoyées dans le formulaire
foreach ($end_of_life_data as $id_product_attribute => $end_of_life) {
    $id_product_attribute = (int)$id_product_attribute;
    $end_of_life = (int)$end_of_life;
    
    // Vérifier si l'enregistrement existe
    $exists = Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_attribute_end_of_life WHERE id_product_attribute = ' . (int)$id_product_attribute);
    
    $result = false;
    
    if ($end_of_life == 1) {
        // La déclinaison est cochée : créer ou mettre à jour
        if ($exists) {
            // Mettre à jour l'enregistrement existant
            $result = Db::getInstance()->update(
                'product_attribute_end_of_life',
                [
                    'end_of_life' => 1,
                    'date_upd' => date('Y-m-d H:i:s')
                ],
                'id_product_attribute = ' . (int)$id_product_attribute
            );
        } else {
            // Créer un nouvel enregistrement
            $result = Db::getInstance()->insert(
                'product_attribute_end_of_life',
                [
                    'id_product_attribute' => $id_product_attribute,
                    'end_of_life' => 1,
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s')
                ]
            );
        }
        
        if ($result) {
            $saved_count++;
            $saved_end_of_life_count++;
        } else {
            $errors[] = 'Erreur pour la déclinaison ' . $id_product_attribute;
        }
    } else {
        // La déclinaison est décochée : supprimer l'enregistrement s'il existe
        if ($exists) {
            $result = Db::getInstance()->delete(
                'product_attribute_end_of_life',
                'id_product_attribute = ' . (int)$id_product_attribute
            );
            
            if ($result) {
                $saved_count++;
                $deleted_count++;
            } else {
                $errors[] = 'Erreur lors de la suppression de la déclinaison ' . $id_product_attribute;
            }
        }
        // Si l'enregistrement n'existe pas, on ne fait rien
    }
}

if (empty($errors)) {
    if ($saved_count > 0) {
        $messages = [];
        
        if ($saved_end_of_life_count > 0) {
            $messages[] = $saved_end_of_life_count . ' déclinaison(s) marquée(s) fin de vie';
        }
        
        if ($deleted_count > 0) {
            $messages[] = $deleted_count . ' déclinaison(s) retirée(s) de fin de vie';
        }
        
        if (!empty($messages)) {
            echo json_encode(['success' => true, 'message' => implode(' et ', $messages)]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Modifications sauvegardées']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Aucune modification nécessaire']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erreurs: ' . implode(', ', $errors)]);
}
