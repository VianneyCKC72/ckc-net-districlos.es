<?php

// Suppression des colonnes ajoutées à la table `ps_customer` (optionnel)
// Note : Commentez ou supprimez ces lignes si vous préférez ne pas supprimer les données à la désinstallation
$sql = [
    "ALTER TABLE `ps_customer` DROP COLUMN `pays`",
    "ALTER TABLE `ps_customer` DROP COLUMN `tva`",
    "ALTER TABLE `ps_customer` DROP COLUMN `adresse`",
    "ALTER TABLE `ps_customer` DROP COLUMN `adresse2`",
    "ALTER TABLE `ps_customer` DROP COLUMN `cp`",
    "ALTER TABLE `ps_customer` DROP COLUMN `ville`",
    "ALTER TABLE `ps_customer` DROP COLUMN `tel`",
    "ALTER TABLE `ps_customer` DROP COLUMN `fileUpload`",
];

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        $this->_errors[] = 'Error while uninstalling the module: failed to drop custom columns.';
        return false; // Arrêter la désinstallation si une erreur SQL survient
    }
}