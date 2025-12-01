<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
class AdminAjaxCkcadcampaignconfiguratorController extends ModuleAdminController
{
    public function ajaxProcessSaveCampaignInfo(){

        $id = Tools::getValue("ID");
        $isActiveCampaign = Tools::getValue("isActiveCampaign");
        $titleCampaign = Tools::getValue("titleCampaign");
        $clientGroup = Tools::getValue("clientGroup");
        $startingDate = Tools::getValue("startingDate");
        $endingDate = Tools::getValue("endingDate");
        $logoPath = Tools::getValue("logoPath");

        if(isset($_FILES['logoFile'])) {
            $logoFile = $_FILES['logoFile'];
        }

        if(Tools::getValue("francoImpact") == "" OR null == Tools::getValue("francoImpact")){
            $francoImpact = 0;
        } else {
            $francoImpact = Tools::getValue("francoImpact");
        }

        if(Tools::getValue("francoImpactPro") == "" OR null == Tools::getValue("francoImpactPro")){
            $francoImpactPro = 0;
        } else {
            $francoImpactPro = Tools::getValue("francoImpactPro");
        }

        $isActiveMenu = Tools::getValue("isActiveMenu");
        $menuImgPath = Tools::getValue("menuImgPath");

        if(isset($_FILES['menuImgFile'])) {
            $menuImgFile = $_FILES['menuImgFile'];
        }
        $menuLink = Tools::getValue("menuLink");

        $isActiveHeader = Tools::getValue("isActiveHeader");
        $headertext = htmlentities(Tools::getValue("headertext"));
        $headerBackColor = Tools::getValue("headerBackColor");
        /*        $isActiveCategory = Tools::getValue("isActiveCategory");
                $categoryText = htmlentities(Tools::getValue("categoryText"));
                $categoryBackColor = Tools::getValue("categoryBackColor");
                $isAllCategories = Tools::getValue("isAllCategories");*/
        $devisText = htmlentities(Tools::getValue("devisText"));
        $devisColor = Tools::getValue("devisColor");
        $promoPageText = htmlentities(Tools::getValue("promoPageText"));
        /*        $selectedCategories = Tools::getValue("selectedCategories");*/

        if(isset($isActiveCampaign) && isset($titleCampaign) && isset($clientGroup) && isset($startingDate) && isset($endingDate)){

            if(isset($id)) {

                if(isset($logoPath) && isset($logoFile)) {
                    $file_name = $logoFile['name'];

                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);

                    $file_base_name = preg_replace('/[^\w\-]/u', '-', $file_base_name); // Remplace les caractères non alphanumériques
                    $file_base_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file_base_name); // Convertit les caractères accentués en non accentués

                    $file_name = $file_base_name . '.' . $file_extension;

                    $checkIfTheSameName = Db::getInstance()->getValue('SELECT `logo_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_list`
                    WHERE id = '.$id.' AND logo_path LIKE "'.$file_name.'"');
                    while ($checkIfTheSameName) {
                        $randomSuffix = rand(1000, 9999);
                        $newFileName = $file_base_name . '-' . $randomSuffix . '.' . $file_extension;

                        // Revérifier avec le nouveau nom de fichier
                        $checkIfTheSameName = Db::getInstance()->getValue(
                            'SELECT `logo_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_list`
                            WHERE id = '.$id.' AND logo_path LIKE "'.$newFileName.'"'
                        );
                    }

                    $file_name = isset($newFileName) ? $newFileName : $file_name;


                    if($file_name) {
                        if (is_uploaded_file($logoFile['tmp_name'])) {
                            // Vérifier si le dossier 'uploads' existe, sinon le créer
                            $uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'campaigns';
                            if (!file_exists($uploadsDir)) {
                                mkdir($uploadsDir, 0755, true); //
                            }
                            $moveResult = move_uploaded_file($logoFile['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $file_name);

                            if ($moveResult) {
                                $updatelogo = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_list` 
                                SET `logo_path` = "'.$file_name.'" WHERE id = '.$id);
                            } else {
                                throw new Exception('Le document n\'a pas pu être enregistré.');
                            }
                        } else {
                            throw new FileNotFoundException('Error while file upload');
                        }
                    }
                }


                $updateCampaign = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_list` 
                SET `title` = "'.$titleCampaign.'", `id_client_group` = \''.$clientGroup.'\', `active` = '.$isActiveCampaign.', `franco_impact` = NULLIF('.$francoImpact.', 0), `franco_impact_pro` = NULLIF('.$francoImpactPro.', 0), `date_start` = "'.$startingDate.'", `date_end` = "'.$endingDate.'" WHERE id = '.$id);
            }

        }


        if(isset($isActiveMenu) && isset($menuImgPath) && isset($menuImgFile)){

            $queryMenu = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_menu ccl
            WHERE ccl.id_campaign = '.$id);

            if(null != $queryMenu) {
                $file_name = $menuImgFile['name'];

                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);

                $file_base_name = preg_replace('/[^\w\-]/u', '-', $file_base_name); // Remplace les caractères non alphanumériques
                $file_base_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file_base_name); // Convertit les caractères accentués en non accentués

                $file_name = $file_base_name . '.' . $file_extension;

                $checkIfTheSameName = Db::getInstance()->getValue('SELECT `img_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu`
                WHERE id = '.$id.' AND img_path LIKE "'.$file_name.'"');
                while ($checkIfTheSameName) {
                    $randomSuffix = rand(1000, 9999);
                    $newFileName = $file_base_name . '-' . $randomSuffix . '.' . $file_extension;

                    // Revérifier avec le nouveau nom de fichier
                    $checkIfTheSameName = Db::getInstance()->getValue(
                        'SELECT `img_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu`
                        WHERE id = '.$id.' AND img_path LIKE "'.$newFileName.'"'
                    );
                }

                $file_name = isset($newFileName) ? $newFileName : $file_name;


                if($file_name) {
                    if (is_uploaded_file($menuImgFile['tmp_name'])) {
                        // Vérifier si le dossier 'uploads' existe, sinon le créer
                        $uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'menus';
                        if (!file_exists($uploadsDir)) {
                            mkdir($uploadsDir, 0755, true); //
                        }
                        $moveResult = move_uploaded_file($menuImgFile['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $file_name);

                        if ($moveResult) {
                            $updatelogo = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` 
                            SET `img_path` = "'.$file_name.'" WHERE `id_campaign` = '.$id);
                        } else {
                            throw new Exception('Le document n\'a pas pu être enregistré.');
                        }
                    } else {
                        throw new FileNotFoundException('Error while file upload');
                    }
                }

                $updateMenu =  Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` 
                SET `active`  = ' . (int)$isActiveMenu . ', `link` = "'.$menuLink.'" WHERE  `id_campaign` = ' .$id);
            } else {

                $file_name = $menuImgFile['name'];

                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);

                $file_base_name = preg_replace('/[^\w\-]/u', '-', $file_base_name); // Remplace les caractères non alphanumériques
                $file_base_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file_base_name); // Convertit les caractères accentués en non accentués

                $file_name = $file_base_name . '.' . $file_extension;

                $checkIfTheSameName = Db::getInstance()->getValue('SELECT `img_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu`
                WHERE id = '.$id.' AND img_path LIKE "'.$file_name.'"');
                while ($checkIfTheSameName) {
                    $randomSuffix = rand(1000, 9999);
                    $newFileName = $file_base_name . '-' . $randomSuffix . '.' . $file_extension;

                    // Revérifier avec le nouveau nom de fichier
                    $checkIfTheSameName = Db::getInstance()->getValue(
                        'SELECT `img_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu`
                        WHERE id = '.$id.' AND img_path LIKE "'.$newFileName.'"'
                    );
                }

                $file_name = isset($newFileName) ? $newFileName : $file_name;


                if($file_name) {
                    if (is_uploaded_file($menuImgFile['tmp_name'])) {
                        // Vérifier si le dossier 'uploads' existe, sinon le créer
                        $uploadsDir =  __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'menus';
                        if (!file_exists($uploadsDir)) {
                            mkdir($uploadsDir, 0755, true); //
                        }
                        $moveResult = move_uploaded_file($menuImgFile['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $file_name);

                        if ($moveResult) {
                            $upload = true;
                        } else {
                            throw new Exception('Le document n\'a pas pu être enregistré.');
                        }
                    } else {
                        throw new FileNotFoundException('Error while file upload');
                    }
                }

                $createMenu = Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` (`id`, `id_campaign`, `img_path`, `active`, `link`, `date_add`)
                                VALUES (NULL, '.$id.', \''.$file_name.'\', '.(int)$isActiveMenu.', \''.$menuLink.'\', CURRENT_TIMESTAMP())'
                );
            }
        } elseif (isset($isActiveMenu) && isset($menuLink)){
            $updateMenu =  Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` 
            SET `active`  = ' . (int)$isActiveMenu . ', `link` = "'.$menuLink.'" WHERE  `id_campaign` = ' .$id);
        } elseif (isset($isActiveMenu)){
            $updateMenu =  Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` 
            SET `active`  = ' . (int)$isActiveMenu . ' WHERE  `id_campaign` = ' .$id);
        }

        if (isset($isActiveHeader) && isset($headertext) && isset($headerBackColor)) {
            $queryHeader = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_header ccl
            WHERE ccl.id_campaign = '.$id);

            if(null != $queryHeader) {
                $updateMenu =  Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_header` 
                SET `active`  = ' . (int)$isActiveHeader . ',
                    `text` = \'' . $headertext . '\',
                    `background_color` = \'' . $headerBackColor . '\'
                                    WHERE  `id_campaign` = ' .$id);
            } else {
                $createMenu = Db::getInstance()->execute(
                    'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_header` (`id`, `id_campaign`, `active`, `text`, `background_color`, `date_add`)
                                VALUES (NULL, '.$id.', '.(int)$isActiveHeader.', \''.$headertext.'\', \''.$headerBackColor.'\', CURRENT_TIMESTAMP())'
                );
            }
        }

        /*        if(isset($isActiveCategory) && isset($categoryText) && isset($categoryBackColor) && isset($isAllCategories)) {
                    $queryCategory = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_category ccl
                    WHERE ccl.id_campaign = '.$id);

                    if(null != $queryCategory) {
                        $updateMenu =  Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_category`
                        SET `text`  = \'' . $categoryText . '\',
                            `background_color`  = \'' . $categoryBackColor . '\',
                            `active`  = ' . (int)$isActiveCategory . ',
                            `show_all`  = ' . (int)$isAllCategories . '
                                            WHERE  `id_campaign` = ' .(int)$id);
                    } else {
                        $createMenu = Db::getInstance()->execute(
                            'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` (`id`, `id_campaign`, `text`, `background_color`, `active`,`show_all`, `date_add`)
                                        VALUES (NULL, '.$id.', \''.$categoryText.'\', \''.$categoryBackColor.'\',
                                        '.(int)$isActiveCategory.', '.(int)$isAllCategories.', CURRENT_TIMESTAMP())'
                        );
                    }
                }*/

        if (isset($devisText) && isset($devisColor)){
            $queryDevis = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_devis ccd
            WHERE ccd.id_campaign = '.$id);
            if(null != $queryDevis) {
                $updateDevis = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_devis` 
                SET `text`  = \'' . $devisText . '\',
                `background_color`  = \'' . $devisColor . '\'
                WHERE  `id_campaign` = ' .(int)$id);
            } else {
                $createDevis =  Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_devis` (`id`, `id_campaign`, `text`, `background_color`, `date_add`)
                                VALUES (NULL, '.$id.', \''.$devisText.'\', \''.$devisColor.'\', CURRENT_TIMESTAMP())');
            }
        }

        if(isset($promoPageText)){
            $queryPromoPage = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage ccpp
            WHERE ccpp.id_campaign = '.$id);
            if(null != $queryPromoPage) {
                $updatePromoPage = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage` 
                SET `text`  = \'' . $promoPageText . '\'
                WHERE  `id_campaign` = ' .(int)$id);
            } else {
                $createPromoPage = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage` (`id`, `id_campaign`, `text`, `date_add`)
                VALUES (NULL, '.$id.', \''.$promoPageText.'\', CURRENT_TIMESTAMP())');
            }
        }

        /*        if(isset($selectedCategories)) {
                    $deleteOld = Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'ckccampaign_config_category_show` WHERE id_campaign = '.$id);
                    if("" !== $selectedCategories) {
                        $selectedCatArr = explode(",",$selectedCategories);

                        foreach ($selectedCatArr as $category) {
                            $updateNew = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_category_show` (`id`, `id_campaign`, `id_category`)
                        VALUES (NULL, '.$id.', '.$category.')');
                        }
                    }
                }*/
        echo $id;

        exit();

    }

    public function ajaxProcessCreateCampaign(){

        $isActiveCampaign = Tools::getValue("isActiveCampaign");
        $titleCampaign = Tools::getValue("titleCampaign");
        $clientGroup = Tools::getValue("clientGroup");
        $startingDate = Tools::getValue("startingDate");
        $endingDate = Tools::getValue("endingDate");
        $logoPath = Tools::getValue("logoPath");

        if(isset($_FILES['logoFile'])) {
            $logoFile = $_FILES['logoFile'];
        }

        if(Tools::getValue("francoImpact") == "" OR null == Tools::getValue("francoImpact")){
            $francoImpact = 0;
        } else {
            $francoImpact = Tools::getValue("francoImpact");
        }

        if(Tools::getValue("francoImpactPro") == "" OR null == Tools::getValue("francoImpactPro")){
            $francoImpactPro = 0;
        } else {
            $francoImpactPro = Tools::getValue("francoImpactPro");
        }

        $isActiveMenu = Tools::getValue("isActiveMenu");
        $menuImgPath = Tools::getValue("menuImgPath");

        if(isset($_FILES['menuImgFile'])) {
            $menuImgFile = $_FILES['menuImgFile'];
        }
        $menuLink = Tools::getValue("menuLink");

        $isActiveHeader = Tools::getValue("isActiveHeader");
        $headertext = Tools::getValue("headertext");
        $headerBackColor = Tools::getValue("headerBackColor");
        $isActiveCategory = Tools::getValue("isActiveCategory");
        $categoryText = Tools::getValue("categoryText");
        $categoryBackColor = Tools::getValue("categoryBackColor");
        $isAllCategories = Tools::getValue("isAllCategories");
        $devisText = Tools::getValue("devisText");
        $devisColor = Tools::getValue("devisColor");
        $promoPageText = Tools::getValue("promoPageText");

        if(isset($isActiveCampaign) && isset($titleCampaign) && isset($clientGroup) && isset($startingDate) && isset($endingDate)){

            $createCampaign = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_list` (`id`, `title`, `id_client_group`,`active`,`franco_impact`,`franco_impact_pro`,`date_start`, `date_end`, `date_add`)
                VALUES (NULL, "'.$titleCampaign.'", "'.$clientGroup.'",'.$isActiveCampaign.', NULLIF('.$francoImpact.', 0), NULLIF('.$francoImpactPro.', 0), "'.$startingDate.'","'.$endingDate.'", CURRENT_TIMESTAMP())');

            $newIdQuery = Db::getInstance()->executeS('SELECT id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_list`
                WHERE `title` LIKE "'.$titleCampaign.'" AND `id_client_group` LIKE "'.$clientGroup.'" AND `date_start` = "'.$startingDate.'"');

            $newId = $newIdQuery[0]["id"];

            if(isset($logoPath) && isset($logoFile)) {
                $file_name = $logoFile['name'];

                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);

                $file_base_name = preg_replace('/[^\w\-]/u', '-', $file_base_name); // Remplace les caractères non alphanumériques
                $file_base_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file_base_name); // Convertit les caractères accentués en non accentués

                $file_name = $file_base_name . '.' . $file_extension;

                $checkIfTheSameName = Db::getInstance()->getValue('SELECT `logo_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_list`
                    WHERE id = '.$newId.' AND logo_path LIKE "'.$file_name.'"');
                while ($checkIfTheSameName) {
                    $randomSuffix = rand(1000, 9999);
                    $newFileName = $file_base_name . '-' . $randomSuffix . '.' . $file_extension;

                    // Revérifier avec le nouveau nom de fichier
                    $checkIfTheSameName = Db::getInstance()->getValue(
                        'SELECT `logo_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_list`
                            WHERE id = '.$newId.' AND logo_path LIKE "'.$newFileName.'"'
                    );
                }

                $file_name = isset($newFileName) ? $newFileName : $file_name;


                if($file_name) {
                    if (is_uploaded_file($logoFile['tmp_name'])) {
                        // Vérifier si le dossier 'uploads' existe, sinon le créer
                        $uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'campaigns';
                        if (!file_exists($uploadsDir)) {
                            mkdir($uploadsDir, 0755, true); //
                        }
                        $moveResult = move_uploaded_file($logoFile['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $file_name);

                        if ($moveResult) {
                            $updatelogo = Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'ckccampaign_config_list` 
                                SET `logo_path` = "'.$file_name.'" WHERE id = '.$newId);
                        } else {
                            throw new Exception('Le document n\'a pas pu être enregistré.');
                        }
                    } else {
                        throw new FileNotFoundException('Error while file upload');
                    }
                }
            }

        }


        if(isset($isActiveMenu) && isset($menuImgPath) && isset($menuImgFile)){


            $file_name = $menuImgFile['name'];

            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $file_base_name = pathinfo($file_name, PATHINFO_FILENAME);

            $file_base_name = preg_replace('/[^\w\-]/u', '-', $file_base_name); // Remplace les caractères non alphanumériques
            $file_base_name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file_base_name); // Convertit les caractères accentués en non accentués

            $file_name = $file_base_name . '.' . $file_extension;

            $checkIfTheSameName = Db::getInstance()->getValue('SELECT `img_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu`
                WHERE id = '.$newId.' AND img_path LIKE "'.$file_name.'"');
            while ($checkIfTheSameName) {
                $randomSuffix = rand(1000, 9999);
                $newFileName = $file_base_name . '-' . $randomSuffix . '.' . $file_extension;

                // Revérifier avec le nouveau nom de fichier
                $checkIfTheSameName = Db::getInstance()->getValue(
                    'SELECT `img_path` FROM `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu`
                        WHERE id = '.$newId.' AND img_path LIKE "'.$newFileName.'"'
                );
            }

            $file_name = isset($newFileName) ? $newFileName : $file_name;


            if($file_name) {
                if (is_uploaded_file($menuImgFile['tmp_name'])) {
                    // Vérifier si le dossier 'uploads' existe, sinon le créer
                    $uploadsDir =  __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'uploads'. DIRECTORY_SEPARATOR .'menus';
                    if (!file_exists($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true); //
                    }
                    $moveResult = move_uploaded_file($menuImgFile['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $file_name);

                    if ($moveResult) {
                        $upload = true;
                    } else {
                        throw new Exception('Le document n\'a pas pu être enregistré.');
                    }
                } else {
                    throw new FileNotFoundException('Error while file upload');
                }
            }

            $createMenu = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_menu` (`id`, `id_campaign`, `img_path`, `link`, `active`, `date_add`)
                                VALUES (NULL, '.$newId.', \''.$file_name.'\',\''.$menuLink.'\','.(int)$isActiveMenu.', CURRENT_TIMESTAMP())'
            );
        }

        if (isset($isActiveHeader) && isset($headertext) && isset($headerBackColor)) {

            $createMenu = Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_header` (`id`, `id_campaign`, `active`, `text`, `background_color`, `date_add`)
                                VALUES (NULL, '.$newId.', '.(int)$isActiveHeader.', \''.$headertext.'\', \''.$headerBackColor.'\', CURRENT_TIMESTAMP())'
            );
        }

        /*        if(isset($isActiveCategory) && isset($categoryText) && isset($categoryBackColor) && isset($isAllCategories)) {
                    $createMenu = Db::getInstance()->execute(
                        'INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_category` (`id`, `id_campaign`, `text`, `background_color`, `active`,`show_all`, `date_add`)
                                        VALUES (NULL, '.$newId.', \''.$categoryText.'\', \''.$categoryBackColor.'\',
                                        '.(int)$isActiveCategory.', '.(int)$isAllCategories.', CURRENT_TIMESTAMP())'
                    );
                }*/

        if (isset($devisText) && isset($devisColor)){
            $createDevis =  Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_devis` (`id`, `id_campaign`, `text`, `background_color`, `date_add`)
                                VALUES (NULL, '.$newId.', \''.$devisText.'\', \''.$devisColor.'\', CURRENT_TIMESTAMP())');
        }

        if(isset($promoPageText)){
            $createPromoPage = Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'ckccampaign_config_content_promopage` (`id`, `id_campaign`, `text`, `date_add`)
                VALUES (NULL, '.$newId.', \''.$promoPageText.'\', CURRENT_TIMESTAMP())');
        }

        $newIdQuery = Db::getInstance()->executeS('SELECT id FROM `' . _DB_PREFIX_ . 'ckccampaign_config_list`
                WHERE `title` LIKE "'.$titleCampaign.'" AND `id_client_group` LIKE "'.$clientGroup.'" AND `date_start` = "'.$startingDate.'"');

        $newId = $newIdQuery[0]["id"];

        echo $newId;

        exit();

    }

    public function ajaxProcessSearchProduct(){

        $query = Tools::getValue('query');

        $queryProduct = Db::getInstance()->executeS('SELECT DISTINCT p.id_product, pl.name, cl.name as category FROM `' . _DB_PREFIX_ . 'product_lang` pl
        LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = pl.id_product
        LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON cl.id_category = p.id_category_default
        WHERE pl.id_lang = '.Context::getContext()->language->id.' AND pl.id_shop = '.Context::getContext()->shop->id.'
        AND cl.id_lang = '.Context::getContext()->language->id.' AND cl.id_shop = '.Context::getContext()->shop->id.'
        AND (pl.name LIKE "%'.$query.'%" OR p.id_product = "'.$query.'" OR cl.name LIKE "%'.$query.'%")');

        echo json_encode($queryProduct);

        exit();
    }

    public function ajaxProcessSearchCategory(){

        $query = Tools::getValue('query');

        $queryCategory = Db::getInstance()->executeS('SELECT DISTINCT cl.id_category, cl.name FROM `' . _DB_PREFIX_ . 'category_lang` cl
        WHERE cl.id_lang = '.Context::getContext()->language->id.' AND cl.id_shop = '.Context::getContext()->shop->id.'
        AND cl.name LIKE "%'.$query.'%" OR cl.id_category = "'.$query.'"');

        echo json_encode($queryCategory);

        exit();
    }
}