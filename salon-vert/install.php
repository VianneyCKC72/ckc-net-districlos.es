<?php
/**
 * One-shot : crée la table ps_salon_vert_inscription.
 * À lancer une fois : php salon-vert/install.php
 */
require __DIR__ . '/bootstrap.php';

try {
    sv_ensure_table();
    echo "OK – table `" . sv_table() . "` prête.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Erreur : ' . $e->getMessage() . "\n");
    exit(1);
}
