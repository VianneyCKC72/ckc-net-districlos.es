<?php
/**
 * Script cron pour mettre à jour la règle panier 790 : REMISE PICOT
 * 
 * Ce script met à jour automatiquement :
 * - Les dates de validité (date_from et date_to) pour le mois en cours
 * - Le code de la règle au format "GROUP_" + 3 premières lettres du mois en cours + année complète
 * 
 * À exécuter le 1er de chaque mois (ex: 0 1 1 * *)
 * 
 * @author Districlos
 */

// Initialisation de PrestaShop
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
require_once _PS_ADMIN_DIR_ . '/../config/config.inc.php';

// ID de la règle panier à mettre à jour
const ID_CART_RULE = 790;

// Destinataire de la notification
const EMAIL_DESTINATAIRE = 'sthibault@dirickx.com';

// Expéditeur de l'email
const EMAIL_EXPEDITEUR = 'webmaster@districlos.com';
const EMAIL_EXPEDITEUR_NOM = 'Districlos';

// Tableau des abréviations de mois en français (3 premières lettres)
const MONTHS_ABBREV = [
    1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'AVR',
    5 => 'MAI', 6 => 'JUI', 7 => 'JUI', 8 => 'AOU',
    9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DEC'
];

// Noms complets des mois en français
const MONTHS_NAMES = [
    1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
    5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
    9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
];

try {
    // Vérification que la règle existe
    $cartRule = new CartRule(ID_CART_RULE);
    
    if (!Validate::isLoadedObject($cartRule)) {
        throw new Exception("La règle panier avec l'ID " . ID_CART_RULE . " n'existe pas.");
    }
    
    // Date actuelle (le cron tourne le 1er du mois)
    $now = new DateTimeImmutable();
    
    // Calcul des dates pour le mois en cours
    $firstDay = $now->modify('first day of this month');
    $lastDay = $firstDay->modify('last day of this month');
    
    $dateFrom = $firstDay->format('Y-m-d 00:00:00');
    $dateTo = $lastDay->format('Y-m-d 23:59:59');
    
    // Génération du code : "GROUP_" + abréviation du mois + année
    $monthNumber = (int)$now->format('n');
    $newCode = 'GROUP_' . MONTHS_ABBREV[$monthNumber] . $now->format('Y');
    
    // Mise à jour de la règle panier
    $cartRule->date_from = $dateFrom;
    $cartRule->date_to = $dateTo;
    $cartRule->code = $newCode;
    
    if ($cartRule->update()) {
        echo "✓ Mise à jour réussie pour la règle panier ID " . ID_CART_RULE . "\n";
        echo "  - Date de début : {$dateFrom}\n";
        echo "  - Date de fin : {$dateTo}\n";
        echo "  - Nouveau code : {$newCode}\n";

        // Envoi de l'email avec le code promo au destinataire
        if (!empty(EMAIL_DESTINATAIRE) && Validate::isEmail(EMAIL_DESTINATAIRE)) {
            $monthName = MONTHS_NAMES[$monthNumber];
            $emailMessage = "Bonjour,\n\n"
                . "Voici le code pour {$monthName} : {$newCode}\n\n"
                . "Chaque mois, un nouveau code sera généré suivant la même nomenclature.\n\n"
                . "Pour rappel, chaque mois ce sera :\n"
                . "- 5 utilisations / personne.\n"
                . "- Non cumulable avec les autres codes promos du site.\n"
                . "- Cumulable avec les remises sur nos produits.\n"
                . "- Pas de montant minimum de commande.\n"
                . "- Les codes appliquent une réduction de 20%\n\n"
                . "Bonne journée,";

            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
            $mailSent = Mail::Send(
                $idLang,
                'cron_remise_picot',
                'Code promo Remise Picot - ' . ucfirst($monthName) . ' : ' . $newCode,
                [
                    '{message}' => $emailMessage,
                    '{message_html}' => nl2br($emailMessage, false),
                ],
                EMAIL_DESTINATAIRE,
                null,
                EMAIL_EXPEDITEUR,
                EMAIL_EXPEDITEUR_NOM,
                null,
                null,
                _PS_MAIL_DIR_,
                false
            );
            if ($mailSent) {
                echo "  - Email envoyé à " . EMAIL_DESTINATAIRE . "\n";
            } else {
                echo "  - ⚠ Échec de l'envoi de l'email\n";
            }
        }
    } else {
        throw new Exception("Erreur lors de la mise à jour de la règle panier.");
    }
    
} catch (Exception $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
