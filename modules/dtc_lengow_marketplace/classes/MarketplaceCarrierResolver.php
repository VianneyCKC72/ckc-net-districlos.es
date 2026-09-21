<?php
/**
 * Selection du transporteur PrestaShop pour les commandes marketplace importees par Lengow,
 * en fonction des produits commandes.
 *
 * Chaque type de colis (portail / portillon / autres) porte sa propre liste de produits et son
 * transporteur, saisis dans le back-office du module dtc_lengow_marketplace.
 * Les transporteurs sont stockes par `id_reference` afin de survivre aux editions PrestaShop
 * (qui creent un nouveau `id_carrier` a chaque modification).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class MarketplaceCarrierResolver
{
    const CONF_ENABLED = 'DTC_LGWMP_CARRIER_ENABLED';
    const CONF_CARRIER_GATE = 'DTC_LGWMP_CARRIER_GATE';
    const CONF_CARRIER_WICKET = 'DTC_LGWMP_CARRIER_WICKET';
    const CONF_CARRIER_OTHER = 'DTC_LGWMP_CARRIER_OTHER';
    const CONF_PRODUCTS_GATE = 'DTC_LGWMP_PRODUCTS_GATE';
    const CONF_PRODUCTS_WICKET = 'DTC_LGWMP_PRODUCTS_WICKET';
    const CONF_PRODUCTS_OTHER = 'DTC_LGWMP_PRODUCTS_OTHER';
    const CONF_PRIORITY = 'DTC_LGWMP_CARRIER_PRIORITY';

    const TYPE_GATE = 'gate';
    const TYPE_WICKET = 'wicket';
    const TYPE_OTHER = 'other';

    /**
     * Ordre applique lorsque la configuration ne precise rien : le colis le plus contraignant gagne.
     */
    const DEFAULT_PRIORITY = 'gate,wicket,other';

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        return Module::isEnabled('dtc_lengow_marketplace')
            && (bool) Configuration::get(self::CONF_ENABLED);
    }

    /**
     * Transporteur a appliquer a une commande marketplace.
     *
     * @param int[] $idProducts identifiants produits de la commande
     *
     * @return int id_carrier actif, 0 si aucune regle applicable (laisse Lengow decider)
     */
    public static function resolveIdCarrier(array $idProducts)
    {
        if (!self::isEnabled()) {
            return 0;
        }

        $type = self::getOrderType($idProducts);
        if ($type === null) {
            return 0;
        }

        $idReference = (int) Configuration::get(self::getCarrierConfigurationKey($type));
        if ($idReference <= 0) {
            return 0;
        }

        return self::getActiveIdCarrierByReference($idReference);
    }

    /**
     * Type de colis de la commande : le premier type de l'ordre de priorite dont un produit
     * figure dans la commande. Un produit absent des trois listes est traite comme "autres".
     *
     * @param int[] $idProducts
     *
     * @return string|null null si aucune liste de produits n'est configuree
     */
    public static function getOrderType(array $idProducts)
    {
        $configuredProducts = 0;
        $productsByType = [];
        foreach (self::getTypes() as $type) {
            $productsByType[$type] = self::getConfiguredProductIds(self::getProductsConfigurationKey($type));
            $configuredProducts += count($productsByType[$type]);
        }
        if ($configuredProducts === 0) {
            return null;
        }

        $idProducts = array_map('intval', $idProducts);
        foreach (self::getPriority() as $type) {
            if (array_intersect($idProducts, $productsByType[$type])) {
                return $type;
            }
        }

        return self::TYPE_OTHER;
    }

    /**
     * @return string[]
     */
    public static function getTypes()
    {
        return [self::TYPE_GATE, self::TYPE_WICKET, self::TYPE_OTHER];
    }

    /**
     * Ordre de priorite configure, complete par les types manquants.
     *
     * @return string[]
     */
    public static function getPriority()
    {
        $priority = [];
        foreach (explode(',', (string) Configuration::get(self::CONF_PRIORITY)) as $type) {
            $type = trim($type);
            if (in_array($type, self::getTypes(), true) && !in_array($type, $priority, true)) {
                $priority[] = $type;
            }
        }

        foreach (explode(',', self::DEFAULT_PRIORITY) as $type) {
            if (!in_array($type, $priority, true)) {
                $priority[] = $type;
            }
        }

        return $priority;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public static function getCarrierConfigurationKey($type)
    {
        switch ($type) {
            case self::TYPE_GATE:
                return self::CONF_CARRIER_GATE;
            case self::TYPE_WICKET:
                return self::CONF_CARRIER_WICKET;
            default:
                return self::CONF_CARRIER_OTHER;
        }
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public static function getProductsConfigurationKey($type)
    {
        switch ($type) {
            case self::TYPE_GATE:
                return self::CONF_PRODUCTS_GATE;
            case self::TYPE_WICKET:
                return self::CONF_PRODUCTS_WICKET;
            default:
                return self::CONF_PRODUCTS_OTHER;
        }
    }

    /**
     * @param string $configurationKey
     *
     * @return int[]
     */
    public static function getConfiguredProductIds($configurationKey)
    {
        return self::parseIdList((string) Configuration::get($configurationKey));
    }

    /**
     * @param array $ids
     *
     * @return string valeur stockee en configuration
     */
    public static function formatIdList(array $ids)
    {
        $cleaned = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $cleaned[$id] = $id;
            }
        }
        sort($cleaned);

        return implode(',', $cleaned);
    }

    /**
     * @param string $value
     *
     * @return int[]
     */
    public static function parseIdList($value)
    {
        if (trim($value) === '') {
            return [];
        }

        $ids = [];
        foreach (explode(',', $value) as $id) {
            $id = (int) trim($id);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Derniere version active du transporteur portant cette reference.
     *
     * @param int $idReference
     *
     * @return int
     */
    private static function getActiveIdCarrierByReference($idReference)
    {
        $idShop = (int) Context::getContext()->shop->id;
        if ($idShop <= 0) {
            $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }

        $idCarrier = Db::getInstance()->getValue(
            'SELECT c.`id_carrier`
            FROM `' . _DB_PREFIX_ . 'carrier` c
            INNER JOIN `' . _DB_PREFIX_ . 'carrier_shop` cs ON cs.`id_carrier` = c.`id_carrier`
            WHERE c.`id_reference` = ' . (int) $idReference . '
                AND c.`deleted` = 0
                AND c.`active` = 1
                AND cs.`id_shop` = ' . $idShop . '
            ORDER BY c.`id_carrier` DESC'
        );

        return (int) $idCarrier;
    }
}
