<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Mapping manuel image pack → couleur (id_attribute).
 * Remplace l'ancien découpage auto (n images / n couleurs).
 */
class PackGalleryColorHelper
{
    public const TABLE = 'pm_advancedpack_image_color';

    public static function ensureTable(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
                `id_image` INT(10) UNSIGNED NOT NULL,
                `id_pack` INT(10) UNSIGNED NOT NULL,
                `id_attribute` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_image`),
                KEY `id_pack` (`id_pack`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    /** @return array<int, int> id_image => id_attribute (0 = toutes / non filtrée) */
    public static function buildColorMap(int $idPack, array $productsPack = []): array
    {
        unset($productsPack);
        self::ensureTable();
        $cacheId = 'PackGalleryColorHelper_manual_v1_' . $idPack;
        if (Cache::isStored($cacheId)) {
            return Cache::retrieve($cacheId);
        }

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `id_image`, `id_attribute`
             FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE `id_pack` = ' . (int) $idPack
        ) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $idAttribute = (int) $row['id_attribute'];
            if ($idAttribute > 0) {
                $map[(int) $row['id_image']] = $idAttribute;
            }
        }
        Cache::store($cacheId, $map);

        return $map;
    }

    /** @return array<int, int> id_image => id_attribute (inclut 0) */
    public static function getRawMap(int $idPack): array
    {
        self::ensureTable();
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `id_image`, `id_attribute`
             FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE `id_pack` = ' . (int) $idPack
        ) ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id_image']] = (int) $row['id_attribute'];
        }

        return $map;
    }

    /**
     * @param array<int, int> $mappings id_image => id_attribute
     */
    public static function saveMappings(int $idPack, array $mappings): bool
    {
        self::ensureTable();
        $db = Db::getInstance();
        $db->delete(self::TABLE, 'id_pack = ' . (int) $idPack);

        foreach ($mappings as $idImage => $idAttribute) {
            $idImage = (int) $idImage;
            $idAttribute = (int) $idAttribute;
            if ($idImage <= 0) {
                continue;
            }
            $db->insert(self::TABLE, [
                'id_image' => $idImage,
                'id_pack' => (int) $idPack,
                'id_attribute' => $idAttribute,
            ]);
        }

        Cache::clean('PackGalleryColorHelper_manual_v1_' . $idPack);

        return true;
    }

    /** @return array<int, array{name: string, hex: string}> */
    public static function getAvailableColors(int $idPack): array
    {
        $idLang = (int) Context::getContext()->language->id;
        $products = AdvancedPack::getPackContent($idPack) ?: [];

        foreach ($products as $p) {
            $idProduct = (int) ($p['id_product'] ?? 0);
            if ($idProduct <= 0) {
                continue;
            }
            $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT DISTINCT a.id_attribute, al.name, a.color
                 FROM ' . _DB_PREFIX_ . 'product_attribute pa
                 INNER JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac
                    ON pac.id_product_attribute = pa.id_product_attribute
                 INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute
                 INNER JOIN ' . _DB_PREFIX_ . 'attribute_group ag ON ag.id_attribute_group = a.id_attribute_group
                 INNER JOIN ' . _DB_PREFIX_ . 'attribute_lang al
                    ON al.id_attribute = a.id_attribute AND al.id_lang = ' . (int) $idLang . '
                 WHERE pa.id_product = ' . (int) $idProduct . '
                   AND (ag.group_type = \'color\' OR ag.is_color_group = 1)
                 ORDER BY a.position ASC'
            ) ?: [];
            $attrs = [];
            foreach ($rows as $row) {
                $attrs[(int) $row['id_attribute']] = [
                    'name' => (string) $row['name'],
                    'hex' => (string) $row['color'],
                ];
            }
            // 1er produit du kit (ordre position) qui a un groupe couleur
            if ($attrs) {
                return $attrs;
            }
        }

        return [];
    }

    public static function getSelectedColorId(array $productsPack, array $packAttributesList = []): int
    {
        foreach ($productsPack as $p) {
            $idPa = (int) ($packAttributesList[(int) ($p['id_product_pack'] ?? 0)] ?? $p['id_product_attribute'] ?? 0);
            if ($idPa > 0) {
                $colors = (new Combination($idPa))->getColorsAttributes();
                if (!empty($colors[0]['id_attribute'])) {
                    return (int) $colors[0]['id_attribute'];
                }
            }
            foreach ($p['attributes']['groups'] ?? [] as $g) {
                if (($g['group_type'] ?? '') === 'color' || !empty($g['is_color_group'])) {
                    return (int) ($g['default'] ?? 0);
                }
            }
        }

        return 0;
    }
}
