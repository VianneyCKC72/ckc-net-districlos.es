<?php
/**
 * Extensions webservice PrestaShop pour l'intégration Odoo.
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/DtcOdooApiWsEndpoint.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementShopUnlinks.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementCartRuleRestrictions.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementProductCarriers.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementCustomerGroups.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementProductAttachments.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementProductExtraAdvice.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementProductFaq.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementProductLabels.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementShippingRules.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementShippingRuleRestrictions.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementStockStores.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementStockStoreStocks.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementAccessoryGroups.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementProductAccessories.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementProductAccessorySettings.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementQuotations.php';
require_once dirname(__FILE__) . '/classes/WebserviceSpecificManagementQuotationCartPrices.php';

class Dtc_Odoo_Api extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_odoo_api';
        $this->tab = 'administration';
        $this->version = '1.12.1';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;
        $this->bootstrap = false;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('API Odoo - Extensions Webservice');
        $this->description = $this->l('Complète l\'API webservice PrestaShop avec des endpoints dédiés à Odoo, pour les cas non couverts nativement.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('addWebserviceResources');
    }

    /**
     * Enregistre les ressources webservice du module.
     *
     * @param array $params
     *
     * @return array
     */
    public function hookAddWebserviceResources($params)
    {
        return [
            'shop_unlinks' => [
                'description' => 'Unlink an entity from a shop without cascade delete (Odoo)',
                'specific_management' => true,
                'forbidden_method' => ['PUT', 'PATCH', 'DELETE'],
            ],
            'cart_rule_restrictions' => [
                'description' => 'Cart rule associations (countries, carriers, groups, shops, combinations, products)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'product_carriers' => [
                'description' => 'Product carrier restrictions (Available carriers tab, not in /api/products)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'customer_groups' => [
                'description' => 'Customer group assignments (read/write customer_group table)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'product_attachments' => [
                'description' => 'Product attachments (link existing ids or register a file already dropped via SFTP)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH'],
            ],
            'product_extra_advice' => [
                'description' => 'Product extra advice & maintenance fields (CKC module ckc_product_extra)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'product_faq' => [
                'description' => 'Product FAQ entries (question/answer per product)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'product_labels' => [
                'description' => 'CKC label management (ps_ckc_labels + product/category links)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH'],
            ],
            'shipping_rules' => [
                'description' => 'Advanced shipping rules (orderfees_shipping / of_shipping_rule)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH'],
            ],
            'shipping_rule_restrictions' => [
                'description' => 'Shipping rule associations (countries, carriers, products, packages, ...)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'stock_stores' => [
                'description' => 'CKC multi-store warehouses (ckcstockstores_stores)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH'],
            ],
            'stock_store_stocks' => [
                'description' => 'CKC per-store product stocks (ckcstockstores_stocks)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'accessory_groups' => [
                'description' => 'HS Multi Accessories Pro groups (accessory_group)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH'],
            ],
            'product_accessories' => [
                'description' => 'HSMA product↔accessory links (accessory_group_product)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'product_accessory_settings' => [
                'description' => 'HSMA per-product buy_together settings',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
            'quotations' => [
                'description' => 'Op\'art devis (opartdevis) quotations for Odoo sync',
                'specific_management' => true,
                'forbidden_method' => ['PATCH'],
            ],
            'quotation_cart_prices' => [
                'description' => 'Negotiated HT prices on quotation cart lines (SpecificPrice)',
                'specific_management' => true,
                'forbidden_method' => ['PATCH', 'DELETE'],
            ],
        ];
    }
}
