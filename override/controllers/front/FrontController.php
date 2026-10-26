<?php
class FrontController extends FrontControllerCore
{
    public function initContent()
    {
        $isLogged = $this->context->customer ? (bool)$this->context->customer->isLogged() : false;
        $newClient                            = 0;
        $isConfirmationPage = strpos($_SERVER['REQUEST_URI'], 'confirmation') !== false;
        if ($isLogged) {
            $id_customer = (int) $this->context->customer->id;
            $default_address_id = null;
            $orders    = Order::getCustomerOrders($id_customer);
            $newClient = count($orders) == 0 ? 1 : 0;
        
            if (count($orders) == 1 && $isConfirmationPage) {
                $newClient = 1;
            }
            if ($id_customer > 0 && !empty($this->context->cookie->default_address_id_cached)) {
                $default_address_id = (int) $this->context->cookie->default_address_id_cached;
                if ($default_address_id <= 0) {
                    $default_address_id = null;
                    unset($this->context->cookie->default_address_id_cached);
                }
            }
            if ($id_customer > 0 && !$default_address_id) {
                $sql = 'SELECT `id_address_default`
                FROM `' . _DB_PREFIX_ . 'customer`
                WHERE `id_customer` = ' . (int) $id_customer;
                $row = Db::getInstance()->getRow($sql);
                if (!empty($row['id_address_default'])) {
                    $default_address_id = (int) $row['id_address_default'];
                }
                if (!$default_address_id) {
                    $sql = 'SELECT `id_address_delivery`
                    FROM `' . _DB_PREFIX_ . 'orders`
                    WHERE `id_customer` = ' . (int) $id_customer . '
                    ORDER BY `id_order` DESC';
                    $row = Db::getInstance()->getRow($sql);
                    if (!empty($row['id_address_delivery'])) {
                        $default_address_id = (int) $row['id_address_delivery'];
                    }
                }
                if (!$default_address_id) {
                    $default_address_id = (int) Address::getFirstCustomerAddressId($id_customer);
                }
                if ($default_address_id > 0) {
                    $this->context->cookie->default_address_id_cached = $default_address_id;
                }
            }
            if ($default_address_id > 0) {
                $this->context->smarty->assign(['default_address_id' => $default_address_id]);
                Media::addJsDef(['default_address_id' => $default_address_id]);
            }
            
        } else {
            unset($this->context->cookie->default_address_id_cached);
        }
        $this->context->smarty->assign(['newClient' => $newClient]);
        parent::initContent();
    }
    public function postProcess()
    {
        $addressId = (int)Tools::getValue('addressId');
        if ($addressId) {
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'customer`
                 SET `id_address_default` = '.$addressId.'
                 WHERE `id_customer` = '.(int)$this->context->customer->id
            );
            Media::addJsDef(['default_address_id' => $addressId]);
            echo 'success';
        }
        parent::postProcess();
    }
    public function initHeader()
    {
        $show_emptycart = false;
        $cookie = new Cookie('psAdmin', '', (int)Configuration::get('PS_COOKIE_LIFETIME_BO'));
        if (!empty($cookie->id_employee)) {
            $show_emptycart = true;
        }
        $this->context->smarty->assign('show_emptycart', $show_emptycart);
        $this->context->smarty->assign('IS_EMPLOYEE_BO', $show_emptycart);
        Media::addJsDef(['IS_EMPLOYEE_BO' => (bool)$show_emptycart]);
        $langLogo = $this->getLanguageShopLogo();
        $this->context->smarty->assign([
            'ckc_is_english' => $this->isCurrentLanguageEnglish(),
            'ckc_logo_src' => !empty($langLogo['src']) ? $langLogo['src'] : '',
        ]);

        return parent::initHeader();
    }
    public function getShopLogo(): array
    {
        $langLogo = $this->getLanguageShopLogo();
        if (!empty($langLogo['src'])) {
            return $langLogo;
        }

        return parent::getShopLogo();
    }

    public function getTemplateVarShop()
    {
        $shop = parent::getTemplateVarShop();
        $langLogo = $this->getLanguageShopLogo();
        if (!empty($langLogo['src'])) {
            $shop['logo'] = $langLogo['src'];
            $shop['logo_details'] = $langLogo;
        }

        return $shop;
    }

    /**
     * CKC Vianney — logo FO selon la langue (module ckc_logo_lang).
     */
    private function getLanguageShopLogo()
    {
        if (!Module::isInstalled('ckc_logo_lang') || !Module::isEnabled('ckc_logo_lang')) {
            return [];
        }

        if (!class_exists('CkcLogoLangHelper', false)) {
            $helperFile = _PS_MODULE_DIR_ . 'ckc_logo_lang/classes/CkcLogoLangHelper.php';
            if (!is_file($helperFile)) {
                return [];
            }
            require_once $helperFile;
        }

        if (!class_exists('CkcLogoLangHelper')) {
            return [];
        }

        return CkcLogoLangHelper::getLogoDetails($this->context);
    }

    private function isCurrentLanguageEnglish()
    {
        if (!isset($this->context->language)) {
            return false;
        }

        return $this->isEnglishLangCode((string) $this->context->language->iso_code)
            || $this->isEnglishLangCode((string) $this->context->language->language_code);
    }

    private function isEnglishLangCode($code)
    {
        $code = strtolower(str_replace('_', '-', (string) $code));
        return $code === 'en' || $code === 'gb' || strpos($code, 'en-') === 0;
    }
    public function getTemplateVarCustomer($customer = null)
    {
        $cust = parent::getTemplateVarCustomer($customer);
        if (CustomerAddressFormatter::isProFrontOffice() || empty($cust['addresses'])) {
            return $cust;
        }
        foreach ($cust['addresses'] as &$address) {
            if (empty($address['id'])) {
                continue;
            }
            $address['formatted'] = AddressFormat::generateAddress(
                new Address((int) $address['id']),
                ['avoid' => ['company', 'vat_number']],
                '<br>'
            );
        }
        unset($address);
        return $cust;
    }
    
    
    
    /*
    * module: ets_awesomeurl
    * date: 2026-09-01 11:03:12
    * version: 1.4.2
    */
    protected $redirectionExtraExcludedKeys = ['rewrite', 'category'];

    /**
     * CKC Vianney — CE Full Width points to its module layout (hooks FR only).
     * Prefer the theme copy so accueil pro can switch 36/37 vs 62/63.
     */
    public function getLayout()
    {
        $layout = parent::getLayout();
        if (!is_string($layout) || strpos($layout, 'layout-header-footer.tpl') === false) {
            return $layout;
        }
        if (strpos($layout, _PS_THEME_DIR_) !== false) {
            return $layout;
        }
        $themeFile = _PS_THEME_DIR_ . 'modules/creativeelements/views/templates/front/theme/layouts/layout-header-footer.tpl';

        return is_file($themeFile) ? $themeFile : $layout;
    }

    /* CKC - remplacement de l'override de getTotals() de CartLazyArray :
    * déplacé ici, car l'override dans CartLazyArray peut être supprimé lors de mise à jour de Prestashop
    */
    protected function assignGeneralPurposeVariables()
    {
        parent::assignGeneralPurposeVariables();
        $presentedCart = $this->context->smarty->getTemplateVars('cart');
        if (!$presentedCart || !Validate::isLoadedObject($this->context->cart)) {
            return;
        }
        $totals = $presentedCart['totals'];
        if (!is_array($totals)) {
            return;
        }
        $includeTax = $this->cart_presenter->includeTaxes();
        $amount = $this->context->cart->getOrderTotal($includeTax, Cart::BOTH_WITHOUT_SHIPPING);
        $totals['total_for_cart'] = [
            'type' => 'total',
            'label' => $this->trans('Total', [], 'Shop.Theme.Checkout'),
            'amount' => $amount,
            'value' => (new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter())->format($amount),
        ];
        $presentedCart['totals'] = $totals;
        $this->context->smarty->assign('cart', $presentedCart);
    }
}
