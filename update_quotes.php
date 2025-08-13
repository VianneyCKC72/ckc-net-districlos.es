<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require(__DIR__.'/config/config.inc.php');
require_once(__DIR__.'/init.php');

$REMOTE_DSN  = 'mysql:host=87.98.141.171;dbname=districlosnew;charset=utf8';
$REMOTE_USER = 'districlosnew';
$REMOTE_PWD  = '11Atb2I20TJ6joDc';

$STATUS_FIELD = 'status';

$LOCAL_TABLE  = _DB_PREFIX_.'opartdevis';
$REMOTE_TABLE = 'ps_opartdevis';

try {
    $remoteDb = new PDO(
        $REMOTE_DSN,
        $REMOTE_USER,
        $REMOTE_PWD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $localQuotes = Db::getInstance()->executeS('SELECT * FROM '.$LOCAL_TABLE.' WHERE date_add > "2025-07-15"');

    if (!$localQuotes) {
        echo "Aucun devis local trouvé.<br>";
        exit;
    }

    $stmtFetchRemote = $remoteDb->prepare("SELECT * FROM {$REMOTE_TABLE} WHERE id_opartdevis = :id_opartdevis");
    $stmtUpdateRemote = $remoteDb->prepare("UPDATE {$REMOTE_TABLE} SET {$STATUS_FIELD} = :status WHERE id_opartdevis = :id_opartdevis");

    $updatedLocal   = 0;
    $updatedRemote  = 0;
    $skippedMissing = 0;
    $unchanged      = 0;

    foreach ($localQuotes as $quote) {
        $id = (int)$quote['id_opartdevis'];

        $localStatus = isset($quote[$STATUS_FIELD]) ? (int)$quote[$STATUS_FIELD] : 0;

        $stmtFetchRemote->execute(['id_opartdevis' => $id]);
        $remoteQuote = $stmtFetchRemote->fetch(PDO::FETCH_ASSOC);

        if (!$remoteQuote) {
            $skippedMissing++;
            echo "Devis #{$id} introuvable dans la base distante, ignoré.<br>";
            continue;
        }

        $remoteStatus = isset($remoteQuote[$STATUS_FIELD]) ? (int)$remoteQuote[$STATUS_FIELD] : 0;

        $finalStatus = max($localStatus, $remoteStatus);

        if ($localStatus !== $finalStatus) {
            Db::getInstance()->update(
                'opartdevis',
                [$STATUS_FIELD => (int)$finalStatus],
                'id_opartdevis = '.(int)$id
            );
            $updatedLocal++;
            echo "Local: devis #{$id} statut {$localStatus} -> {$finalStatus}<br>";
        }

        if ($remoteStatus !== $finalStatus) {
            $stmtUpdateRemote->execute([
                'status'         => $finalStatus,
                'id_opartdevis'  => $id,
            ]);
            $updatedRemote++;
            echo "Distant: devis #{$id} statut {$remoteStatus} -> {$finalStatus}<br>";
        }

        if ($localStatus === $finalStatus && $remoteStatus === $finalStatus) {
            $unchanged++;
        }
    }

    echo "<br>--- Récapitulatif ---<br>";
    echo "Mises à jour locales : {$updatedLocal}<br>";
    echo "Mises à jour distantes : {$updatedRemote}<br>";
    echo "Dévis manquants côté distant : {$skippedMissing}<br>";
    echo "Déjà synchronisés : {$unchanged}<br>";

} catch (Exception $e) {
    echo "Erreur: ".$e->getMessage()."<br>";
    exit(1);
}
