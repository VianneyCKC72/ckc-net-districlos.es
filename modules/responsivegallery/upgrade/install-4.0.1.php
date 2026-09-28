<?php
/**
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 */

function upgradeModule401($module)
{

    //ajout des tabs pour controllers backoffice
    deleteOldtab();
    if (Tools::version_compare(_PS_VERSION_, '1.7', '>')) {
        addnewtab17();
    } else {
        addNewTab();
    }

    addMetaSeoUrl();
    deleteOldFile();

    // mettre à jours les tables de gallery
    createTablesGallery();
    insertDataGallery();

    // mettre à jours les tables items
    createTablesItem();
    insertDataItem();

    //mettre à jours la table de configuration
    return true;
}

function addMetaSeoUrl()
{
    $o_meta = new Meta();
    $o_meta->page = 'module-responsivegallery-galleries';
    $o_meta->save();

    $o_meta = new Meta();
    $o_meta->page = 'module-responsivegallery-galleryitems';
    $o_meta->save();
}

function deleteOldFile()
{
    $path_module = _PS_MODULE_DIR_ . 'responsivegallery' . DIRECTORY_SEPARATOR;
    unlink(
        $path_module . implode(
            DIRECTORY_SEPARATOR,
            array('controllers', 'admin', 'AdminResponsiveGalleryController.php')
        )
    );
    unlink(
        $path_module . implode(
            DIRECTORY_SEPARATOR,
            array('controllers', 'admin', 'AdminResponsiveGalleryMultiUploadController.php')
        )
    );
}

function deleteOldTab()
{
    $id_tab = Tab::getIdFromClassName('AdminResponsiveGalleryMultiUpload');
    if ($id_tab != 0) {
        $tab = new Tab($id_tab);
        $tab->delete();
    }

    $id_tab = Tab::getIdFromClassName('AdminResponsiveGalleryGallery');
    if ($id_tab != 0) {
        $tab = new Tab($id_tab);
        $tab->delete();
    }

    $id_tab = Tab::getIdFromClassName('AdminResponsiveGallery');
    if ($id_tab != 0) {
        $tab = new Tab($id_tab);
        $tab->delete();
    }
}

function addNewTab()
{
    $tab = new Tab();
    $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
    $tab->name[$id_lang] = 'Manage Gallery';
    $tab->class_name = 'AdminResponsiveGalleryGallery';
    $tab->id_parent = Tab::getIdFromClassName('AdminParentModules');
    $tab->module = 'responsivegallery';
    $tab->add();

    $tab = new Tab();
    $tab->name[$id_lang] = 'Manage Image Gallery';
    $tab->class_name = 'AdminResponsiveGalleryItem';
    $tab->id_parent = Tab::getIdFromClassName('AdminParentModules');
    $tab->module = 'responsivegallery';
    $tab->add();

    $tab = new Tab();
    $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
    $tab->name[$id_lang] = 'Manage Gallery Multi upload';
    $tab->class_name = 'AdminResponsiveGalleryItemMultiUpload';
    $tab->id_parent = Tab::getIdFromClassName('AdminResponsiveGalleryItem');
    $tab->module = 'responsivegallery';
    $tab->add();
}

function addnewtab17()
{
    $tab = new Tab();
    $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
    $tab->name[$id_lang] = 'Galleries photo';
    $tab->class_name = 'AdminResponsiveGallery';
    $tab->id_parent = Tab::getIdFromClassName('IMPROVE');
    $tab->module = 'responsivegallery';
    $tab->icon = 'image';
    $tab->add();

    $tab = new Tab();
    $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
    $tab->name[$id_lang] = 'Manage Gallery';
    $tab->class_name = 'AdminResponsiveGalleryGallery';
    $tab->id_parent = Tab::getIdFromClassName('AdminResponsiveGallery');
    $tab->module = 'responsivegallery';
    $tab->add();

    $tab = new Tab();
    $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
    $tab->name[$id_lang] = 'Manage Image Gallery';
    $tab->class_name = 'AdminResponsiveGalleryItem';
    $tab->id_parent = Tab::getIdFromClassName('AdminResponsiveGallery');
    $tab->module = 'responsivegallery';
    $tab->add();

    $tab = new Tab();
    $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
    $tab->name[$id_lang] = 'Manage Gallery Multi upload';
    $tab->class_name = 'AdminResponsiveGalleryItemMultiUpload';
    $tab->id_parent = Tab::getIdFromClassName('AdminResponsiveGallery');
    $tab->module = 'responsivegallery';
    $tab->active = 0;
    $tab->add();
}

function createTablesGallery()
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
}

