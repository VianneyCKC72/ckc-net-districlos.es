<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CkcLogoLangHelper.php';

class ckc_logo_lang extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_logo_lang';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Agence CKC';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Logo par langue');
        $this->description = $this->l('Permet de choisir un logo d’en-tête différent selon la langue, depuis la page Thème et logo.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        $ok = parent::install()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayBackOfficeTop');

        if ($ok) {
            CkcLogoLangHelper::seedEnglishDefault();
        }

        return $ok;
    }

    public function getContent()
    {
        $output = $this->processForm();

        return $output . $this->renderForm(false);
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (!$this->isThemeLogoPage() && Tools::getValue('configure') !== $this->name) {
            return;
        }

        $this->context->controller->addCSS($this->_path . 'views/css/bo.css');
    }

    public function hookDisplayBackOfficeTop()
    {
        if (!$this->isThemeLogoPage()) {
            return '';
        }

        return $this->renderForm(true);
    }

    protected function processForm()
    {
        if (!Tools::isSubmit('submitCkcLogoLang')) {
            return '';
        }

        $errors = CkcLogoLangHelper::saveUploads((int) $this->context->shop->id);
        if ($errors) {
            return $this->displayError(implode('<br>', $errors));
        }

        if (Tools::getValue('ckc_logo_from_theme')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminThemes') . '&conf=4');
        }

        return $this->displayConfirmation($this->l('Logos enregistrés.'));
    }

    protected function renderForm($fromThemePage)
    {
        $idShop = (int) $this->context->shop->id;
        $languages = Language::getLanguages(false, $idShop);
        $rows = [];

        foreach ($languages as $language) {
            $idLang = (int) $language['id_lang'];
            $filename = CkcLogoLangHelper::getFilename($idLang, $idShop);
            $rows[] = [
                'id_lang' => $idLang,
                'name' => $language['name'],
                'iso_code' => $language['iso_code'],
                'filename' => $filename,
                'src' => $filename ? _PS_IMG_ . $filename : '',
                'input_name' => 'ckc_logo_lang_' . $idLang,
                'delete_name' => 'ckc_logo_lang_delete_' . $idLang,
            ];
        }

        $this->context->smarty->assign([
            'ckc_logo_action' => $this->context->link->getAdminLink('AdminModules', true, [], [
                'configure' => $this->name,
                'module_name' => $this->name,
            ]),
            'ckc_logo_from_theme' => $fromThemePage ? 1 : 0,
            'ckc_logo_rows' => $rows,
            'ckc_logo_on_theme_page' => $fromThemePage,
        ]);

        return $this->display(__FILE__, 'views/templates/bo/form.tpl');
    }

    protected function isThemeLogoPage()
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        return (bool) preg_match('#/improve/design/themes/?(\?|$)#', $uri);
    }
}
