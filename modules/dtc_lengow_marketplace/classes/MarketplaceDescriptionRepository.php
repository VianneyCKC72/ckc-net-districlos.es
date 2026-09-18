<?php
/**
 * Descriptions produit dediees aux exports marketplace.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class MarketplaceDescriptionRepository
{
    const TABLE_NAME = 'dtc_lengow_marketplace_description';

    /**
     * @return bool
     */
    public static function tableExists()
    {
        $tableName = _DB_PREFIX_ . self::TABLE_NAME;

        return (bool) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = "' . pSQL($tableName) . '"'
        );
    }

    /**
     * @param int $idProduct
     * @param int $idShop
     *
     * @return array<int, string>
     */
    public function getForProduct($idProduct, $idShop)
    {
        $idProduct = (int) $idProduct;
        $idShop = (int) $idShop;

        if ($idProduct <= 0 || $idShop <= 0 || !self::tableExists()) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT id_lang, description_marketplace
            FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`
            WHERE id_product = ' . (int) $idProduct . '
            AND id_shop = ' . (int) $idShop
        );

        $descriptions = [];
        foreach ($rows ?: [] as $row) {
            $descriptions[(int) $row['id_lang']] = (string) $row['description_marketplace'];
        }

        return $descriptions;
    }

    /**
     * @param int $idProduct
     * @param int $idShop
     * @param array<int, string> $valuesByLang
     *
     * @return bool
     */
    public function saveForProduct($idProduct, $idShop, array $valuesByLang)
    {
        $idProduct = (int) $idProduct;
        $idShop = (int) $idShop;

        if ($idProduct <= 0 || $idShop <= 0 || !self::tableExists()) {
            return false;
        }

        foreach ($valuesByLang as $idLang => $description) {
            $idLang = (int) $idLang;
            if ($idLang <= 0) {
                continue;
            }

            $description = trim((string) $description);
            Db::getInstance()->execute(
                'REPLACE INTO `' . _DB_PREFIX_ . self::TABLE_NAME . '`
                (`id_product`, `id_shop`, `id_lang`, `description_marketplace`)
                VALUES (
                    ' . (int) $idProduct . ',
                    ' . (int) $idShop . ',
                    ' . (int) $idLang . ',
                    "' . pSQL($description, true) . '"
                )'
            );
        }

        return true;
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     */
    public function deleteForProduct($idProduct)
    {
        $idProduct = (int) $idProduct;

        if ($idProduct <= 0 || !self::tableExists()) {
            return false;
        }

        return (bool) Db::getInstance()->delete(
            self::TABLE_NAME,
            'id_product = ' . (int) $idProduct
        );
    }

    /**
     * Methode utilitaire pour l'injection dans un flux Lengow.
     *
     * @param int $idProduct
     * @param int $idLang
     * @param int $idShop
     *
     * @return string
     */
    public static function getValue($idProduct, $idLang, $idShop)
    {
        $idProduct = (int) $idProduct;
        $idLang = (int) $idLang;
        $idShop = (int) $idShop;

        if ($idProduct <= 0 || $idLang <= 0 || $idShop <= 0 || !self::tableExists()) {
            return '';
        }

        return (string) Db::getInstance()->getValue(
            'SELECT description_marketplace
            FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`
            WHERE id_product = ' . (int) $idProduct . '
            AND id_lang = ' . (int) $idLang . '
            AND id_shop = ' . (int) $idShop
        );
    }
}
