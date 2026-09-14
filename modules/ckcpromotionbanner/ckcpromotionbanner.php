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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2024 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/controllers/admin/AdminBarrePromoTabController.php';

/**
 * @since 18-01-2022
 * @author Vianney - CKC
 */

class Ckcpromotionbanner extends Module
{
    protected $config_form = false;

    /** JSON map per shop: {"1":["texte FR"],"2":["texte EN"]} — avoids PrestaShop multilang Configuration bugs */
    const CONF_TEXTS_I18N = 'CKC_BARREPROMO_TEXTE_I18N';
    const CONF_TEXTS_PRO_I18N = 'CKC_BARREPROMO_TEXTE_PRO_I18N';

    public function __construct()
    {
        $this->name = 'ckcpromotionbanner';
        $this->tab = 'pricing_promotion';
        $this->version = '1.2.0';
        $this->author = 'Agence CKC';
        $this->need_instance = 1;
        $this->className = 'AdminManagesBarrePromo';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Promotion banner');
        $this->description = $this->l('Generate a customizable promo banner with a timer with the help of this module.');

        $this->confirmUninstall = $this->l('Are you sure ? ');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

    }

    /**
     * @return bool
     */
    public function callInstallTab()
    {
        $this->installTab('AdminBarrePromoTab', 'Promotion Banner', 'AdminParentThemes');

        return true;
    }


    /**
     * Install admin tabs
     *
     * @param string $className
     * @param string $tabName
     * @param bool $tabParentName
     *
     * @return bool
     */
    public function installTab($className, $tabName, $tabParentName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();

        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Promotion Banner');
        }

