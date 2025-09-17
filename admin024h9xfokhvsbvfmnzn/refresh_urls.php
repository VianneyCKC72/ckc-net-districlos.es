<?php
// Liste des URL à actualiser
$urlsToRefresh = array(
    'https://www.cloture-discount.fr/modules/divaltoInfinityConnec/divaltoOpartdevis.php?step=01'
);

// Parcourir la liste des URL et les actualiser
foreach ($urlsToRefresh as $urlToRefresh) {
    $response = file_get_contents($urlToRefresh);

    if ($response === false) {
        echo 'Erreur lors de l\'actualisation de l\'URL : ' . $urlToRefresh . '<br>';
    } else {
        echo 'URL actualisée avec succès : ' . $urlToRefresh . '<br>';
    }
}
