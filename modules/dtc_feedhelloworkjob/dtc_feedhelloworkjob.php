<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/CkcHelloworkI18nHelper.php';

class Dtc_FeedHelloWorkJob extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_feedhelloworkjob';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DTC Feed Hello Work Job');
        $this->description = $this->l('Affiche un flux Hello Work sur une page CMS.');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        $ok = parent::install()
            && $this->registerHook('displayFooter')
            && Configuration::updateValue('DTC_HELLOWORK_FEED_URL', 'https://www.hw-recruteur.com/flux/v1/share/it0fnV1nfcdLYm-Diw0CFA==')
            && Configuration::updateValue('DTC_HELLOWORK_JOBS_PER_PAGE', 16);

        if ($ok) {
            CkcHelloworkI18nHelper::ensureSql();
        }

        return (bool) $ok;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('DTC_HELLOWORK_FEED_URL')
            && Configuration::deleteByName('DTC_HELLOWORK_JOBS_PER_PAGE');
    }

    public function getContent()
    {
        CkcHelloworkI18nHelper::ensureSql();
        $output = $this->processCkcTranslateActions();

        if (Tools::isSubmit('submit' . $this->name)) {
            $feed_url = Tools::getValue('DTC_HELLOWORK_FEED_URL');
            $jobs_per_page = (int) Tools::getValue('DTC_HELLOWORK_JOBS_PER_PAGE');
            if (!$feed_url || !$jobs_per_page) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('DTC_HELLOWORK_FEED_URL', $feed_url);
                Configuration::updateValue('DTC_HELLOWORK_JOBS_PER_PAGE', $jobs_per_page);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        $output .= $this->displayTranslatePanel();
        $output .= $this->displayInstructions();

        return $output . $this->displayForm();
    }

    /**
     * Offres Districlos depuis le flux HelloWork (toujours en français).
     *
     * @return array
     */
    public function getJobsFromFeed()
    {
        $url = Configuration::get('DTC_HELLOWORK_FEED_URL');
        $jobs = [];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; PrestaShopBot/1.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['Results']) && is_array($data['Results'])) {
                $jobs = $data['Results'];
            }
        }

        $jobs = array_filter($jobs, function ($job) {
            return isset($job['CompanyName']) && stripos($job['CompanyName'], 'districlos') !== false;
        });

        return array_values($jobs);
    }

    public function displayForm()
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $fields_form[0]['form'] = [
            'legend' => ['title' => $this->l('Settings')],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Feed URL'),
                    'name' => 'DTC_HELLOWORK_FEED_URL',
                    'size' => 50,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Nombre d\'annonces par page'),
                    'name' => 'DTC_HELLOWORK_JOBS_PER_PAGE',
                    'size' => 5,
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->fields_value['DTC_HELLOWORK_FEED_URL'] = Configuration::get('DTC_HELLOWORK_FEED_URL');
        $helper->fields_value['DTC_HELLOWORK_JOBS_PER_PAGE'] = Configuration::get('DTC_HELLOWORK_JOBS_PER_PAGE');

        return $helper->generateForm($fields_form);
    }

    protected function processCkcTranslateActions()
    {
        $output = '';

        if (Tools::getValue('ckcHelloworkTranslateMissing')) {
            if ($this->isCkcTranslateReady()) {
                Tools::redirectAdmin(
                    $this->context->link->getAdminLink('AdminCkcTranslate')
                    . '&run_job=hellowork_jobs&fresh_queue=1'
                );
            }
            $output .= $this->displayError($this->l('Le module CKC Translate n’est pas actif (clé API).'));
        }

        if (Tools::getValue('ckcHelloworkSync')) {
            $synced = CkcHelloworkI18nHelper::syncFromFeed($this->getJobsFromFeed());
            $output .= $this->displayConfirmation(sprintf(
                $this->l('Flux synchronisé : %d offre(s).'),
                (int) $synced
            ));
        }

        return $output;
    }

    protected function displayTranslatePanel()
    {
        $configure = $this->context->link->getAdminLink('AdminModules', true)
            . '&configure=' . $this->name;
        $count = CkcHelloworkI18nHelper::countJobs();
        $html = '<div class="panel"><h3>' . $this->l('Traduction (anglais)') . '</h3>';
        $html .= '<p>' . sprintf($this->l('Offres synchronisées : %d. Synchronisez le flux, puis lancez CKC Translate.'), $count) . '</p>';
        $html .= '<p><a class="btn btn-default" href="' . htmlspecialchars($configure . '&ckcHelloworkSync=1', ENT_QUOTES, 'UTF-8') . '">';
        $html .= $this->l('Synchroniser le flux HelloWork');
        $html .= '</a> ';
        if ($this->isCkcTranslateReady()) {
            $html .= '<a class="btn btn-primary" href="' . htmlspecialchars($configure . '&ckcHelloworkTranslateMissing=1', ENT_QUOTES, 'UTF-8') . '">';
            $html .= $this->l('Traduire les offres manquantes (EN)');
            $html .= '</a>';
        } else {
            $html .= '<span class="help-block">' . $this->l('CKC Translate doit être actif (clé API).') . '</span>';
        }
        $html .= '</p></div>';

        return $html;
    }

    protected function isCkcTranslateReady()
    {
        if (!Module::isInstalled('ckctranslate') || !Module::isEnabled('ckctranslate')) {
            return false;
        }
        if (!class_exists('CkcTranslate', false)) {
            require_once _PS_MODULE_DIR_ . 'ckctranslate/ckctranslate.php';
        }

        return class_exists('CkcTranslate')
            && (string) Configuration::get(CkcTranslate::CONF_API_KEY) !== '';
    }

    private function displayInstructions()
    {
        return $this->displayInformation($this->l(
            'Pour personnaliser l\'URL de la page des offres, allez dans "Paramètres de la boutique > Trafic & SEO > SEO & URLs" et créez une réécriture pour : module-dtc_feedhelloworkjob-jobs → /offres-emploi'
        ));
    }
}
