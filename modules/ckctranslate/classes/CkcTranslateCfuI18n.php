<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CKC Vianney — traductions FO des labels CFU (colonne form, non lang).
 */
class CkcTranslateCfuI18n
{
    public const TABLE = 'ckctranslate_ets_cfu_form';
    public const VIRTUAL_TYPE = 'ets_cfu_contact_form';

    /**
     * @return bool
     */
    public static function ensureTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
            `id_contact` INT UNSIGNED NOT NULL,
            `id_lang` INT UNSIGNED NOT NULL,
            `id_shop` INT UNSIGNED NOT NULL DEFAULT 0,
            `payload` MEDIUMTEXT NOT NULL,
            PRIMARY KEY (`id_contact`, `id_lang`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4';

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * @return array{table: string, id_column: string, form_column: string, has_id_shop: bool}|null
     */
    public static function getContactFormMeta()
    {
        static $meta = false;
        if ($meta !== false) {
            return $meta;
        }

        $table = 'ets_cfu_contact';
        if (!self::tableExists($table)) {
            $meta = null;

            return $meta;
        }

        $formColumn = '';
        foreach (array('form', 'form_content', 'form_html', 'contact_form') as $column) {
            if (self::columnExists($table, $column)) {
                $formColumn = $column;
                break;
            }
        }
        if ($formColumn === '') {
            $meta = null;

            return $meta;
        }

        $idColumn = self::columnExists($table, 'id_contact') ? 'id_contact' : '';
        if ($idColumn === '') {
            $meta = null;

            return $meta;
        }

        $meta = array(
            'table' => $table,
            'id_column' => $idColumn,
            'form_column' => $formColumn,
            'has_id_shop' => self::columnExists($table, 'id_shop'),
        );

        return $meta;
    }

    /**
     * @param string $form
     *
     * @return string[]
     */
    public static function extractStrings($form)
    {
        $form = (string) $form;
        $found = array();
        $add = static function ($value) use (&$found) {
            $value = html_entity_decode(trim(preg_replace('/\s+/u', ' ', (string) $value)), ENT_QUOTES, 'UTF-8');
            if ($value === '' || Tools::strlen($value) > 300 || preg_match('/^[\d.\s*:+_\-\/,%]+$/u', $value)) {
                return;
            }
            if (preg_match('/^(https?:\/\/|www\.)/i', $value) || strpos($value, '@') !== false) {
                return;
            }
            if (preg_match('/^[a-z]+-?\d+$/i', $value)) {
                return;
            }
            if (preg_match('/^\.[a-z0-9]+(,\s*\.?[a-z0-9]+)+$/i', $value)) {
                return;
            }
            $found[$value] = true;
        };

        if (preg_match_all('/ets_cfu_span[^>]*>\s*(.*?)\s*<\/span>/is', $form, $matches)) {
            foreach ($matches[1] as $html) {
                $add(strip_tags($html));
            }
        }
        if (preg_match_all('/<label\b[^>]*>(.*?)<\/label>/is', $form, $matches)) {
            foreach ($matches[1] as $html) {
                $add(strip_tags($html));
            }
        }
        if (preg_match_all('/<option\b[^>]*>\s*(.*?)\s*<\/option>/is', $form, $matches)) {
            foreach ($matches[1] as $html) {
                $add(strip_tags($html));
            }
        }
        if (preg_match_all('/<input\b[^>]*\btype=["\']submit["\'][^>]*>/is', $form, $matches)) {
            foreach ($matches[0] as $tag) {
                if (preg_match('/\bvalue=["\']([^"\']+)["\']/i', $tag, $valueMatch)) {
                    $add($valueMatch[1]);
                }
            }
        }
        if (preg_match_all('/\[acceptance[^\]]*\](.*?)\[\/acceptance\]/is', $form, $matches)) {
            foreach ($matches[1] as $html) {
                $add(strip_tags($html));
            }
        }
        if (preg_match_all('/\[[a-z*][^\]]*\]/i', $form, $shortcodes)) {
            foreach ($shortcodes[0] as $shortcode) {
                if (preg_match_all('/"([^"]+)"/', $shortcode, $quoted)) {
                    foreach ($quoted[1] as $text) {
                        if (preg_match('/^(class|id|minlength|maxlength|watermark|placeholder|optional|limit|filetypes|accept|size):/i', $text)) {
                            continue;
                        }
                        $add($text);
                    }
                }
            }
        }
        if (preg_match_all('/(?:placeholder|watermark)\s*[:=]\s*"([^"]+)"/i', $form, $matches)) {
            foreach ($matches[1] as $text) {
                $add($text);
            }
        }

        return array_keys($found);
    }

    /**
     * @param int $idContact
     * @param int $idLang
     * @param int $idShop
     * @param array<string, string> $map
     *
     * @return bool
     */
    public static function saveMap($idContact, $idLang, $idShop, array $map)
    {
        self::ensureTable();
        $idContact = (int) $idContact;
        $idLang = (int) $idLang;
        $idShop = (int) $idShop;
        if ($idContact <= 0 || $idLang <= 0 || empty($map)) {
            return false;
        }

        $json = json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            return false;
        }

        $payload = array(
            'id_contact' => $idContact,
            'id_lang' => $idLang,
            'id_shop' => $idShop,
            'payload' => pSQL($json, true),
        );
        $where = 'id_contact = ' . $idContact
            . ' AND id_lang = ' . $idLang
            . ' AND id_shop = ' . $idShop;
        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_contact FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE ' . $where
        );
        if ($exists) {
            return (bool) Db::getInstance()->update(self::TABLE, array('payload' => $payload['payload']), $where);
        }

