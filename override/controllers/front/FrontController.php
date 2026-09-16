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
        $this->context->smarty->assign(
            'ckc_lang_preview',
            (string) Tools::getValue('ckc') === '1'
        );
        return parent::initHeader();
    }
    public function getTemplateVarUrls()
    {
        $urls = parent::getTemplateVarUrls();
        if (!empty($urls['alternative_langs']) && is_array($urls['alternative_langs'])) {
            foreach (array_keys($urls['alternative_langs']) as $code) {
                if ($this->isEnglishLangCode((string) $code)) {
                    unset($urls['alternative_langs'][$code]);
                }
            }
        }
        return $urls;
    }
    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $iso = strtolower((string) $this->context->language->iso_code);
        if ($this->isEnglishLangCode($iso)) {
            $page['meta']['robots'] = 'noindex, nofollow';
        }
        return $page;
    }
    private function isEnglishLangCode($code)
    {
        $code = strtolower(str_replace('_', '-', (string) $code));
        return $code === 'en' || strpos($code, 'en-') === 0;
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
}
