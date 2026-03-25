<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ======================================================
// DUPLICATION DES PACKS ADVANCED PACK 5
// PS 8.2 - Vianney
// ======================================================
require_once dirname(__FILE__) . '/../config/config.inc.php';
require_once dirname(__FILE__) . '/../init.php';

$shop_source = 1;
$shop_target = 2;

$db = Db::getInstance();

echo "<pre>";
echo "=== DUPLICATION PACKS ADVANCED PACK 5 ===\n";
echo "Source shop: $shop_source\n";
echo "Target shop: $shop_target\n\n";

// ------------------------------------------------------
// 1. Récupération des packs AP5 du shop source
// ------------------------------------------------------
$packs = $db->executeS("
    SELECT ap.*, p.id_product
    FROM ps_pm_advancedpack ap
    INNER JOIN ps_product p ON p.id_product = ap.id_pack
    WHERE ap.id_shop = ".(int)$shop_source."
");

if (!$packs) {
    die("Aucun pack trouvé dans la boutique source.\n");
}

echo count($packs)." packs trouvés.\n\n";

// On force les index à 0,1,2... pour éviter les tableaux "troués"
$packs = array_values($packs);

echo "DEBUG KEYS: ";
print_r(array_keys($packs));

// ------------------------------------------------------
// Fonctions utilitaires
// ------------------------------------------------------

// Duplique un produit dans ps_product + ps_product_shop
function duplicateProductToShop($id_product, $shop_target)
{
    $db = Db::getInstance();

    // Vérifier si déjà existant dans shop_target
    $exists = $db->getValue("
        SELECT COUNT(*) FROM ps_product_shop
        WHERE id_product=".(int)$id_product." AND id_shop=".(int)$shop_target."
    ");

    if ($exists) {
        echo " - Produit $id_product déjà lié au shop $shop_target\n";
        return true;
    }

    // Récupère dynamiquement les colonnes existantes
    $columns = $db->executeS("SHOW COLUMNS FROM ps_product_shop");

    $select_fields = [];
    $insert_fields = [];

    foreach ($columns as $col) {

        $name = $col['Field'];

        // On saute la PK et id_shop car on les réécrit
        if ($name == 'id_product_shop' || $name == 'id_shop') {
            continue;
        }

        $insert_fields[] = "`$name`";

        if ($name == 'id_product') {
            // On garde id_product comme tel
            $select_fields[] = "id_product";
        } else {
            // Sinon on copie la valeur du shop 1
            $select_fields[] = "`$name`";
        }
    }

    // Construction SQL
    $sql = "
        INSERT INTO ps_product_shop (" . implode(",", $insert_fields) . ", id_shop)
        SELECT " . implode(",", $select_fields) . ", ".(int)$shop_target."
        FROM ps_product_shop
        WHERE id_product=".(int)$id_product." AND id_shop=1
    ";

    $db->execute($sql);

    echo " - Produit $id_product ajouté dans ps_product_shop (shop $shop_target)\n";

    return true;
}


// Duplique ps_pm_advancedpack
function duplicateAP5Data($id_pack_source, $id_pack_target, $shop_target)
{
    $db = Db::getInstance();

    // Vérifier si la ligne AP5 existe déjà pour ce pack et ce shop
    $exists = $db->getValue("
        SELECT COUNT(*) FROM ps_pm_advancedpack
        WHERE id_pack=".(int)$id_pack_target." AND id_shop=".(int)$shop_target."
    ");

    if ($exists) {
        echo "   - pm_advancedpack DEJA PRESENT (skip)\n";
        return true;
    }

    // Sinon on copie depuis le shop 1
    $db->execute("
        INSERT INTO ps_pm_advancedpack (id_pack, id_shop, fixed_price, allow_remove_product,
            auto_choose, ml_choose, is_bundle, bundle_datas)
        SELECT ".(int)$id_pack_target.", ".(int)$shop_target.",
               fixed_price, allow_remove_product, auto_choose, ml_choose, is_bundle, bundle_datas
        FROM ps_pm_advancedpack
        WHERE id_pack=".(int)$id_pack_source." AND id_shop=1
    ");

    echo "   - pm_advancedpack OK\n";
}


// Duplique le contenu du pack
function duplicateAP5Products($id_pack_source, $id_pack_target)
{
    $db = Db::getInstance();

    // pm_advancedpack_products
    $items = $db->executeS("
        SELECT * FROM ps_pm_advancedpack_products
        WHERE id_pack=".(int)$id_pack_source."
    ");

    foreach ($items as $it) {

        $exists = $db->getValue("
        SELECT COUNT(*) FROM ps_pm_advancedpack_products
        WHERE id_pack = ".(int)$id_pack_target."
          AND id_product = ".(int)$it['id_product']."
        ");

        if ($exists) {
            return true; // ne pas recréer la ligne
        }

        $db->insert("pm_advancedpack_products", [
            'id_pack'                     => (int)$id_pack_target,
            'id_product'                  => (int)$it['id_product'],
            'default_id_product_attribute'=> (int)$it['default_id_product_attribute'],
            'quantity'                    => (int)$it['quantity'],
            'formula'                     => pSQL($it['formula']),
            'use_reduc'                   => (int)$it['use_reduc'],
            'position'                    => (int)$it['position'],
            'reduction_amount'            => (float)$it['reduction_amount'],
            'reduction_type'              => pSQL($it['reduction_type'])
        ]);

    }
    echo "   - pm_advancedpack_products OK\n";

    // pm_advancedpack_products_attributes
    $attrs = $db->executeS("
        SELECT * FROM ps_pm_advancedpack_products_attributes
        WHERE id_product_pack=".(int)$id_pack_source."
    ");

    foreach ($attrs as $a) {
        $exists = $db->getValue("
        SELECT COUNT(*) FROM ps_pm_advancedpack_products_attributes
        WHERE id_product_pack = ".(int)$id_pack_target."
          AND id_product_attribute = ".(int)$a['id_product_attribute']."
        ");

        if ($exists) return true;

        $db->insert("pm_advancedpack_products_attributes", [
            'id_product_pack'     => (int)$id_pack_target,
            'id_product_attribute'=> (int)$a['id_product_attribute'],
            'reduction_amount'    => (float)$a['reduction_amount'],
            'reduction_type'      => pSQL($a['reduction_type'])
        ]);
    }
    echo "   - pm_advancedpack_products_attributes OK\n";
}
function duplicateSpecificPrices($id_product, $shop_target, $shop_source){
    $db = Db::getInstance();
    $groupsPro = json_decode($db->getValue("SELECT value FROM ps_configuration WHERE name LIKE 'CKC_GROUP_PRO_IDS'"));
    $specificPrices = [];
    if(is_array($groupsPro)){
        foreach ($groupsPro as $group) {
            $specificPricesGroup = $db->executeS("SELECT * FROM ps_specific_price WHERE id_product=".(int)$id_product." AND id_shop=".(int)$shop_source."
             AND id_group=".(int)$group);

            if(count($specificPricesGroup) > 0 && isset($specificPricesGroup[0]['id_product'])){
                $specificPrices = array_merge($specificPrices, $specificPricesGroup);
            }
        }
    }
    if(count($specificPrices) > 0 && isset($specificPrices[0]['id_product'])){
        foreach ($specificPrices as $specificPrice) {

            $checkIfExists = $db->getValue("SELECT COUNT(*) FROM ps_specific_price WHERE id_product=".(int)$specificPrice['id_product']." AND id_shop=".(int)$shop_target."
             AND id_group=".(int)$specificPrice['id_group']);

            if(!$checkIfExists){
                $createSpecificPrice = $db->insert("specific_price", [
                    'id_specific_price_rule'      => (int)$specificPrice['id_specific_price_rule'],
                    'id_cart'                     => (int)$specificPrice['id_cart'],
                    'id_product'                  => (int)$specificPrice['id_product'],
                    'id_shop'                     => (int)$shop_target,
                    'id_shop_group'               => (int)$specificPrice['id_shop_group'],
                    'id_currency'                 => (int)$specificPrice['id_currency'],
                    'id_country'                  => (int)$specificPrice['id_country'],
                    'id_group'                    => (int)$specificPrice['id_group'],
                    'id_customer'                 => (int)$specificPrice['id_customer'],
                    'id_product_attribute'        => (int)$specificPrice['id_product_attribute'],
                    'price'                       => $specificPrice['price'],
                    'from_quantity'               => $specificPrice['from_quantity'],
                    'reduction'                   => $specificPrice['reduction'],
                    'reduction_tax'               => $specificPrice['reduction_tax'],
                    'reduction_type'              => $specificPrice['reduction_type'],
                    'from'                        => $specificPrice['from'],
                    'to'                          => $specificPrice['to']
                ]);
            } else {
                echo 'SpecificPrice exists :'.$specificPrice['id_specific_price_rule'].' . Product : '.$specificPrice['id_product'];
            }
        }
    }

}

function duplicateProductData($id_product, $shop_target, $shop_source){
    $db = Db::getInstance();
    $exists = $db->getValue("SELECT COUNT(*) FROM ps_product_lang WHERE id_product=".(int)$id_product." AND id_shop=".(int)$shop_target);
    if(!$exists){
        $originalProductData = $db->getRow("SELECT * FROM ps_product_lang WHERE id_product=".(int)$id_product." AND id_shop=".(int)$shop_source);
        if(isset($originalProductData['id_product'])){
            $createProductData = Db::getInstance()->insert("product_lang", [
                'id_product'            => (int)$originalProductData['id_product'],
                'id_shop'               => (int)$shop_target,
                'id_lang'               => (int)$originalProductData['id_lang'],
                'description'           => $originalProductData['description'],
                'description_short'     => $originalProductData['description_short'],
                'link_rewrite'          => $originalProductData['link_rewrite'],
                'meta_description'      => $originalProductData['meta_description'],
                'meta_keywords'         => $originalProductData['meta_keywords'],
                'meta_title'            => $originalProductData['meta_title'],
                'name'                  => $originalProductData['name'],
                'available_now'         => $originalProductData['available_now'],
                'available_later'       => $originalProductData['available_later'],
                'delivery_in_stock'     => $originalProductData['delivery_in_stock'],
                'delivery_out_stock'    => $originalProductData['delivery_out_stock']
            ]);
        }
    } else {
        echo 'ProductData exists : '.$id_product;
    }
}

function duplicateProductAttributes($id_product, $shop_target, $shop_source){
    $db = Db::getInstance();
    $sourceShopAttributes = $db->executeS("SELECT * FROM ps_product_attribute_shop WHERE id_product=".(int)$id_product." AND id_shop=".(int)$shop_source);

    if(isset($sourceShopAttributes[0]['id_product_attribute'])){
        foreach ($sourceShopAttributes as $sourceShopAttribute) {
            $checkIfExists = $db->getValue("SELECT COUNT(*) FROM ps_product_attribute_shop WHERE id_product=".(int)$id_product." AND id_shop=".(int)$shop_target." 
            AND id_product_attribute=".(int)$sourceShopAttribute['id_product_attribute']);
            if(!$checkIfExists){
                echo 'ProductAttribute Create INFO : '.$sourceShopAttribute['id_product_attribute'].' . Product : '.$id_product. '. Shop : '. $shop_target;
                $createProductAttribute = Db::getInstance()->insert("product_attribute_shop", [
                    'id_product'          => (int)$id_product,
                    'id_product_attribute'      => (int)$sourceShopAttribute['id_product_attribute'],
                    'id_shop' => (int)$shop_target,
                    'wholesale_price' => (float)$sourceShopAttribute['wholesale_price'],
                    'price' => (float)$sourceShopAttribute['price'],
                    'ecotax' => (float)$sourceShopAttribute['ecotax'],
                    'weight' => (float)$sourceShopAttribute['weight'],
                    'unit_price_impact' => (float)$sourceShopAttribute['unit_price_impact'],
                    'default_on' => (int)$sourceShopAttribute['default_on'],
                    'minimal_quantity' => (int)$sourceShopAttribute['minimal_quantity'],
                    'low_stock_threshold' => (int)$sourceShopAttribute['low_stock_threshold'],
                    'low_stock_alert' => (int)$sourceShopAttribute['low_stock_alert'],
                    'available_date' => pSQL($sourceShopAttribute['available_date'])
                ]);
                echo 'ProductAttribute Create : '.json_encode($createProductAttribute);
            } else {
                echo 'ProductAttribute exists : '.$sourceShopAttribute['id_product_attribute'].' . Product : '.$id_product;
            }

        }
    }
}


// ------------------------------------------------------
// BOUCLE DE DUPLICATION AVEC FOR
// ------------------------------------------------------
for ($i = 0; $i < count($packs); $i++) {

    $pack = $packs[$i];

    $id_pack_source = (int)$pack['id_pack'];
    $id_product     = (int)$pack['id_product'];

    echo "-------------------------------\n";
    echo "PACK #$id_pack_source (product $id_product)\n";
    echo "-------------------------------\n";

    try {
        $db->execute("START TRANSACTION");

        duplicateProductToShop($id_product, $shop_target);
        duplicateSpecificPrices($id_product, $shop_target, $shop_source);
        duplicateProductData($id_product, $shop_target, $shop_source);
        /*duplicateProductAttributes($id_product, $shop_target, $shop_source);*/
        duplicateAP5Data($id_pack_source, $id_product, $shop_target);
        /*duplicateAP5Products($id_pack_source, $id_product);*/

        $db->execute("COMMIT");

        echo ">>> PACK $id_pack_source OK\n\n";

    } catch (Exception $e) {
        $db->execute("ROLLBACK");
        echo "!!! ERREUR : ".$e->getMessage()."\n\n";
    }
}

echo "=== TERMINÉ ===\n";
echo "</pre>";