function insertDataGallery()
{
    $sql_all_infos = 'SELECT rg.*, rgl.*, rgs.*
					  FROM ' . _DB_PREFIX_ . 'responsivegallery_gallery_shop rgs
					  LEFT JOIN ' . _DB_PREFIX_ . 'responsivegallery_gallery_lang rgl
					  ON rgl.id_gallery = rgs.id_gallery
					  LEFT JOIN ' . _DB_PREFIX_ . 'responsivegallery_gallery rg
					  ON rg.id_gallery = rgl.id_gallery
					  ORDER BY rg.id_gallery';
    $all_infos = Db::getInstance()->executeS($sql_all_infos);

    foreach ($all_infos as $info) {
        // modif en BD
        insertRowGallery($info);
        // modif des images sur serveur
        renameImage($info, 'gallery', 'galleries');
    }
}

function insertRowGallery($info)
{
    //copie de la table shop
    insertPrimaryTableGallery($info);
    insertShopTableGallery($info);
    insertLangTableGallery($info);
}

function insertPrimaryTableGallery($info)
{
    $sql_is_insert = 'SELECT *
					  FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery
					  WHERE id_responsive_gallery_gallery = ' . $info['id_gallery'];

    $is_insert = Db::getInstance()->executeS($sql_is_insert);

    if (!$is_insert) {
        $filename = '';
        if (isset($info['image']) && !empty($info['image'])) {
            $filename = Tools::substr($info['image'], strrpos($info['image'], '/') + 1);
        }

        $sql_insert = 'INSERT INTO ' . _DB_PREFIX_ . 'responsive_gallery_gallery
		(
			id_responsive_gallery_gallery,
		 	id_shop_default,
		    active,
		    legend_on_photo,
		    horizontal_margin,
		    vertical_margin,
		    fadein_speed,
		    nb_item_per_page,
		    breakpoint_1,
		    nb_item_per_line_1,
		    breakpoint_2,
		    nb_item_per_line_2,
		    breakpoint_3,
		    nb_item_per_line_3,
		    breakpoint_4,
		    nb_item_per_line_4,
		    breakpoint_5,
		    nb_item_per_line_5,
		    image
		)
	    VALUES
		(
			' . $info['id_gallery'] . ',
		 	' . $info['id_shop_default'] . ',
		    ' . $info['active'] . ',
		    ' . $info['legend_on_photo'] . ',
		    ' . $info['horizontal_margin'] . ',
		    ' . $info['vertical_margin'] . ',
		    ' . $info['fadein_speed'] . ' ,
		    ' . $info['nb_item_per_page'] . ' ,
		    ' . $info['breakpoint_1'] . ' ,
		    ' . $info['nb_item_per_line_1'] . ' ,
		    ' . $info['breakpoint_2'] . ' ,
		    ' . $info['nb_item_per_line_2'] . ' ,
		    ' . $info['breakpoint_3'] . ' ,
		    ' . $info['nb_item_per_line_3'] . ' ,
		    ' . $info['breakpoint_4'] . ' ,
		    ' . $info['nb_item_per_line_4'] . ' ,
		    ' . $info['breakpoint_5'] . ' ,
		    ' . $info['nb_item_per_line_5'] . ' ,
		    "' . $filename . '"
	    )';
        Db::getInstance()->execute($sql_insert);
    }
}

function insertShopTableGallery($info)
{
    $sql_is_insert = 'SELECT *
					  FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop
					  WHERE id_responsive_gallery_gallery = ' . $info['id_gallery'] . '
					  AND id_shop = ' . $info['id_shop'];
    $is_insert = Db::getInstance()->executeS($sql_is_insert);

    if (!$is_insert) {
        $sql_insert = 'INSERT INTO ' . _DB_PREFIX_ . 'responsive_gallery_gallery_shop
		(
			id_responsive_gallery_gallery,
		 	id_shop,
		    position
		)
	    VALUES
		(
		 	' . $info['id_gallery'] . ',
		    ' . $info['id_shop'] . ',
		    ' . $info['position'] . '
	    )';
        Db::getInstance()->execute($sql_insert);
    }
}

function insertLangTableGallery($info)
{
    $sql_is_insert = 'SELECT *
					  FROM ' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang
					  WHERE id_responsive_gallery_gallery = ' . $info['id_gallery'] . '
					  AND id_lang = ' . $info['id_lang'];
    $is_insert = Db::getInstance()->executeS($sql_is_insert);

    if (!$is_insert) {
        $sql_insert = 'INSERT INTO ' . _DB_PREFIX_ . 'responsive_gallery_gallery_lang
		(
			id_responsive_gallery_gallery,
		 	id_shop,
		    id_lang,
			title,
		 	description,
		    alt
		)
	    VALUES
		(
		 	' . $info['id_gallery'] . ',
		    ' . $info['id_shop'] . ',
		    ' . $info['id_lang'] . ',
		   	"' . $info['title'] . '",
	    	"' . $info['preamble'] . '",
		    ""
	    )';
        Db::getInstance()->execute($sql_insert);
    }
}

