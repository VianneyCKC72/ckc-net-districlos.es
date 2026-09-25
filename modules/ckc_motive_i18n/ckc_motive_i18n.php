<?php
/**
 * CKC — charge le layer Motive sur les langues que le module tiers ignore (EN).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckc_motive_i18n extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_motive_i18n';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Agence CKC';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Motive i18n');
        $this->description = $this->l('Active le moteur de recherche Motive sur la boutique anglaise, sans modifier le module Motive.');
        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() && $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayHeader($params)
    {
        // CKC Vianney — Motive injecte déjà le layer EN (engine dédié). Ne pas le réécrire.
        return '';
    }
}
