<?php
if (!defined('_PS_VERSION_')) { exit; }

Configuration::deleteByName('CKC_DISPLAY_EP');
Configuration::deleteByName('CKC_FORM_MIN_EP');
Configuration::deleteByName('CKC_PRO_URL_BOUTIQUE');

// note vianney : je supprime pas les champs en bdd pour éviter de perdre les infos clients !

return true;