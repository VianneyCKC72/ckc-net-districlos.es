<?php
/**
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ResponsiveGalleryGallery extends ObjectModel
{

    public $id_shop_default;
    public $active;
    public $legend_on_photo;
    public $fadein_speed = 1000;
    public $horizontal_margin = 5;
    public $vertical_margin = 5;
    public $nb_item_per_page = 12;
    public $breakpoint_1 = 320;
    public $nb_item_per_line_1 = 1;
    public $breakpoint_2 = 480;
    public $nb_item_per_line_2 = 2;
    public $breakpoint_3 = 768;
    public $nb_item_per_line_3 = 3;
    public $breakpoint_4 = 992;
    public $nb_item_per_line_4 = 4;
    public $breakpoint_5 = 9999;
    public $nb_item_per_line_5 = 5;
    public $title;
    public $description;
    public $image;
    public $alt;

    public static $definition = array(
        'table' => 'responsive_gallery_gallery',
        'primary' => 'id_responsive_gallery_gallery',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => array(

            //not visible in form
            'id_shop_default' => array('type' => self::TYPE_INT, 'required' => false),

            //switch
            'active' => array('type' => self::TYPE_BOOL),
            //checkbox
            'legend_on_photo' => array('type' => self::TYPE_BOOL, 'lang' => false),

            //configuration image
            'horizontal_margin' => array('type' => self::TYPE_INT, 'lang' => false),
            'vertical_margin' => array('type' => self::TYPE_INT, 'lang' => false),
            'fadein_speed' => array('type' => self::TYPE_INT, 'lang' => false),

            //configuration breakpoint bootstrap
            'nb_item_per_page' => array('type' => self::TYPE_INT, 'lang' => false),
            'breakpoint_1' => array('type' => self::TYPE_INT, 'lang' => false),
            'nb_item_per_line_1' => array('type' => self::TYPE_INT, 'lang' => false),
            'breakpoint_2' => array('type' => self::TYPE_INT, 'lang' => false),
            'nb_item_per_line_2' => array('type' => self::TYPE_INT, 'lang' => false),
            'breakpoint_3' => array('type' => self::TYPE_INT, 'lang' => false),
            'nb_item_per_line_3' => array('type' => self::TYPE_INT, 'lang' => false),
            'breakpoint_4' => array('type' => self::TYPE_INT, 'lang' => false),
            'nb_item_per_line_4' => array('type' => self::TYPE_INT, 'lang' => false),
            'breakpoint_5' => array('type' => self::TYPE_INT, 'lang' => false),
            'nb_item_per_line_5' => array('type' => self::TYPE_INT, 'lang' => false),
            'image' => array('type' => self::TYPE_STRING, 'lang' => false),

            //field multi-lang
            'title' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'description' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'alt' => array('type' => self::TYPE_STRING, 'lang' => true),

        ),
    );

    public static function installTables()
    {
        $result = true;

        $result &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_gallery` (
            `id_responsive_gallery_gallery` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop_default` INT(10) UNSIGNED NOT NULL,
            `active` int(1) NOT NULL,
            `legend_on_photo` int(1) NOT NULL,
            `horizontal_margin` int(10) NOT NULL,
            `vertical_margin` int(10) NOT NULL,
            `fadein_speed` int(10) NOT NULL,
            `nb_item_per_page` int(10) NOT NULL,
            `breakpoint_1` int(10) NOT NULL,
            `nb_item_per_line_1` int(10) NOT NULL,
            `breakpoint_2` int(10) NOT NULL,
            `nb_item_per_line_2` int(10) NOT NULL,
            `breakpoint_3` int(10) NOT NULL,
            `nb_item_per_line_3` int(10) NOT NULL,
            `breakpoint_4` int(10) NOT NULL,
            `nb_item_per_line_4` int(10) NOT NULL,
            `breakpoint_5` int(10) NOT NULL,
            `nb_item_per_line_5` int(10) NOT NULL,
            `image` VARCHAR(255) NOT NULL,
		PRIMARY KEY (`id_responsive_gallery_gallery`)
		) ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        );

        $result &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop` (
            `id_responsive_gallery_gallery` int(10) unsigned NOT NULL ,
            `id_shop` int(10) unsigned NOT NULL ,
            `position` INT(10) UNSIGNED NOT NULL,
		PRIMARY KEY (`id_responsive_gallery_gallery`,`id_shop`)
		) ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        );

        $result &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang` (
            `id_responsive_gallery_gallery` int(10) unsigned NOT NULL ,
            `id_shop` int(10) unsigned NOT NULL ,
            `id_lang` int(10) unsigned NOT NULL ,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT NOT NULL,
            `alt` VARCHAR(255) NOT NULL,
		PRIMARY KEY (`id_responsive_gallery_gallery`,`id_shop`,`id_lang`)
		) ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        );

        return $result;
    }

    public static function uninstallTables()
    {
        $result = true;

        $result &= Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang`'
        );
        $result &= Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop`'
        );
        $result &= Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_gallery`'
        );

        return $result;
    }

    public static function getGalleries($active_gallery = false)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;

        $sql = 'SELECT *
                FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery rgg
                LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang rggl
                ON rgg.id_responsive_gallery_gallery = rggl.id_responsive_gallery_gallery
                LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop rggs
                ON (rggs.id_responsive_gallery_gallery = rggl.id_responsive_gallery_gallery
                AND rggl.id_shop = rggs.id_shop)
                WHERE rggl.id_lang = ' . (int)$id_lang . '
                AND rggs.id_shop = ' . (int)$id_shop .
            ((isset($active_gallery) && $active_gallery) ? ' AND rgg.active = ' . (int) $active_gallery : '') . '
                ORDER BY rggs.position';

        return Db::getInstance()->executeS($sql);
    }

    public static function getNameImageById($id_gallery)
    {
        $sql = 'SELECT rgg.image
                FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery rgg
                WHERE rgg.id_responsive_gallery_gallery = ' . (int)$id_gallery;

        return Db::getInstance()->getValue($sql);
    }

    /**
     * Récupère les éléments à afficher en fonction de la page demandée.
     *
     * Par défaut la fonction renvoie les éléments activés,
     * sinon on peut récupérer uniquement les éléments non activés.
     *
     * @param $page page demandée
     * @param null $size nombre d'éléments souhaité
     * @param bool $b_active_only indique si on recherche uniquement les éléments activés ou non
     * @return array tableau d'éléments pour la page demandée
     */
    public function getGalleryPage($page, $size = null, $b_active_only = true)
    {
        if ($size === null) {
            $size = $this->nb_item_per_page;
        }

        return $this->getGalleryItemRange(($page - 1) * $size, $size, $b_active_only);
    }

    /**
     * Récupère les éléments de la galerie en fonction de la position de départ,
     * du nombre d'éléments que l'on souhaite et si l'on souhaite uniquement les
     * éléments actifs.
     *
     *
     * Par défaut la fonction renvoie les éléments activés,
     * sinon on peut récupérer uniquement les éléments non activés.
     *
     * @param $start début du range souhaité
     * @param null $size taille du range
     * @param bool $b_active_only indique si on recherche uniquement les éléments activés ou non
     * @return array tableau des éléments
     */
    public function getGalleryItemRange($start, $size = null, $b_active_only = true, $id_lang = null)
    {
        if ($size === null) {
            $size = $this->nb_item_per_page;
        }
        if (!isset($id_lang)) {
            $id_lang = Context::getContext()->cookie->id_lang;
        }

        $req = 'SELECT rgi.*,rgil.*
                FROM ' . _DB_PREFIX_ . 'responsive_gallery_item rgi
                LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_item_lang rgil
                ON (rgi.id_responsive_gallery_item = rgil.id_responsive_gallery_item)
                WHERE rgil.id_lang = ' . (int)$id_lang . '
                AND rgi.id_responsive_gallery_gallery = ' . (int)$this->id .
            ($b_active_only ? ' AND rgi.active=1 ' : '');

        $req .= 'ORDER BY position '.(Configuration::get('RG_ORDER_ACS_IMAGE') ? 'ASC ':'DESC ')
        .' LIMIT ' . (int)$start . ',' . (int)$size;

        return Db::getInstance()->executeS($req);
    }

    public static function inThisShop($id_gallery)
    {
        $sql = 'SELECT rgg.id_responsive_gallery_gallery
               FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery rgg
               LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop rggs
               ON rgg.id_responsive_gallery_gallery = rggs.id_responsive_gallery_gallery
               WHERE rgg.id_responsive_gallery_gallery = ' . (int)$id_gallery . '
               AND rggs.id_shop = ' . (int)Context::getContext()->shop->id;

        return Db::getInstance()->getValue($sql);
    }

    public function assignMultipleConstant($context, $display_home = false)
    {
        if (Module::isEnabled('responsivegallery')) {
            $o_module = Module::getInstanceByName('responsivegallery');
            $id_shop = $context->shop->id;
            $id_lang = $context->language->id;
            if ($display_home) {
                $context->smarty->assign(
                    array(
                        'RG_TITLE' => Configuration::get('RG_TITLE_HOME', $id_lang, null, $id_shop),
                        'RG_DESCRIPTION' => Configuration::get('RG_DESCRIPTION_HOME', $id_lang, null, $id_shop),
                    )
                );
            } else {
                $context->smarty->assign(
                    array(
                        'RG_TITLE' => $this->title,
                        'RG_DESCRIPTION' => $this->description,
                    )
                );
            }
            $context->smarty->assign(
                array(
                    'current_gallery' => $this->id,
                    'RG_HORIZONTAL_MARGIN' => $this->horizontal_margin,
                    'RG_VERTICAL_MARGIN' => $this->vertical_margin,
                    'RG_FADEIN_SPEED' => $this->fadein_speed,
                    'RG_CURRENT_PAGE' => 1,
                    'RG_BREAKPOINT_1' => $this->breakpoint_1,
                    'RG_BREAKPOINT_2' => $this->breakpoint_2,
                    'RG_BREAKPOINT_3' => $this->breakpoint_3,
                    'RG_BREAKPOINT_4' => $this->breakpoint_4,
                    'RG_BREAKPOINT_5' => $this->breakpoint_5,
                    'RG_NB_ITEM_1' => $this->nb_item_per_line_1,
                    'RG_NB_ITEM_2' => $this->nb_item_per_line_2,
                    'RG_NB_ITEM_3' => $this->nb_item_per_line_3,
                    'RG_NB_ITEM_4' => $this->nb_item_per_line_4,
                    'RG_NB_ITEM_5' => $this->nb_item_per_line_5,
                    'RG_LEGEND_ON_PHOTO' => $this->legend_on_photo,
                    'ajax_link' => $o_module->getGalleryLink($this->id, true),
                )
            );
        }
    }

    public function updatePosition($new_position, $old_position, $way)
    {
        $id_shop = Context::getContext()->shop->id;
        if ($way) {
            $sql_update_position = 'UPDATE ' . _DB_PREFIX_ . 'responsive_gallery_gallery rgg
                                    LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop rggs
                                    ON rgg.id_responsive_gallery_gallery = rggs.id_responsive_gallery_gallery
                                    SET rggs.position = rggs.position - 1
                                    WHERE rggs.id_shop = ' . (int)$id_shop . '
                                    AND rggs.position <= ' . (int)$new_position . '
                                    AND rggs.position > ' . (int)$old_position;
        } else {
            $sql_update_position = 'UPDATE ' . _DB_PREFIX_ . 'responsive_gallery_gallery rgg
                                    LEFT JOIN ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop rggs
                                    ON rgg.id_responsive_gallery_gallery = rggs.id_responsive_gallery_gallery
                                    SET rggs.position = rggs.position + 1
                                    WHERE rggs.id_shop = ' . (int)$id_shop . '
                                    AND rggs.position >= ' . (int)$new_position . '
                                    AND rggs.position < ' . (int)$old_position;
        }

        $sql_update_gallery_position = 'UPDATE ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop
                                    SET position = ' . (int)$new_position . '
                                    WHERE id_shop = ' . (int)$id_shop . '
                                    AND id_responsive_gallery_gallery = ' . (int)$this->id;

        return Db::getInstance()->execute($sql_update_position) &&
            Db::getInstance()->execute($sql_update_gallery_position);
    }
}
