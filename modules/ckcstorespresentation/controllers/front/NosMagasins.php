<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcstorespresentationNosMagasinsModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {

        $magasins = Db::getInstance()->executeS(
            'SELECT s.*, p.`place_id`, p.`google_rating`, p.`google_rating_count`
            FROM `' . _DB_PREFIX_ . 'ckcstorespresentation` s
            LEFT JOIN `' . _DB_PREFIX_ . 'presentation_place` p
                ON p.`id_ckcstorespresentation` = s.`id_ckcstorespresentation`'
        );
        $magasins = $this->module->localizeStoresForDisplay($magasins ?: []);
        $this->context->controller->addJquery();

        $this->context->smarty->assign(array(
            'magasins' => $magasins,
            'elements' => $magasins,
            'modulePath' => $this->context->link->getModuleLink('ckcstorespresentation', 'NosMagasins'),
            'lang' => Context::getContext()->language->iso_code,
        ));

        if (preg_match("/^1.6/", _PS_VERSION_)) {
            $this->setTemplate('nos-magasins.tpl');
        } else {
            $this->setTemplate('module:ckcstorespresentation/views/templates/front/nos-magasins.tpl');
        }

        return parent::initContent();
    }

    public function postProcess()
    {
        $query = Tools::getValue('query');
        $lat = Tools::getValue('latitude');
        $lng = Tools::getValue('longitude');
        $search = [];

        // Si coordonnées de géolocalisation fournies
        if (!empty($lat) && !empty($lng)) {
            $lat = (float) $lat;
            $lng = (float) $lng;

            $search = Db::getInstance()->executeS(
                "SELECT s.*, p.`place_id`, p.`google_rating`, p.`google_rating_count`, (
                6371 * acos(
                    cos(radians(" . pSQL($lat) . ")) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(" . pSQL($lng) . ")) +
                    sin(radians(" . pSQL($lat) . ")) * sin(radians(latitude))
                )
            ) AS distance
            FROM `" . _DB_PREFIX_ . "ckcstorespresentation` s
            LEFT JOIN `" . _DB_PREFIX_ . "presentation_place` p
                ON p.`id_ckcstorespresentation` = s.`id_ckcstorespresentation`
            WHERE s.latitude IS NOT NULL AND s.longitude IS NOT NULL
            ORDER BY distance ASC
            LIMIT 20"
            );
        }

        // Sinon, recherche textuelle
        elseif ($query !== false) {
            $querySql = '%' . pSQL($query) . '%';

            $search = Db::getInstance()->executeS(
                "SELECT s.*, p.`place_id`, p.`google_rating`, p.`google_rating_count`
                FROM `" . _DB_PREFIX_ . "ckcstorespresentation` s
                LEFT JOIN `" . _DB_PREFIX_ . "presentation_place` p
                    ON p.`id_ckcstorespresentation` = s.`id_ckcstorespresentation`
                WHERE s.id_ckcstorespresentation LIKE '$querySql'
                OR s.departement LIKE '$querySql'
                OR s.adresse LIKE '$querySql'
                OR s.ville LIKE '$querySql'
                OR s.code_postal LIKE '$querySql'
                OR s.email LIKE '$querySql'
                OR s.telephone LIKE '$querySql'
                OR s.horaires_jours LIKE '$querySql'
                OR s.horaires_heures LIKE '$querySql'"
            );
        }

        // Affichage des résultats si recherche effectuée
        if (!empty($search)) {
            $search = $this->module->localizeStoresForDisplay($search);
            $this->context->smarty->assign([
                'magasins' => $search,
                'modulePath' => $this->context->link->getModuleLink('ckcstorespresentation', 'NosMagasins'),
                'lang' => Context::getContext()->language->iso_code,
                'elements' => $search
            ]);

            $html = $this->context->smarty->fetch('module:ckcstorespresentation/views/templates/front/ajax.tpl');
            die($html);
        }

        // Optionnel : si aucune recherche ou résultat vide
        die('<p class="no-results">' . $this->trans('Aucun magasin trouvé.', [], 'Shop.Theme.Customstores') . '</p>');
    }

}
