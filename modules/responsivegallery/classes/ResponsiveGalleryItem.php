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
class ResponsiveGalleryItem extends ObjectModel
{

    const TABLE_ITEM = 'responsive_gallery_item';
    public $id;
    public $id_responsive_gallery_gallery;
    public $position;
    public $legend_on_hover;
    public $active;
    public $image;

    public $legend;
    public $link;
    public $alt;

    public static $definition = array(
        'table' => 'responsive_gallery_item',
        'primary' => 'id_responsive_gallery_item',
        'multilang' => true,
        'fields' => array(
            'id_responsive_gallery_gallery' => array('type' => self::TYPE_INT, 'required' => true, 'lang' => false),
            'position' => array('type' => self::TYPE_INT, 'required' => false, 'lang' => false),
            'legend_on_hover' => array('type' => self::TYPE_BOOL, 'required' => false, 'lang' => false),
            'active' => array('type' => self::TYPE_BOOL),
            'image' => array('type' => self::TYPE_STRING, 'lang' => false, 'validate' => 'isAnything'),

            //fields multi-lang
            'legend' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isAnything'),
            'link' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isAnything'),
            'alt' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isAnything'),
        ),
    );

    public static function installTables()
    {
        $result = true;
        $result &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_item` (
            `id_responsive_gallery_item` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_responsive_gallery_gallery` INT(10) UNSIGNED NOT NULL,
            `position` INT(10) UNSIGNED NOT NULL,
            `legend_on_hover` int(1) NOT NULL,
            `active` int(1) NOT NULL,
            `image` VARCHAR(255) NOT NULL,
		PRIMARY KEY (`id_responsive_gallery_item`)
		) ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        );

        $result &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_item_lang` (
            `id_responsive_gallery_item` int(10) unsigned NOT NULL ,
            `id_lang` int(10) unsigned NOT NULL ,
            `link` VARCHAR(255) NOT NULL,
            `legend` VARCHAR(255) NOT NULL,
            `alt` VARCHAR(255) NOT NULL,
		PRIMARY KEY (`id_responsive_gallery_item`,`id_lang`)
		) ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        );
        return $result;
    }

    public static function uninstallTable()
    {
        $result = true;
        $result &= Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_item_lang`'
        );
        $result &= Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'responsive_gallery_item`'
        );
        return $result;
    }

    public static function getNameImageById($id_item)
    {
        $sql = 'SELECT image
                FROM ' . _DB_PREFIX_ . 'responsive_gallery_item
                WHERE id_responsive_gallery_item = ' . (int)$id_item;

        return Db::getInstance()->getValue($sql);
    }

    public function getLastPosition()
    {
        return self::getLastPositionStatic($this->id_responsive_gallery_gallery);
    }

    public static function getLastPositionStatic($id_gallery)
    {
        //pour réordonner correctement les items
        self::reOrderAllItemsInAllGalleries();
        $sql = 'SELECT max(position) as maxposition, count(position) as countposition
                FROM ' . _DB_PREFIX_ . 'responsive_gallery_item 
                WHERE id_responsive_gallery_gallery = ' . (int)$id_gallery . '
                ORDER BY position DESC';
        $result = Db::getInstance()->getRow($sql);
        if ($result['countposition']) {
            return (int)$result['maxposition'] + 1 ;
        } else {
            return 0;
        }
    }

    public function setLastPosition()
    {
        $this->position = $this->getLastPosition();
    }

    public function updatePosition($new_position, $old_position, $way)
    {
        if ($way) {
            $sql_update_position = 'UPDATE ' . _DB_PREFIX_ . 'responsive_gallery_item
                                SET position = position - 1
                                WHERE id_responsive_gallery_gallery = ' . (int)$this->id_responsive_gallery_gallery . '
                                AND position <= ' . (int)$new_position . '
                                AND position > ' . (int)$old_position;
        } else {
            $sql_update_position = 'UPDATE ' . _DB_PREFIX_ . 'responsive_gallery_item
                                SET position = position + 1
                                WHERE id_responsive_gallery_gallery = ' . (int)$this->id_responsive_gallery_gallery . '
                                AND position >= ' . (int)$new_position . '
                                AND position < ' . (int)$old_position;
        }
        $this->position = $new_position;
        //pour réordonner correctement les items
        return Db::getInstance()->execute($sql_update_position) &&
            $this->save() &&
            self::reOrderAllItemsInAllGalleries();
    }

    public static function reOrderAllItemsInAllGalleries()
    {
        $all_galleries = ResponsiveGalleryGallery::getGalleries();
        foreach ($all_galleries as $gallery) {
            $id_gallery = $gallery['id_responsive_gallery_gallery'];
            $sql_items_by_gallery = 'SELECT * 
                                FROM '._DB_PREFIX_.'responsive_gallery_item 
                                WHERE id_responsive_gallery_gallery = '.$id_gallery.'
                                ORDER BY position, id_responsive_gallery_item';
            $results = Db::getInstance()->executeS($sql_items_by_gallery);

            $position = 0;
            foreach ($results as $result) {
                $sql_update = 'UPDATE '._DB_PREFIX_.self::$definition['table'].' 
                            SET position = '.(int)$position.' 
                            WHERE id_responsive_gallery_item = '.(int)$result['id_responsive_gallery_item'];
                Db::getInstance()->execute($sql_update);
                $position += 1;
            }
        }
    }
}
