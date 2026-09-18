
<?php
if (!defined('_PS_VERSION_')) {
 exit;
}

class Dtc_FeedHelloWorkJob extends Module
{
 public function __construct()
 {
 $this->name = 'dtc_feedhelloworkjob';
 $this->tab = 'front_office_features';
 $this->version = '1.0.1';
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
 return parent::install()
 && $this->registerHook('displayFooter')
 && Configuration::updateValue('DTC_HELLOWORK_FEED_URL', 'https://www.hw-recruteur.com/flux/v1/share/it0fnV1nfcdLYm-Diw0CFA==')
 && Configuration::updateValue('DTC_HELLOWORK_JOBS_PER_PAGE', 16);
 }

 public function uninstall()
 {
 return parent::uninstall()
 && Configuration::deleteByName('DTC_HELLOWORK_FEED_URL')
 && Configuration::deleteByName('DTC_HELLOWORK_JOBS_PER_PAGE');
 }

 public function getContent()
 {
 $output = '';

 if (Tools::isSubmit('submit' . $this->name)) {
 $feed_url = Tools::getValue('DTC_HELLOWORK_FEED_URL');
 $jobs_per_page = (int)Tools::getValue('DTC_HELLOWORK_JOBS_PER_PAGE');
 if (!$feed_url || !$jobs_per_page) {
 $output .= $this->displayError($this->l('Invalid Configuration value'));
 } else {
 Configuration::updateValue('DTC_HELLOWORK_FEED_URL', $feed_url);
 Configuration::updateValue('DTC_HELLOWORK_JOBS_PER_PAGE', $jobs_per_page);
 $output .= $this->displayConfirmation($this->l('Settings updated'));
 }
 }

 $output .= $this->displayInstructions();

 return $output . $this->displayForm();
 }

 public function displayForm()
 {
 $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

 $fields_form[0]['form'] = [
 'legend' => ['title' => $this->l('Settings')],
 'input' => [
 [
 'type' => 'text',
 'label' => $this->l('Feed URL'),
 'name' => 'DTC_HELLOWORK_FEED_URL',
 'size' => 50,
 'required' => true
 ],
 [
 'type' => 'text',
 'label' => $this->l('Nombre d\'annonces par page'),
 'name' => 'DTC_HELLOWORK_JOBS_PER_PAGE',
 'size' => 5,
 'required' => true
 ]
 ],
 'submit' => [
 'title' => $this->l('Save'),
 'class' => 'btn btn-default pull-right'
 ]
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

 private function displayInstructions()
 {
 return $this->displayInformation($this->l(
 'Pour personnaliser l\'URL de la page des offres, allez dans "Paramètres de la boutique > Trafic & SEO > SEO & URLs" et créez une réécriture pour : module-dtc_feedhelloworkjob-jobs → /offres-emploi'
 ));
 }
}
?>