function renameImage($info, $old_directory, $new_directory)
{
    $upload_path_old = _PS_MODULE_DIR_ . implode(
        DIRECTORY_SEPARATOR,
        array('responsivegallery', 'views', 'img', 'uploads', $old_directory, '')
    );

    $upload_path_new = _PS_MODULE_DIR_ . implode(
        DIRECTORY_SEPARATOR,
        array('responsivegallery', 'views', 'img', $new_directory, '')
    );

    $extension = Tools::substr($info['image'], strrpos($info['image'], '.') + 1);
    $filename = str_replace('.' . $extension, '', Tools::substr($info['image'], strrpos($info['image'], '/') + 1));

    if ($old_directory == 'item') {
        ImageManager::resize(
            $upload_path_old . $filename . '.' . $extension,
            $upload_path_new . $info['id_item'] . '-' . $filename . '.' . $extension,
            null,
            null,
            $extension
        );

        ImageManager::resize(
            $upload_path_new . $info['id_item'] . '-' . $filename . '.' . $extension,
            $upload_path_new . 'thumb-' . $info['id_item'] . '-' . $filename . '.' . $extension,
            200,
            150,
            $extension
        );
    } else {
        ImageManager::resize(
            $upload_path_old . $info['id_gallery'] . '_original.' . $extension,
            $upload_path_new . $info['id_gallery'] . '-' . $filename . '.' . $extension,
            null,
            null,
            $extension
        );

        ImageManager::resize(
            $upload_path_new . $info['id_gallery'] . '-' . $filename . '.' . $extension,
            $upload_path_new . 'thumb-' . $info['id_gallery'] . '-' . $filename . '.' . $extension,
            200,
            150,
            $extension
        );
    }
}

function createTablesItem()
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
}

function insertDataItem()
{
    $sql_all_infos = 'SELECT rgi.*, rgil.*, rgis.*
					  FROM ' . _DB_PREFIX_ . 'responsivegallery_item_shop rgis
					  LEFT JOIN ' . _DB_PREFIX_ . 'responsivegallery_item_lang rgil
					  ON rgil.id_item = rgis.id_item AND rgil.id_shop = rgis.id_shop
					  LEFT JOIN ' . _DB_PREFIX_ . 'responsivegallery_item rgi
					  ON rgi.id_item = rgil.id_item
					  ORDER BY rgi.id_item';
    $all_infos = Db::getInstance()->executeS($sql_all_infos);

    foreach ($all_infos as $info) {
        // modif en BD
        insertRowItem($info);
        // modif des images sur serveur
        renameImage($info, 'item', 'items');
    }
}

function insertRowItem($info)
{
    insertPrimaryTableItem($info);
    insertLangTableItem($info);
}

function insertPrimaryTableItem($info)
{
    $sql_is_insert = 'SELECT *
					  FROM ' . _DB_PREFIX_ . 'responsive_gallery_item
					  WHERE id_responsive_gallery_item = ' . $info['id_item'];
    $is_insert = Db::getInstance()->executeS($sql_is_insert);

    if (!$is_insert) {
        $filename = '';
        if (isset($info['image']) && !empty($info['image'])) {
            $filename = Tools::substr($info['image'], strrpos($info['image'], '/') + 1);
        }

        $sql_insert = 'INSERT INTO ' . _DB_PREFIX_ . 'responsive_gallery_item
		(
			id_responsive_gallery_item,
		 	id_responsive_gallery_gallery,
		    position,
		    legend_on_hover,
		    active,
		    image
		)
	    VALUES
		(
			' . $info['id_item'] . ',
		 	' . $info['id_gallery'] . ',
		    ' . $info['position'] . ',
		    ' . $info['legend_on_hover'] . ',
		    ' . $info['active'] . ',
		    "' . $filename . '"
	    )';
        Db::getInstance()->execute($sql_insert);
    }
}

function insertLangTableItem($info)
{
    $sql_is_insert = 'SELECT *
				  FROM ' . _DB_PREFIX_ . 'responsive_gallery_item_lang
				  WHERE id_responsive_gallery_item = ' . $info['id_item'] . '
				  AND id_lang = ' . $info['id_lang'];
    $is_insert = Db::getInstance()->executeS($sql_is_insert);

    if (!$is_insert) {
        $sql_insert = 'INSERT INTO ' . _DB_PREFIX_ . 'responsive_gallery_item_lang
		(
			id_responsive_gallery_item,
		 	id_lang,
		    link,
			legend,
		 	alt
		)
	    VALUES
		(
		 	' . $info['id_item'] . ',
		    ' . $info['id_lang'] . ',
		    "' . $info['link'] . '",
		   	"' . $info['legend'] . '",
	    	""
	    )';
        Db::getInstance()->execute($sql_insert);
    }
}
