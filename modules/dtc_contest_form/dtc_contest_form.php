<?php
/**
 * Module de formulaire de concours avec stockage en base de données
 * 
 * @author DISTRICLOS
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Contest_Form extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_contest_form';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Formulaire de concours');
        $this->description = $this->l('Gère les participations au concours avec stockage en base de données.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    /**
     * Installation du module
     */
    public function install()
    {
        // Charger la classe ObjectModel avant l'installation
        require_once dirname(__FILE__) . '/classes/ContestEntry.php';

        return parent::install()
            && $this->installDb();
    }

    /**
     * Désinstallation du module
     */
    public function uninstall()
    {
        require_once dirname(__FILE__) . '/classes/ContestEntry.php';

        return parent::uninstall()
            && $this->uninstallDb();
    }

    /**
     * Installation de la table en base de données
     */
    private function installDb()
    {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_contest_entry` (
                `id_contest_entry` INT(11) NOT NULL AUTO_INCREMENT,
                `last_name` VARCHAR(255) NOT NULL,
                `first_name` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(20) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `activity` VARCHAR(255) DEFAULT NULL,
                `postal_code` VARCHAR(10) NOT NULL,
                `guess_code` VARCHAR(255) NOT NULL,
                `date_add` DATETIME NOT NULL,
                PRIMARY KEY (`id_contest_entry`),
                INDEX `idx_email` (`email`),
                INDEX `idx_date_add` (`date_add`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    /**
     * Désinstallation de la table
     */
    private function uninstallDb()
    {
        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dtc_contest_entry`
        ');
    }
}