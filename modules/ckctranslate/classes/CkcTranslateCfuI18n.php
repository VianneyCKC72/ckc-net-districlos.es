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

        // FO lit contact_lang.short_code (multilang), pas une colonne form partagée.
        if (self::columnExists('ets_cfu_contact_lang', 'short_code')) {
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
     * CKC Vianney — canvas BO : ets_cfu_label_{id_lang}, ets_cfu_short_code_{id_lang}, etc.
     *
     * @param string               $html
     * @param array<string, string> $map
     * @param int                  $sourceLangId
     * @param int                  $targetLangId
     *
     * @return string
     */
    public static function writeLangValuesIntoRenderForm($html, array $map, $sourceLangId, $targetLangId)
    {
        $html = (string) $html;
        $sourceLangId = (int) $sourceLangId;
        $targetLangId = (int) $targetLangId;
        if ($html === '' || empty($map) || $targetLangId <= 0) {
            return $html;
        }

        foreach (array('label', 'short_code', 'values', 'desc', 'content') as $key) {
            $targetClass = 'ets_cfu_' . $key . '_' . $targetLangId;
            $sourceClass = 'ets_cfu_' . $key . '_' . $sourceLangId;
            if (!preg_match('/class="[^"]*' . preg_quote($targetClass, '/') . '[^"]*"/', $html)) {
                $html = preg_replace_callback(
                    '/<span class="' . preg_quote($sourceClass, '/') . '"([^>]*)>(.*?)<\/span>/is',
                    static function (array $match) use ($targetClass, $map) {
                        $inner = CkcTranslateCfuI18n::applyMap($match[2], $map);

                        return $match[0] . '<span class="' . $targetClass . '"' . $match[1] . '>' . $inner . '</span>';
                    },
                    $html
                );
                continue;
            }
            $html = preg_replace_callback(
                '/(<span class="' . preg_quote($targetClass, '/') . '"[^>]*>)(.*?)(<\/span>)/is',
                static function (array $match) use ($map) {
                    return $match[1] . CkcTranslateCfuI18n::applyMap($match[2], $map) . $match[3];
                },
                $html
            );
        }

        return $html;
    }

    /**
     * @param int                  $idContact
     * @param array<string, string> $map
     * @param int                  $sourceLangId
     * @param int                  $targetLangId
     *
     * @return bool
     */
    public static function updateRenderForm($idContact, array $map, $sourceLangId, $targetLangId)
    {
        $idContact = (int) $idContact;
        if ($idContact <= 0 || empty($map) || !self::columnExists('ets_cfu_contact', 'render_form')) {
            return false;
        }
        $html = (string) Db::getInstance()->getValue(
            'SELECT render_form FROM `' . _DB_PREFIX_ . 'ets_cfu_contact`
             WHERE id_contact = ' . $idContact
        );
        if (trim($html) === '') {
            return false;
        }
        $updated = self::writeLangValuesIntoRenderForm($html, $map, $sourceLangId, $targetLangId);
        if ($updated === $html) {
            return true;
        }

        return (bool) Db::getInstance()->update(
            'ets_cfu_contact',
            array('render_form' => pSQL($updated, true)),
            'id_contact = ' . $idContact
        );
    }

    /**
     * CKC Vianney — écrit label_{id_lang} dans le HTML ETS (data-label) sans casser le FR.
     *
     * @param string               $form
     * @param array<string, string> $map
     * @param int                  $sourceLangId
     * @param int                  $targetLangId
     *
     * @return string
     */
    public static function writeLangValuesIntoForm($form, array $map, $sourceLangId, $targetLangId)
    {
        $form = (string) $form;
        $sourceLangId = (int) $sourceLangId;
        $targetLangId = (int) $targetLangId;
        if ($form === '' || $sourceLangId <= 0 || $targetLangId <= 0 || empty($map)) {
            return $form;
        }

        $form = preg_replace_callback(
            '/\b(data-(?:label|values|placeholder|defaultvalue|default_value|multi-lang|langs|submit))="([^"]*)"/i',
            static function (array $match) use ($map, $sourceLangId, $targetLangId) {
                $decoded = self::decodeLangMap($match[2]);
                if ($decoded === null) {
                    return $match[0];
                }
                $updated = self::fillLangMap($decoded, $map, $sourceLangId, $targetLangId);

                return $match[1] . '="' . self::encodeLangMap($updated) . '"';
            },
            $form
        );

        $form = preg_replace_callback(
            '/<span class="ets_cfu_span"([^>]*)>(.*?)<\/span>/is',
            static function (array $match) use ($map, $sourceLangId, $targetLangId) {
                $attrs = $match[1];
                $inner = $match[2];
                if (preg_match('/\bdata-label=/i', $attrs)) {
                    return $match[0];
                }
                $text = html_entity_decode(trim(strip_tags($inner)), ENT_QUOTES, 'UTF-8');
                if ($text === '' || !isset($map[$text])) {
                    return $match[0];
                }
                $langMap = array(
                    (string) $sourceLangId => $text,
                    (string) $targetLangId => (string) $map[$text],
                );

                return '<span class="ets_cfu_span" data-label="' . self::encodeLangMap($langMap) . '"' . $attrs . '>' . $inner . '</span>';
            },
            $form
        );

        $form = preg_replace_callback(
            '/<input\b([^>]*\btype=["\']submit["\'][^>]*)>/i',
            static function (array $match) use ($map, $sourceLangId, $targetLangId) {
                $tag = $match[0];
                if (preg_match('/\bdata-label=/i', $tag)) {
                    return $tag;
                }
                if (!preg_match('/\bvalue=["\']([^"\']+)["\']/', $tag, $valueMatch)) {
                    return $tag;
                }
                $text = html_entity_decode($valueMatch[1], ENT_QUOTES, 'UTF-8');
                if (!isset($map[$text])) {
                    return $tag;
                }
                $langMap = array(
                    (string) $sourceLangId => $text,
                    (string) $targetLangId => (string) $map[$text],
                );

                return rtrim(substr($tag, 0, -1)) . ' data-label="' . self::encodeLangMap($langMap) . '">';
            },
            $form
        );

        return $form;
    }

    /**
     * @param string $form
     * @param int    $targetLangId
     *
     * @return bool
     */
    public static function formNeedsEtsLangWrite($form, $targetLangId)
    {
        $targetLangId = (int) $targetLangId;
        if ((string) $form === '' || $targetLangId <= 0) {
            return false;
        }
        if (!preg_match_all('/<span class="ets_cfu_span"([^>]*)>(.*?)<\/span>/is', $form, $matches, PREG_SET_ORDER)) {
            return true;
        }
        foreach ($matches as $match) {
            if (!preg_match('/\bdata-label="([^"]*)"/i', $match[1], $attr)) {
                return true;
            }
            $decoded = self::decodeLangMap($attr[1]);
            if ($decoded === null) {
                return true;
            }
            $target = '';
            if (isset($decoded[$targetLangId])) {
                $target = trim((string) $decoded[$targetLangId]);
            } elseif (isset($decoded[(string) $targetLangId])) {
                $target = trim((string) $decoded[(string) $targetLangId]);
            }
            if ($target === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int                  $idContact
     * @param int                  $sourceLangId
     * @param int                  $targetLangId
     * @param int                  $idShop
     * @param array<string, string> $map
     *
     * @return bool
     */
    public static function writeLangValuesIntoAdditionalSettings($idContact, $sourceLangId, $targetLangId, $idShop, array $map)
    {
        $idContact = (int) $idContact;
        $sourceLangId = (int) $sourceLangId;
        $targetLangId = (int) $targetLangId;
        if ($idContact <= 0 || empty($map) || !self::columnExists('ets_cfu_contact_lang', 'additional_settings')) {
            return false;
        }

        $sourceRaw = (string) Db::getInstance()->getValue(
            'SELECT additional_settings FROM `' . _DB_PREFIX_ . 'ets_cfu_contact_lang`
             WHERE id_contact = ' . $idContact . '
               AND id_lang = ' . $sourceLangId
        );
        if (trim($sourceRaw) === '') {
            return false;
        }
        $tree = json_decode($sourceRaw, true);
        if (!is_array($tree)) {
            return false;
        }

        $updated = self::injectMapIntoSettings($tree, $map, $sourceLangId, $targetLangId);
        $json = json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            return false;
        }

        $where = 'id_contact = ' . $idContact . ' AND id_lang = ' . $targetLangId;
        $exists = (int) Db::getInstance()->getValue(
            'SELECT id_contact FROM `' . _DB_PREFIX_ . 'ets_cfu_contact_lang` WHERE ' . $where
        );
        if ($exists) {
            return (bool) Db::getInstance()->update(
                'ets_cfu_contact_lang',
                array('additional_settings' => pSQL($json, true)),
                $where
            );
        }

        return false;
    }

    /**
     * @param mixed                $node
     * @param array<string, string> $map
     * @param int                  $sourceLangId
     * @param int                  $targetLangId
     *
     * @return mixed
     */
    protected static function injectMapIntoSettings($node, array $map, $sourceLangId, $targetLangId)
    {
        if (is_string($node)) {
            return isset($map[$node]) ? $map[$node] : $node;
        }
        if (!is_array($node)) {
            return $node;
        }
        if (self::isLangKeyedArray($node)) {
            $sourceVal = '';
            if (isset($node[$sourceLangId])) {
                $sourceVal = $node[$sourceLangId];
            } elseif (isset($node[(string) $sourceLangId])) {
                $sourceVal = $node[(string) $sourceLangId];
            }
            if (is_string($sourceVal) && isset($map[$sourceVal])) {
                $node[$targetLangId] = $map[$sourceVal];
                $node[(string) $targetLangId] = $map[$sourceVal];
            } elseif (is_array($sourceVal)) {
                $node[$targetLangId] = self::injectMapIntoSettings($sourceVal, $map, $sourceLangId, $targetLangId);
            }

            return $node;
        }
        foreach ($node as $key => $value) {
            $node[$key] = self::injectMapIntoSettings($value, $map, $sourceLangId, $targetLangId);
        }

        return $node;
    }

    /**
     * @param array $node
     *
     * @return bool
     */
    protected static function isLangKeyedArray(array $node)
    {
        if ($node === array()) {
            return false;
        }
        foreach (array_keys($node) as $key) {
            if (!preg_match('/^\d+$/', (string) $key) || (int) $key <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed                $decoded
     * @param array<string, string> $map
     * @param int                  $sourceLangId
     * @param int                  $targetLangId
     *
     * @return array
     */
    protected static function fillLangMap($decoded, array $map, $sourceLangId, $targetLangId)
    {
        if (!is_array($decoded)) {
            return array();
        }
        $sourceVal = '';
        if (isset($decoded[$sourceLangId])) {
            $sourceVal = $decoded[$sourceLangId];
        } elseif (isset($decoded[(string) $sourceLangId])) {
            $sourceVal = $decoded[(string) $sourceLangId];
        }
        if (is_string($sourceVal) && isset($map[$sourceVal])) {
            $decoded[$targetLangId] = $map[$sourceVal];
            $decoded[(string) $targetLangId] = $map[$sourceVal];
        } elseif (is_array($sourceVal)) {
            $translated = array();
            foreach ($sourceVal as $item) {
                $translated[] = (is_string($item) && isset($map[$item])) ? $map[$item] : $item;
            }
            $decoded[$targetLangId] = $translated;
            $decoded[(string) $targetLangId] = $translated;
        }

        return $decoded;
    }

    /**
     * @param string $raw
     *
     * @return array|null
     */
    protected static function decodeLangMap($raw)
    {
        $raw = html_entity_decode((string) $raw, ENT_QUOTES, 'UTF-8');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        return self::isLangKeyedArray($decoded) ? $decoded : null;
    }

    /**
     * @param array $map
     *
     * @return string
     */
    protected static function encodeLangMap(array $map)
    {
        $json = json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return htmlspecialchars(is_string($json) ? $json : '{}', ENT_QUOTES, 'UTF-8');
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
