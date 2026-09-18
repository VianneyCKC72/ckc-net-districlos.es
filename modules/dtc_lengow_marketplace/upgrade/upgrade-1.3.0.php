<?php
/**
 * Passage du ciblage par categorie a un ciblage par produit pour le choix du transporteur
 * des commandes marketplace.
 *
 * Les listes de produits sont amorcees une seule fois depuis les categories portail / portillon
 * afin de ne pas laisser l'ecran de configuration vide. Elles sont ensuite maintenues produit par
 * produit dans le back-office : le resolver n'utilise plus les categories.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Dtc_lengow_marketplace $module
 *
 * @return bool
 */
function upgrade_module_1_3_0($module)
{
    $module->installCarrierConfiguration();

    $legacyKeys = [
        MarketplaceCarrierResolver::CONF_PRODUCTS_GATE => [
            'configuration' => 'DTC_LGWMP_CATEGORIES_GATE',
            // portail battant, coulissant, autoportant, electrique, portails alu, packs portail + portillon
            'fallback' => [239, 241, 242, 170, 364, 337, 323],
        ],
        MarketplaceCarrierResolver::CONF_PRODUCTS_WICKET => [
            'configuration' => 'DTC_LGWMP_CATEGORIES_WICKET',
            'fallback' => [130, 171, 219, 254, 280, 292, 294, 121, 119],
        ],
    ];

    foreach ($legacyKeys as $productsKey => $legacy) {
        if (MarketplaceCarrierResolver::getConfiguredProductIds($productsKey)) {
            continue;
        }

        $idCategories = MarketplaceCarrierResolver::parseIdList(
            (string) Configuration::get($legacy['configuration'])
        );
        if (empty($idCategories)) {
            $idCategories = $legacy['fallback'];
        }

        Configuration::updateValue(
            $productsKey,
            MarketplaceCarrierResolver::formatIdList(dtcLgwmpGetProductIdsInCategories($idCategories))
        );
    }

    foreach ($legacyKeys as $legacy) {
        Configuration::deleteByName($legacy['configuration']);
    }

    return true;
}

/**
 * @param int[] $idCategories
 *
 * @return int[]
 */
function dtcLgwmpGetProductIdsInCategories(array $idCategories)
{
    $idCategories = array_filter(array_map('intval', $idCategories));
    if (empty($idCategories)) {
        return [];
    }

    $rows = Db::getInstance()->executeS(
        'SELECT DISTINCT cp.`id_product`
        FROM `' . _DB_PREFIX_ . 'category_product` cp
        WHERE cp.`id_category` IN (' . implode(',', $idCategories) . ')'
    );
    if (!is_array($rows)) {
        return [];
    }

    $idProducts = [];
    foreach ($rows as $row) {
        $idProducts[] = (int) $row['id_product'];
    }

    return $idProducts;
}