        if ($tabParentName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentName);
        } else {
            $tab->id_parent = 0;
        }

        $tab->module = $this->name;

        return $tab->add();
    }


    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CKC_BARREPROMO_DISPLAY', false);
        Configuration::updateValue('CKC_BARREPROMO_DISPLAY_BOTTOM', false);
        Configuration::updateValue('CKC_BARREPROMO_TEXTE', false);
        Configuration::updateValue('CKC_BARREPROMO_COLOR', false);
        Configuration::updateValue('CKC_BARREPROMO_TIMER', false);
        Configuration::updateValue('CKC_BARREPROMO_TIMER_COLOR', false);
        Configuration::updateValue('CKC_BARREPROMO_TIMER_DISPLAY', false);

        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->callInstallTab() &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayAfterBodyOpeningTag');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CKC_BARREPROMO_DISPLAY_BOTTOM');
        Configuration::deleteByName('CKC_BARREPROMO_DISPLAY');
        Configuration::deleteByName('CKC_BARREPROMO_TEXTE');
        Configuration::deleteByName(self::CONF_TEXTS_I18N);
        Configuration::deleteByName(self::CONF_TEXTS_PRO_I18N);
        Configuration::deleteByName('CKC_BARREPROMO_TEXTE_PRO');
        Configuration::deleteByName('CKC_BARREPROMO_COLOR');
        Configuration::deleteByName('CKC_BARREPROMO_TIMER');
        Configuration::deleteByName('CKC_BARREPROMO_TIMER_COLOR');
        Configuration::deleteByName('CKC_BARREPROMO_TIMER_DISPLAY');

        $this->uninstallTab();

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Uninstall admin tabs
     *
     * @return bool
     */
    public function uninstallTab()
    {
        $moduleTabs = Tab::getCollectionFromModule($this->name);
        if (!empty($moduleTabs)) {
            foreach ($moduleTabs as $moduleTab) {
                $moduleTab->delete();
            }
        }

        return true;
    }


    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (((bool) Tools::isSubmit('submitCkc_barrepromoModule')) == true) {
            $this->postProcess();
        }

        $flash = '';

        if (Tools::isSubmit('submitTranslatePromoTextsB2c')) {
            // Persist current BO state first (unsaved FR edits), then translate.
            if (!empty($_POST['id_promotexts_json'])) {
                $this->savePromoTextsFromPost('id_promotexts_json', self::CONF_TEXTS_I18N);
            }
            $flash = $this->translateAndSavePromoTexts(self::CONF_TEXTS_I18N, 'CKC_BARREPROMO_TEXTE');
        } elseif (Tools::isSubmit('submitTranslatePromoTextsPro')) {
            if (!empty($_POST['id_promotextsPro_json'])) {
                $this->savePromoTextsFromPost('id_promotextsPro_json', self::CONF_TEXTS_PRO_I18N);
            }
            $flash = $this->translateAndSavePromoTexts(self::CONF_TEXTS_PRO_I18N, 'CKC_BARREPROMO_TEXTE_PRO');
        } elseif (Tools::isSubmit('submitPromoTextsB2c')) {
            $ok = $this->savePromoTextsFromPost('id_promotexts_json', self::CONF_TEXTS_I18N);
            $flash = $ok
                ? $this->displayConfirmation($this->l('Promotional texts saved.'))
                : $this->displayError($this->l('Could not save promotional texts.'));
        } elseif (Tools::isSubmit('submitPromoTextsPro')) {
            $ok = $this->savePromoTextsFromPost('id_promotextsPro_json', self::CONF_TEXTS_PRO_I18N);
            $flash = $ok
                ? $this->displayConfirmation($this->l('Promotional texts saved.'))
                : $this->displayError($this->l('Could not save promotional texts.'));
        }

        $moduleAdminLink = $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]);

        $languages = Language::getLanguages(false);
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
        $employeeLangId = (int) $this->context->language->id;

        $promotextsByLang = $this->loadPromoTextsCsvByLang(self::CONF_TEXTS_I18N, 'CKC_BARREPROMO_TEXTE', $languages, $defaultLangId);
        $promotextsProByLang = $this->loadPromoTextsCsvByLang(self::CONF_TEXTS_PRO_I18N, 'CKC_BARREPROMO_TEXTE_PRO', $languages, $defaultLangId);

        $ckctranslateReady = $this->isCkcTranslateReady();
        $targetLangId = $ckctranslateReady
            ? (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG)
            : (int) Language::getIdByIso('en');
        if ($targetLangId <= 0) {
            $targetLangId = $defaultLangId;
        }

        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'moduleAdminLink' => $moduleAdminLink,
            'languages' => $languages,
            'default_lang_id' => $defaultLangId,
            'employee_lang_id' => $employeeLangId,
            'promotextsByLang' => $promotextsByLang,
            'promotextsProByLang' => $promotextsProByLang,
            'promotextsByLangJson' => $this->jsonForJs($promotextsByLang),
            'promotextsProByLangJson' => $this->jsonForJs($promotextsProByLang),
            'ckctranslate_ready' => $ckctranslateReady,
            'ckctranslate_target_lang_id' => $targetLangId,
            'current_shop_id' => (int) $this->context->shop->id,
        ));

        $output = $flash;
        $output .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * @return bool
     */
    protected function isCkcTranslateReady()
    {
        if (!Module::isInstalled('ckctranslate') || !Module::isEnabled('ckctranslate')) {
            return false;
        }

        if (!class_exists('CkcTranslate', false)) {
            require_once _PS_MODULE_DIR_ . 'ckctranslate/ckctranslate.php';
        }
        if (!class_exists('CkcTranslateAi', false)) {
            require_once _PS_MODULE_DIR_ . 'ckctranslate/classes/CkcTranslateAi.php';
        }

        $ai = $this->buildCkcTranslateAi();

        return $ai && $ai->isConfigured();
    }

    /**
     * @return CkcTranslateAi|null
     */
    protected function buildCkcTranslateAi()
    {
        if (!Module::isInstalled('ckctranslate') || !Module::isEnabled('ckctranslate')) {
            return null;
        }

        if (!class_exists('CkcTranslate', false)) {
            require_once _PS_MODULE_DIR_ . 'ckctranslate/ckctranslate.php';
        }
        if (!class_exists('CkcTranslateAi', false)) {
            require_once _PS_MODULE_DIR_ . 'ckctranslate/classes/CkcTranslateAi.php';
        }

        return new CkcTranslateAi(
            (string) Configuration::get(CkcTranslate::CONF_API_KEY),
            (string) Configuration::get(CkcTranslate::CONF_MODEL),
            (float) Configuration::get(CkcTranslate::CONF_TEMPERATURE),
            (int) Configuration::get(CkcTranslate::CONF_MAX_TOKENS),
            (string) Configuration::get(CkcTranslate::CONF_PROMPT)
        );
    }

    /**
     * Translate source-language promo texts to ckctranslate target language and persist.
     *
     * @param string $i18nKey
     * @param string $legacyKey
     *
     * @return string HTML flash message
     */
    protected function translateAndSavePromoTexts($i18nKey, $legacyKey)
    {
        if (!$this->isCkcTranslateReady()) {
            return $this->displayError($this->l('ckctranslate is not available or has no API key configured.'));
        }

        $ai = $this->buildCkcTranslateAi();
        $sourceLangId = (int) Configuration::get(CkcTranslate::CONF_SOURCE_LANG);
        $targetLangId = (int) Configuration::get(CkcTranslate::CONF_TARGET_LANG);
        if ($sourceLangId <= 0) {
            $sourceLangId = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        if ($targetLangId <= 0) {
            $targetLangId = (int) Language::getIdByIso('en');
        }
        if ($sourceLangId <= 0 || $targetLangId <= 0 || $sourceLangId === $targetLangId) {
            return $this->displayError($this->l('Invalid source/target languages in ckctranslate.'));
        }

        $map = $this->loadTextsMap($i18nKey, $legacyKey);
        $sourceTexts = isset($map[$sourceLangId]) ? $map[$sourceLangId] : array();
        if (empty($sourceTexts)) {
            return $this->displayError($this->l('No source-language promotional texts to translate.'));
        }

        $sourceIso = Language::getIsoById($sourceLangId) ?: 'fr';
        $targetIso = Language::getIsoById($targetLangId) ?: 'en';
        $jobPrompt = (string) Configuration::get(CkcTranslate::CONF_PROMPT_REASSURANCE);

        $system = trim($jobPrompt . "\n\nTranslate short e-commerce promo banner texts. Keep them short and punchy. "
            . 'Preserve currency amounts, percentages, brand codes (e.g. BLACK2WEEKS) and * markers. '
            . 'Return ONLY valid JSON.');

        $user = 'Translate from ' . $sourceIso . ' to ' . $targetIso . ".\n"
            . "Return ONLY valid JSON:\n{\"texts\":[\"\",\"\"]}\n\n"
            . "Source texts (JSON array):\n" . json_encode(array_values($sourceTexts), JSON_UNESCAPED_UNICODE);

        $data = $ai->requestJson($system, $user);
        if (!is_array($data) || !isset($data['texts']) || !is_array($data['texts'])) {
            return $this->displayError($this->l('Translation failed (invalid AI response). Check ckctranslate API key / logs.'));
        }

        $translated = array();
        foreach ($data['texts'] as $text) {
            $text = trim((string) $text);
            if ($text !== '') {
                $translated[] = $text;
            }
        }

        if (empty($translated)) {
            return $this->displayError($this->l('Translation returned empty texts.'));
        }

        $map[$targetLangId] = $translated;
        if (!$this->saveTextsMap($i18nKey, $map)) {
            return $this->displayError($this->l('Translated texts could not be saved.'));
        }

        return $this->displayConfirmation(sprintf(
            $this->l('Translated %d text(s) to language #%d and saved for shop #%d.'),
            count($translated),
            $targetLangId,
            (int) $this->context->shop->id
        ));
    }

    /**
     * @param string $postJsonKey
     * @param string $i18nKey
     *
     * @return bool
     */
    protected function savePromoTextsFromPost($postJsonKey, $i18nKey)
    {
        // Avoid Tools::getValue() urldecode which can corrupt "%" in promo texts.
        $rawJson = isset($_POST[$postJsonKey]) ? $_POST[$postJsonKey] : Tools::getValue($postJsonKey);
        if ($rawJson === false || $rawJson === null || $rawJson === '') {
            return false;
        }

        $decoded = json_decode((string) $rawJson, true);
        if (!is_array($decoded)) {
            return false;
        }

        $map = array();
        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];
            $items = array();
            if (isset($decoded[$idLang]) && is_array($decoded[$idLang])) {
                $items = $decoded[$idLang];
            } elseif (isset($decoded[(string) $idLang]) && is_array($decoded[(string) $idLang])) {
                $items = $decoded[(string) $idLang];
            }

            $clean = array();
            foreach ($items as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $clean[] = $item;
                }
            }
            $map[$idLang] = $clean;
        }

        return $this->saveTextsMap($i18nKey, $map);
    }

    /**
     * @param string $i18nKey
     * @param array<int, array<int, string>> $map
     *
     * @return bool
     */
    protected function saveTextsMap($i18nKey, array $map)
    {
        $normalized = array();
        foreach ($map as $idLang => $texts) {
            $idLang = (int) $idLang;
            if ($idLang <= 0 || !is_array($texts)) {
                continue;
            }
            $normalized[(string) $idLang] = array_values($texts);
        }

        $payload = base64_encode(json_encode($normalized, JSON_UNESCAPED_UNICODE));
        if ($payload === false) {
            return false;
        }

        $idShop = (int) $this->context->shop->id;

        return (bool) Configuration::updateValue($i18nKey, $payload, false, null, $idShop > 0 ? $idShop : null);
    }

    /**
     * @param string $i18nKey
     * @param string $legacyKey
     *
     * @return array<int, array<int, string>>
     */
    protected function loadTextsMap($i18nKey, $legacyKey)
    {
        $idShop = (int) $this->context->shop->id;
        $raw = Configuration::get($i18nKey, null, null, $idShop > 0 ? $idShop : null);
        if ($raw === false || $raw === null || $raw === '') {
            $raw = Configuration::get($i18nKey);
        }

        $map = array();
        if (is_string($raw) && $raw !== '') {
            $json = base64_decode($raw, true);
            if ($json === false) {
                $json = $raw;
            }
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                foreach ($decoded as $idLang => $texts) {
                    if (!is_array($texts)) {
                        continue;
                    }
                    $clean = array();
                    foreach ($texts as $t) {
                        $t = trim((string) $t);
                        if ($t !== '') {
                            $clean[] = $t;
                        }
                    }
                    $map[(int) $idLang] = $clean;
                }
            }
        }

        // Migrate once from legacy serialized / multilang keys if I18N empty
        $hasAny = false;
        foreach ($map as $texts) {
            if (!empty($texts)) {
                $hasAny = true;
                break;
            }
        }

        if (!$hasAny) {
            $map = $this->migrateLegacyTextsMap($legacyKey);
            if (!empty($map)) {
                $this->saveTextsMap($i18nKey, $map);
            }
        }

        return $map;
    }

    /**
     * @param string $legacyKey
     *
     * @return array<int, array<int, string>>
     */
    protected function migrateLegacyTextsMap($legacyKey)
    {
        $map = array();
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');

        if (Configuration::isLangKey($legacyKey)) {
            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int) $lang['id_lang'];
                $raw = Configuration::get($legacyKey, $idLang);
                $arr = $this->decodePromoTexts($raw === false || $raw === null ? '' : (string) $raw);
                if (!empty($arr)) {
                    $map[$idLang] = $arr;
                }
            }
        }

        if (empty($map[$defaultLangId])) {
            $legacyRaw = $this->getLegacyConfigurationValue($legacyKey);
            $legacyArr = $this->decodePromoTexts($legacyRaw);
            if (!empty($legacyArr)) {
                $map[$defaultLangId] = $legacyArr;
            }
        }

        return $map;
    }

    /**
     * @param string $i18nKey
     * @param string $legacyKey
     * @param array $languages
     * @param int $defaultLangId
     *
     * @return array<int, string> CSV per lang for the BO widget
     */
    protected function loadPromoTextsCsvByLang($i18nKey, $legacyKey, array $languages, $defaultLangId)
    {
        $map = $this->loadTextsMap($i18nKey, $legacyKey);
        $byLang = array();

        foreach ($languages as $lang) {
            $idLang = (int) $lang['id_lang'];
            $arr = isset($map[$idLang]) ? $map[$idLang] : array();
            $byLang[$idLang] = implode(',,', $arr);
        }

        return $byLang;
    }

    /**
     * Read the raw value column from ps_configuration (legacy / non-lang storage).
     *
     * @param string $configKey
     *
     * @return string
     */
    protected function getLegacyConfigurationValue($configKey)
    {
        $idShop = (int) $this->context->shop->id;

        $sql = 'SELECT `value` FROM `' . _DB_PREFIX_ . 'configuration`
            WHERE `name` = \'' . pSQL($configKey) . '\'';
        if ($idShop > 0) {
            $sql .= ' AND `id_shop` = ' . (int) $idShop;
        } else {
            $sql .= ' AND (`id_shop` IS NULL OR `id_shop` = 0)';
        }

        $raw = Db::getInstance()->getValue($sql);
        if ($raw !== false && $raw !== null && $raw !== '') {
            return (string) $raw;
        }

        $raw = Db::getInstance()->getValue(
            'SELECT `value` FROM `' . _DB_PREFIX_ . 'configuration`
            WHERE `name` = \'' . pSQL($configKey) . '\'
            ORDER BY `id_shop` DESC, `id_shop_group` DESC'
        );

        return $raw === false || $raw === null ? '' : (string) $raw;
    }

    protected function encodePromoTexts($csv)
    {
        $arr = array();
        foreach (explode(',,', (string) $csv) as $item) {
            $item = trim($item);
            if ($item !== '') {
                $arr[] = $item;
            }
        }

        return base64_encode(serialize($arr));
    }

    protected function decodePromoTexts($raw)
    {
        if ($raw === '' || $raw === null || $raw === false) {
            return array();
        }

        $decoded = @unserialize(base64_decode((string) $raw));

        return is_array($decoded) ? $decoded : array();
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    protected function jsonForJs($data)
    {
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);

        return $json === false ? '{}' : $json;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCkc_barrepromoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show the promotion banner'),
                        'name' => 'CKC_BARREPROMO_DISPLAY',
                        'is_bool' => true,
                        'desc' => $this->l('The promotion banner will be displayed at the top or bottom of the page'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l("Show")
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l("Hide")
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show at bottom of page'),
                        'name' => 'CKC_BARREPROMO_DISPLAY_BOTTOM',
                        'is_bool' => true,
                        'desc' => $this->l('By default, the promotion banner is displayed at the top of the page'),
                        'values' => array(
                            array(
                                'id' => 'bottom_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'bottom_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_COLOR',
                        'label' => $this->l('Background color of the banner'),
                        'desc' => $this->l('Choose the background color of the banner'),
                        'type' => 'color',
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_TIMER',
                        'label' => $this->l('Deadline'),
                        'desc' => $this->l('Promotion expiration date, will be displayed as a timer.'),
                        'type' => 'datetime',
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_TIMER_COLOR',
                        'label' => $this->l('Text color of the timer'),
                        'desc' => $this->l('Choose the Text color of the timer'),
                        'type' => 'color',
                    ),
                    array(
                        'name' => 'CKC_BARREPROMO_TIMER_DISPLAY',
                        'label' => $this->l("Display a timer"),
                        'align' => 'text-left',
                        'type' => 'switch',
                        'class' => 'fixed-width-lg',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'timer_on',
                                'value' => true,
                                'label' => $this->l('Show')
                            ),
                            array(
                                'id' => 'timer_off',
                                'value' => false,
                                'label' => $this->l('Hide')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CKC_BARREPROMO_DISPLAY' => Configuration::get('CKC_BARREPROMO_DISPLAY', false),
            'CKC_BARREPROMO_DISPLAY_BOTTOM' => Configuration::get('CKC_BARREPROMO_DISPLAY_BOTTOM', false),
            'CKC_BARREPROMO_COLOR' => Configuration::get('CKC_BARREPROMO_COLOR',false),
            'CKC_BARREPROMO_TIMER' => Configuration::get('CKC_BARREPROMO_TIMER', false),
            'CKC_BARREPROMO_TIMER_COLOR' => Configuration::get('CKC_BARREPROMO_TIMER_COLOR', false),
            'CKC_BARREPROMO_TIMER_DISPLAY' => Configuration::get('CKC_BARREPROMO_TIMER_DISPLAY', false),
        );
    }
    protected function getConfigFormValuesFront()
    {
        return array(
            'CKC_BARREPROMO_DISPLAY' => Configuration::get('CKC_BARREPROMO_DISPLAY', false),
            'CKC_BARREPROMO_DISPLAY_BOTTOM' => Configuration::get('CKC_BARREPROMO_DISPLAY_BOTTOM', false),
            'CKC_BARREPROMO_COLOR' => Configuration::get('CKC_BARREPROMO_COLOR',false),
            'CKC_BARREPROMO_TIMER' => Configuration::get('CKC_BARREPROMO_TIMER', false),
            'CKC_BARREPROMO_TIMER_COLOR' => Configuration::get('CKC_BARREPROMO_TIMER_COLOR', false),
            'CKC_BARREPROMO_TIMER_DISPLAY' => Configuration::get('CKC_BARREPROMO_TIMER_DISPLAY', false),
        );
    }
    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        $p_open = '<' . 'p' . '>';
        $p_close = '</' . 'p' . '>';
        foreach (array_keys($form_values) as $key) {
            $value = str_replace([$p_open, $p_close], '', Tools::getValue($key));
            Configuration::updateValue($key, $value, true);
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * @author Vianney
     *         Print all values in the front template
     * @return mixed
     */
    public function hookDisplayAfterBodyOpeningTag()
    {
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
        $this->context->controller->addJS($this->_path . '/views/js/front.js');

        $form_values = $this->getConfigFormValuesFront();
        $addClass = '';
        if (Configuration::get('CKC_BARREPROMO_DISPLAY_BOTTOM') == true) {
            $addClass .= 'bottom';
        }

        $idLang = (int) $this->context->language->id;
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');

        $b2cMap = $this->loadTextsMap(self::CONF_TEXTS_I18N, 'CKC_BARREPROMO_TEXTE');
        $proMap = $this->loadTextsMap(self::CONF_TEXTS_PRO_I18N, 'CKC_BARREPROMO_TEXTE_PRO');

        $b2cTexts = isset($b2cMap[$idLang]) ? $b2cMap[$idLang] : array();
        if (empty($b2cTexts) && isset($b2cMap[$defaultLangId])) {
            $b2cTexts = $b2cMap[$defaultLangId];
        }

        $proTexts = isset($proMap[$idLang]) ? $proMap[$idLang] : array();
        if (empty($proTexts) && isset($proMap[$defaultLangId])) {
            $proTexts = $proMap[$defaultLangId];
        }

        $this->smarty->assign('ckcpromobarretexts', !empty($b2cTexts) ? $b2cTexts : '');
        $this->smarty->assign('ckcpromobarretextsPro', !empty($proTexts) ? $proTexts : '');

        foreach (array_keys($form_values) as $key) {
            $this->smarty->assign($key, Configuration::get($key));
        }

        $this->smarty->assign('class', $addClass);

        $today = new DateTime();
        $timer = null;
        if (!empty(Configuration::get('CKC_BARREPROMO_TIMER'))) {
            $timer = new DateTime(Configuration::get('CKC_BARREPROMO_TIMER'));
        }
        if (Configuration::get('CKC_BARREPROMO_DISPLAY') == 1) {
            if (($today < $timer) || $timer == null) {
                return $this->display(__FILE__, 'views/templates/front/alert.tpl');
            } else {
                return $this->display(__FILE__, 'views/templates/front/alert.tpl');
            }
        } else {
            return $this->display(__FILE__, 'views/templates/front/alert.tpl');
        }
    }
}