        return (bool) Db::getInstance()->insert(self::TABLE, $payload);
    }

    /**
     * @param int $idContact
     * @param int $idLang
     * @param int $idShop
     *
     * @return array<string, string>
     */
    public static function getMap($idContact, $idLang, $idShop)
    {
        self::ensureTable();
        $idContact = (int) $idContact;
        $idLang = (int) $idLang;
        $idShop = (int) $idShop;
        if ($idContact <= 0 || $idLang <= 0) {
            return array();
        }

        $row = Db::getInstance()->getRow(
            'SELECT payload FROM `' . _DB_PREFIX_ . self::TABLE . '`
             WHERE id_contact = ' . $idContact . '
               AND id_lang = ' . $idLang . '
               AND id_shop IN (0, ' . $idShop . ')
             ORDER BY id_shop DESC'
        );
        if (!$row || empty($row['payload'])) {
            return array();
        }
        $map = json_decode((string) $row['payload'], true);

        return is_array($map) ? $map : array();
    }

    /**
     * @param int $idContact
     * @param int $idLang
     * @param int $idShop
     *
     * @return bool
     */
    public static function hasMap($idContact, $idLang, $idShop)
    {
        return !empty(self::getMap($idContact, $idLang, $idShop));
    }

    /**
     * @param string $html
     * @param int    $idLang
     * @param int    $idShop
     *
     * @return string
     */
    public static function replaceInHtml($html, $idLang, $idShop)
    {
        $html = (string) $html;
        if ($html === '' || strpos($html, 'ets-cfu-form') === false) {
            return $html;
        }

        return preg_replace_callback(
            '/<form\b[^>]*\bets-cfu-form\b[^>]*>.*?<\/form>/is',
            static function (array $match) use ($idLang, $idShop) {
                $formHtml = $match[0];
                $idContact = 0;
                if (preg_match('/\bdata-id=["\'](\d+)["\']/', $formHtml, $idMatch)) {
                    $idContact = (int) $idMatch[1];
                }
                if ($idContact <= 0) {
                    return $formHtml;
                }
                $map = self::getMap($idContact, (int) $idLang, (int) $idShop);
                if (empty($map)) {
                    return $formHtml;
                }

                return self::applyMap($formHtml, $map);
            },
            $html
        );
    }

    /**
     * @param string               $html
     * @param array<string, string> $map
     *
     * @return string
     */
    public static function applyMap($html, array $map)
    {
        $keys = array_keys($map);
        usort($keys, static function ($a, $b) {
            return Tools::strlen($b) - Tools::strlen($a);
        });
        foreach ($keys as $from) {
            $to = (string) $map[$from];
            if ($from === '' || $to === '' || $from === $to) {
                continue;
            }
            $html = str_replace($from, $to, $html);
            $encoded = htmlspecialchars($from, ENT_QUOTES, 'UTF-8');
            if ($encoded !== $from) {
                $html = str_replace($encoded, htmlspecialchars($to, ENT_QUOTES, 'UTF-8'), $html);
            }
        }

        return $html;
    }

    /**
     * @param string $table
     *
     * @return bool
     */
    protected static function tableExists($table)
    {
        $table = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $table));
        if ($table === '') {
            return false;
        }
        $like = str_replace('_', '\\_', _DB_PREFIX_ . $table);

        return (bool) Db::getInstance()->executeS('SHOW TABLES LIKE "' . $like . '"');
    }

    /**
     * @param string $table
     * @param string $column
     *
     * @return bool
     */
    protected static function columnExists($table, $column)
    {
        $table = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $table));
        $column = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $column));
        if ($table === '' || $column === '') {
            return false;
        }
        $columnLike = str_replace('_', '\\_', $column);

        return (bool) Db::getInstance()->executeS(
            'SHOW COLUMNS FROM `' . _DB_PREFIX_ . bqSQL($table) . '` LIKE "' . $columnLike . '"'
        );
    }
}
