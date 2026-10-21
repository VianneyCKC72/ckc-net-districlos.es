<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ps_Shoppingcart extends Module implements WidgetInterface
{
    /**
     * @var string Name of the module running on PS 1.6.x. Used for data migration.
     */
    const PS_16_EQUIVALENT_MODULE = 'blockcart';

    public function __construct()
    {
        $this->name = 'ps_shoppingcart';
        $this->tab = 'front_office_features';
        $this->version = '3.0.0';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Shopping cart', [], 'Modules.Shoppingcart.Admin');
        $this->description = $this->trans('Display a shopping cart icon on your pages and the number of items it contains.', [], 'Modules.Shoppingcart.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => _PS_VERSION_];
        $this->controllers = ['ajax'];
    }

    /**
     * @return void
     */
    public function hookDisplayHeader()
    {
        if (Configuration::isCatalogMode()) {
            return;
        }

        if (Configuration::get('PS_BLOCK_CART_AJAX')) {
            $this->context->controller->registerJavascript('modules-shoppingcart', 'modules/' . $this->name . '/ps_shoppingcart.js', ['position' => 'bottom', 'priority' => 150]);
        }
    }

    /**
     * @return string
     */
    private function getCartSummaryURL()
    {
        return $this->context->link->getPageLink(
            'cart',
            null,
            $this->context->language->id,
            [
                'action' => 'show',
            ],
            false,
            null,
            true
        );
    }

    /**
     * @param string|null $hookName
     * @param array<string,mixed> $params
     *
     * @return array<string,mixed>
     */
    public function getWidgetVariables($hookName, array $params)
    {
        return [
            'cart' => $this->getPresentedCart(),
            'refresh_url' => $this->context->link->getModuleLink('ps_shoppingcart', 'ajax', [], null, null, null, true),
            'cart_url' => $this->getCartSummaryURL(),
        ];
    }

    /**
     * Provides an already presented object from the context if set.
     * If not, runs the presenter.
     *
     * @return array presented cart
     */
    private function getPresentedCart()
    {
        /*
         * We will use the already presented cart in the first place. It should be already in the template.
         * Check FrontController::assignGeneralPurposeVariables for more information.
         *
         * If it's not, we will present the cart ourselves. There will always be a cart object
         * assigned in FrontController::init.
         */
        if (!empty($this->context->smarty->getTemplateVars('cart'))) {
            return $this->context->smarty->getTemplateVars('cart');
        } else {
            return (new CartPresenter())->present($this->context->cart);
        }
    }

    /**
     * @param string|null $hookName
     * @param array<string,mixed> $params
     *
     * @return string
     */
    public function renderWidget($hookName, array $params)
    {
        if (Configuration::isCatalogMode()) {
            return '';
        }

        $this->smarty->assign($this->getWidgetVariables($hookName, $params));

        return $this->fetch('module:ps_shoppingcart/ps_shoppingcart.tpl');
    }

    /**
     * @param int $id_product
     * @param int $id_product_attribute
     * @param int $id_customization
     *
     * @return string
     *
     * @throws Exception
     */
    public function renderModal($id_product, $id_product_attribute, $id_customization, $isPack, $packContent)
    {
        // Fallback pack éclaté AP5 : sur certains ajouts (notamment qty>1),
        // ps_shoppingcart/ajax ne reçoit pas id_product_attribute (Tools::getValue => false).
        // On doit quand même afficher une modale complète à partir de l'id_pack (= id_product).
        $isMissingIdProductAttribute = ($id_product_attribute === false || $id_product_attribute === null || $id_product_attribute === '' || $id_product_attribute === 0);
        if (!$isPack && $isMissingIdProductAttribute && Module::isInstalled('pm_advancedpack') && AdvancedPack::isValidPack((int) $id_product)) {
            $data = $this->getPresentedCart();

            $idLang = (int) $this->context->language->id;
            $idShop = (int) $this->context->shop->id;

            $packObj = new Product((int) $id_product, false, $idLang, $idShop);
            $product = array_merge(
                $packObj->getFields(),
                $packObj->getFieldsLang()[$idLang] ?? []
            );

            // Image
            $cover = Product::getCover((int) $id_product);
            if ($cover) {
                $image = new Image((int) $cover['id_image']);
                $imagePath = $image->getExistingImgPath();
                $product['default_image'] = [
                    'medium' => ['url' => _PS_BASE_URL_ . _THEME_PROD_DIR_ . $imagePath . '-medium_default.jpg'],
                    'large' => ['url' => _PS_BASE_URL_ . _THEME_PROD_DIR_ . $imagePath . '-large_default.jpg'],
                    'legend' => $image->legend[$idLang] ?? '',
                ];
            }

            // Contenu pack + quantité pack calculée depuis le panier
            $ap5Module = Module::getInstanceByName('pm_advancedpack');
            if ($ap5Module instanceof pm_advancedpack && $ap5Module->active) {
                $packProducts = AdvancedPack::getPackContent((int) $id_product);
                if (!empty($packProducts)) {
                    $packContent = $ap5Module->displayPackContent(
                        (int) $id_product,
                        false,
                        pm_advancedpack::PACK_CONTENT_SHOPPING_CART,
                        $packProducts
                    );

                    $firstComp = reset($packProducts);
                    $qtyPerPack = !empty($firstComp['quantity']) ? max(1, (int) $firstComp['quantity']) : 1;
                    $packQty = 1;
                    foreach ($data['products'] as $p) {
                        if (!empty($firstComp['id_product']) && (int) $p['id_product'] === (int) $firstComp['id_product']) {
                            $packQty = max(1, (int) round(((int) $p['cart_quantity']) / $qtyPerPack));
                            break;
                        }
                    }
                    $product['cart_quantity'] = $packQty;
                }
            }

            $this->applyPackModalPrices($product, (int) $id_product);

            // Forcer le template standard (modal-part.tpl) à afficher nom+prix+contenu+qty
            $isPack = false;

            $this->smarty->assign([
                'product' => $product,
                'cart' => $data,
                'cart_url' => $this->getCartSummaryURL(),
                'is_pack' => $isPack,
                'pack_content' => $packContent,
            ]);

            return $this->fetch('module:ps_shoppingcart/modal.tpl');
        }

        // Détection automatique du contenu d'un pack AP5 standard (qty > 1)
        // quand le contenu n'est pas déjà fourni (cas pack éclaté géré séparément).
        // Module::isInstalled() utilise le cache mémoire PS → vérification quasi-gratuite.
        if (!$isPack && empty($packContent) && (int) $id_product_attribute > 0
            && Module::isInstalled('pm_advancedpack')
        ) {
            $ap5Module = Module::getInstanceByName('pm_advancedpack');
            if ($ap5Module instanceof pm_advancedpack && $ap5Module->active) {
                if (AdvancedPack::isValidPack((int) $id_product)) {
                    $packContent = $ap5Module->displayPackContent(
                        (int) $id_product,
                        (int) $id_product_attribute,
                        pm_advancedpack::PACK_CONTENT_SHOPPING_CART
                    );
                }
            }
        }

        $data = $this->getPresentedCart();
        $product = null;
        
        if ($isPack) {
            // Cas pack éclaté AP5 : on reçoit parfois id_product_attribute sous forme de tableau
            // (ex: {cq, idpal, ...}) mais sans pack_content. On reconstruit les infos nécessaires
            // et on force ensuite le rendu standard (is_pack=false) pour afficher quantité + prix + contenu.
            if (empty($packContent) && is_array($id_product_attribute) && Module::isInstalled('pm_advancedpack') && AdvancedPack::isValidPack((int) $id_product)) {
                $ap5Module = Module::getInstanceByName('pm_advancedpack');
                if ($ap5Module instanceof pm_advancedpack && $ap5Module->active) {
                    $packProducts = AdvancedPack::getPackContent((int) $id_product, null, true, $id_product_attribute['idpal'] ?? []);
                    if (!empty($packProducts)) {
                        $packContent = $ap5Module->displayPackContent(
                            (int) $id_product,
                            false,
                            pm_advancedpack::PACK_CONTENT_SHOPPING_CART,
                            $packProducts
                        );
                    }
                }
            }

            $id_lang = 1;
            $product = new Product($id_product, false, $id_lang, $id_shop);
            
            // Récupère les champs de base + langues
            $product = array_merge(
                $product->getFields(),
                $product->getFieldsLang()[$id_lang] ?? []
            );
            
            // Récupère l’image par défaut
            $cover = Product::getCover($id_product);
            
            if ($cover) {
                $image = new Image($cover['id_image']);
                $imagePath = $image->getExistingImgPath();
            
                $product['default_image'] = [
                    'medium' => [
                        'url' => _PS_BASE_URL_ . _THEME_PROD_DIR_ . $imagePath . '-medium_default.jpg',
                    ],
                    'large' => [
                        'url' => _PS_BASE_URL_ . _THEME_PROD_DIR_ . $imagePath . '-large_default.jpg',
                    ],
                    'legend' => $image->legend[$id_lang] ?? '',
                ];
            } else {
                // Image par défaut (placeholder)
                $product['default_image'] = [
                    'medium' => [
                        'url' => _PS_BASE_URL_ . _THEME_IMG_DIR_ . 'default-medium_default.jpg',
                    ],
                    'large' => [
                        'url' => _PS_BASE_URL_ . _THEME_IMG_DIR_ . 'default-large_default.jpg',
                    ],
                    'legend' => '',
                ];
            }

            // Quantité ajoutée : si on a cq dans les données pack éclaté, on l'utilise
            if (is_array($id_product_attribute) && isset($id_product_attribute['cq']) && is_numeric($id_product_attribute['cq'])) {
                $product['cart_quantity'] = max(1, (int) $id_product_attribute['cq']);
            }

            if (Module::isInstalled('pm_advancedpack') && AdvancedPack::isValidPack((int) $id_product)) {
                $idpal = (is_array($id_product_attribute) && !empty($id_product_attribute['idpal']) && is_array($id_product_attribute['idpal'])) ? $id_product_attribute['idpal'] : [];
                $ql = (is_array($id_product_attribute) && !empty($id_product_attribute['ql']) && is_array($id_product_attribute['ql'])) ? $id_product_attribute['ql'] : [];
                $pel = (is_array($id_product_attribute) && !empty($id_product_attribute['pel']) && is_array($id_product_attribute['pel'])) ? $id_product_attribute['pel'] : [];
                $this->applyPackModalPrices($product, (int) $id_product, $idpal, $ql, $pel);
            }

            // Forcer la branche standard de modal-part.tpl (affiche quantité + prix + contenu)
            $isPack = false;
        } else {
            foreach ($data['products'] as $p) {
                if ((int) $p['id_product'] == $id_product &&
                    (int) $p['id_product_attribute'] == $id_product_attribute &&
                    (int) $p['id_customization'] == $id_customization) {
                    $product = $p;
                    break;
                }
            }
        }
        
        $this->smarty->assign([
            'product' => $product,
            'cart' => $data,
            'cart_url' => $this->getCartSummaryURL(),
            'is_pack' => $isPack,
            'pack_content' => $packContent
        ]);

        return $this->fetch('module:ps_shoppingcart/modal.tpl');
    }

    /** Prix pack modale : réduit + barré + HT ligne (remplace has_discount=false). */
    private function applyPackModalPrices(array &$product, $idPack, array $idpal = [], array $ql = [], array $pel = [])
    {
        $useTax = !((int) Group::getCurrent()->price_display_method);
        $price = (float) AdvancedPack::getPackPrice($idPack, $useTax, true, true, 6, $idpal, $ql, $pel, true);
        $classic = (float) AdvancedPack::getPackPrice($idPack, $useTax, false, true, 6, $idpal, $ql, $pel, true);
        $product['price'] = Tools::displayPrice($price);
        $product['cart_quantity'] = max(1, (int) ($product['cart_quantity'] ?? 1));
        $product['price_with_reduction_without_tax'] = $useTax ? (float) AdvancedPack::getPackPrice($idPack, false, true, true, 6, $idpal, $ql, $pel, true) : $price;
        $product['price_without_reduction_without_tax'] = $useTax ? (float) AdvancedPack::getPackPrice($idPack, false, false, true, 6, $idpal, $ql, $pel, true) : $classic;
        $product['has_discount'] = $classic > $price + 0.000001;
        if ($product['has_discount']) {
            $product['regular_price'] = Tools::displayPrice($classic);
        }
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitBlockCart')) {
            $ajax = Tools::getValue('PS_BLOCK_CART_AJAX');
            if ($ajax != 0 && $ajax != 1) {
                $output .= $this->displayError($this->trans('Ajax: Invalid choice.', [], 'Modules.Shoppingcart.Admin'));
            } else {
                Configuration::updateValue('PS_BLOCK_CART_AJAX', (int) ($ajax));
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&conf=6');
            }
        }

        return $output . $this->renderForm();
    }

    /**
     * @return bool
     */
    public function install()
    {
        $this->uninstallPrestaShop16Module();

        return
            parent::install()
                && $this->registerHook('displayHeader')
                && $this->registerHook('displayNav2')
                && Configuration::updateValue('PS_BLOCK_CART_AJAX', 1);
    }

    /**
     * Migrate data from 1.6 equivalent module (if applicable), then uninstall
     *
     * @return bool
     */
    public function uninstallPrestaShop16Module()
    {
        if (!Module::isInstalled(self::PS_16_EQUIVALENT_MODULE)) {
            return false;
        }
        $oldModule = Module::getInstanceByName(self::PS_16_EQUIVALENT_MODULE);
        if ($oldModule) {
            // This closure calls the parent class to prevent data to be erased
            // It allows the new module to be configured without migration
            $parentUninstallClosure = function () {
                return parent::uninstall();
            };
            $parentUninstallClosure = $parentUninstallClosure->bindTo($oldModule, get_class($oldModule));
            $parentUninstallClosure();
        }

        return true;
    }

    /**
     * @return string
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Ajax cart', [], 'Modules.Shoppingcart.Admin'),
                        'name' => 'PS_BLOCK_CART_AJAX',
                        'is_bool' => true,
                        'desc' => $this->trans('Activate Ajax mode for the cart (compatible with the default theme).', [], 'Modules.Shoppingcart.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBlockCart';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab
        . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * @return bool[]
     */
    public function getConfigFieldsValues()
    {
        return [
            'PS_BLOCK_CART_AJAX' => (bool) Tools::getValue('PS_BLOCK_CART_AJAX', Configuration::get('PS_BLOCK_CART_AJAX')),
        ];
    }
}
