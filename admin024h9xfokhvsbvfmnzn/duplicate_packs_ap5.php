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

$simple_kits = [2299,2298,2296,2295,2297,2291,1390,1389,1356,1355,1354,1353,1352,1351,1350,1349,1348,1347,1953,2247,1956,2248,1957,1958,2249,2250,
    2237,2239,2238,2240,2241,2242,2244,2245,2246,2252,2254,2255,2256,2257,2258,2259,2260,2261,22622263,2264,2265,2266,2267,2268,2269,2271,2224,2226,2227,2228,2235,2231,2225,2230,2234,2229,2232,2233,2287,
    1936,1938,1939,1940,1941,1942,1944,1945,1946,1947,1948,1949,2272,2274,2276,2279,2280,2281,2273,2275,2277,2282,2283,2284];

$cloture_composite = $db->executeS("SELECT id_product FROM ps_category_product WHERE id_category = 438");
$cloture_alum_en_kit = $db->executeS("SELECT id_product FROM ps_category_product WHERE id_category = 216");

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

function duplicateProductImages($id_product, $shop_target, $shop_source){
    $db = Db::getInstance();
    $sourceShopImages = $db->executeS("SELECT id_image, cover FROM ps_image_shop WHERE id_product=".(int)$id_product." AND id_shop=".(int)$shop_source);

    if(isset($sourceShopImages[0]['id_image'])){
        foreach ($sourceShopImages as $sourceShopImage) {
            $checkIfExists = $db->getValue("SELECT COUNT(*) FROM ps_image_shop WHERE id_product = ".(int)$id_product." AND id_shop=".(int)$shop_target." AND id_image=".(int)$sourceShopImage['id_image']);
            if(!$checkIfExists){
                echo "Image Create Info : image_id = ". $sourceShopImage['id_image'].'. Product : '.$id_product;
                $sql = "INSERT INTO `ps_image_shop` (`id_product`, `id_image`, `id_shop`, `cover`) 
                            VALUES ('".$id_product."', '".$sourceShopImage['id_image']."', '".$shop_target."', NULLIF(".(int)$sourceShopImage['cover'].",0))";
                $create = Db::getInstance()->execute($sql);
            } else {
                echo "Image Exists : image_id = ". $sourceShopImage['id_image'].'. Product : '.$id_product;
            }
        }
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

                $sql = "INSERT INTO ps_product_attribute_shop (`id_product`, `id_product_attribute`, `id_shop`, `wholesale_price`, `price`, `ecotax`, `weight`, `unit_price_impact`, `default_on`,
                        `minimal_quantity`, `low_stock_threshold`, `low_stock_alert`, `available_date`) 
                        VALUES (".(int)$id_product.", ".(int)$sourceShopAttribute['id_product_attribute'].", ".(int)$shop_target.", ".(float)$sourceShopAttribute['wholesale_price'].",
                        ".(float)$sourceShopAttribute['price'].", ".(float)$sourceShopAttribute['ecotax'].", ".(float)$sourceShopAttribute['weight'].", ".(float)$sourceShopAttribute['unit_price_impact'].",
                        NULLIF(".(int)$sourceShopAttribute['default_on'].",0), ".(int)$sourceShopAttribute['minimal_quantity'].", NULLIF(".(int)$sourceShopAttribute['low_stock_threshold'].",0), 
                        ".(int)$sourceShopAttribute['low_stock_alert'].", ".pSQL($sourceShopAttribute['available_date']).")";

                $createProductAttribute = Db::getInstance()->execute($sql);
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
        duplicateProductAttributes($id_product, $shop_target, $shop_source);
        duplicateAP5Data($id_pack_source, $id_product, $shop_target);
        /*duplicateAP5Products($id_pack_source, $id_product);*/

        $db->execute("COMMIT");

        echo ">>> PACK $id_pack_source OK\n\n";

    } catch (Exception $e) {
        $db->execute("ROLLBACK");
        echo "!!! ERREUR : ".$e->getMessage()."\n\n";
    }
}

foreach ($simple_kits as $product){

    $id_product = (int)$product;

    echo "-------------------------------\n";
    echo "KIT (product $id_product)\n";
    echo "-------------------------------\n";

    try {
        $db->execute("START TRANSACTION");

        duplicateProductToShop($id_product, $shop_target);
        duplicateSpecificPrices($id_product, $shop_target, $shop_source);
        duplicateProductData($id_product, $shop_target, $shop_source);
        duplicateProductImages($id_product, $shop_target, $shop_source);
        duplicateProductAttributes($id_product, $shop_target, $shop_source);

        $db->execute("COMMIT");

        echo ">>> PACK $id_pack_source OK\n\n";

    } catch (Exception $e) {
        $db->execute("ROLLBACK");
        echo "!!! ERREUR : ".$e->getMessage()."\n\n";
    }

}

foreach ($cloture_composite as $product){
    $id_product = (int)$product['id_product'];

    echo "-------------------------------\n";
    echo "KIT (product $id_product)\n";
    echo "-------------------------------\n";

    try {
        $db->execute("START TRANSACTION");

        duplicateProductToShop($id_product, $shop_target);
        duplicateSpecificPrices($id_product, $shop_target, $shop_source);
        duplicateProductData($id_product, $shop_target, $shop_source);
        duplicateProductImages($id_product, $shop_target, $shop_source);
        duplicateProductAttributes($id_product, $shop_target, $shop_source);

        $db->execute("COMMIT");

        echo ">>> KIT $id_product OK\n\n";

    } catch (Exception $e) {
        $db->execute("ROLLBACK");
        echo "!!! ERREUR : ".$e->getMessage()."\n\n";
    }
}

foreach ($cloture_alum_en_kit as $product){
    $id_product = (int)$product['id_product'];

    echo "-------------------------------\n";
    echo "KIT (product $id_product)\n";
    echo "-------------------------------\n";

    try {
        $db->execute("START TRANSACTION");

        duplicateProductToShop($id_product, $shop_target);
        duplicateSpecificPrices($id_product, $shop_target, $shop_source);
        duplicateProductData($id_product, $shop_target, $shop_source);
        duplicateProductImages($id_product, $shop_target, $shop_source);
        duplicateProductAttributes($id_product, $shop_target, $shop_source);

        $db->execute("COMMIT");

        echo ">>> KIT $id_product OK\n\n";

    } catch (Exception $e) {
        $db->execute("ROLLBACK");
        echo "!!! ERREUR : ".$e->getMessage()."\n\n";
    }
}
echo "=== TERMINÉ ===\n";
echo "</pre>";
