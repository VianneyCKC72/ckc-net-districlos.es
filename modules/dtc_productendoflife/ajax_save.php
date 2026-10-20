<?php
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';

$id_product = (int) Tools::getValue('id_product');
$end_of_life_data = Tools::getValue('end_of_life', []);
$end_of_life_national_data = Tools::getValue('end_of_life_national', []);

if (!$id_product) {
    echo json_encode(['success' => false, 'message' => 'ID produit manquant']);
    exit;
}

// Garantir la colonne national sur les installs historiques
$table = _DB_PREFIX_ . 'product_attribute_end_of_life';
$columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . bqSQL($table) . '` LIKE "end_of_life_national"');
if (empty($columns)) {
    Db::getInstance()->execute(
        'ALTER TABLE `' . bqSQL($table) . '`
        ADD `end_of_life_national` tinyint(1) NOT NULL DEFAULT 0 AFTER `end_of_life`'
    );
}

$ids = array_unique(array_merge(
    array_keys(is_array($end_of_life_data) ? $end_of_life_data : []),
    array_keys(is_array($end_of_life_national_data) ? $end_of_life_national_data : [])
));

$saved_count = 0;
$saved_end_of_life_count = 0;
$saved_national_count = 0;
$deleted_count = 0;
$errors = [];

foreach ($ids as $id_product_attribute) {
    $id_product_attribute = (int) $id_product_attribute;
    if ($id_product_attribute <= 0) {
        continue;
    }

    $end_of_life = isset($end_of_life_data[$id_product_attribute])
        ? (int) $end_of_life_data[$id_product_attribute]
        : 0;
    $end_of_life_national = isset($end_of_life_national_data[$id_product_attribute])
        ? (int) $end_of_life_national_data[$id_product_attribute]
        : 0;

    $exists = (bool) Db::getInstance()->getValue(
        'SELECT COUNT(*) FROM `' . bqSQL($table) . '`
        WHERE id_product_attribute = ' . (int) $id_product_attribute
    );

    $result = false;

    if ($end_of_life === 1 || $end_of_life_national === 1) {
        $payload = [
            'end_of_life' => $end_of_life ? 1 : 0,
            'end_of_life_national' => $end_of_life_national ? 1 : 0,
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        if ($exists) {
            $result = Db::getInstance()->update(
                'product_attribute_end_of_life',
                $payload,
                'id_product_attribute = ' . (int) $id_product_attribute
            );
        } else {
            $payload['id_product_attribute'] = $id_product_attribute;
            $payload['date_add'] = date('Y-m-d H:i:s');
            $result = Db::getInstance()->insert('product_attribute_end_of_life', $payload);
        }

        if ($result) {
            $saved_count++;
            if ($end_of_life === 1) {
                ++$saved_end_of_life_count;
            }
            if ($end_of_life_national === 1) {
                ++$saved_national_count;
            }
        } else {
            $errors[] = 'Erreur pour la déclinaison ' . $id_product_attribute;
        }
    } elseif ($exists) {
        // Aucun flag actif : supprimer la ligne (conserve le seuil seulement si un flag reste)
        $result = Db::getInstance()->delete(
            'product_attribute_end_of_life',
            'id_product_attribute = ' . (int) $id_product_attribute
        );

        if ($result) {
            $saved_count++;
            $deleted_count++;
        } else {
            $errors[] = 'Erreur lors de la suppression de la déclinaison ' . $id_product_attribute;
        }
    }
}

if (empty($errors)) {
    if ($saved_count > 0) {
        $messages = [];

        if ($saved_end_of_life_count > 0) {
            $messages[] = $saved_end_of_life_count . ' déclinaison(s) marquée(s) fin de vie';
        }

        if ($saved_national_count > 0) {
            $messages[] = $saved_national_count . ' déclinaison(s) marquée(s) fin de vie national';
        }

        if ($deleted_count > 0) {
            $messages[] = $deleted_count . ' déclinaison(s) retirée(s) de fin de vie';
        }

        echo json_encode([
            'success' => true,
            'message' => !empty($messages) ? implode(' et ', $messages) : 'Modifications sauvegardées',
        ]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Aucune modification nécessaire']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erreurs: ' . implode(', ', $errors)]);
}
