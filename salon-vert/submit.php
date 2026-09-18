<?php
require __DIR__ . '/bootstrap.php';

$basePath = sv_base_path();
$lang = sv_lang();
$langQuery = 'lang=' . rawurlencode($lang);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . $basePath . '/?' . $langQuery);
    exit;
}

$nom = trim((string) ($_POST['nom'] ?? ''));
$prenom = trim((string) ($_POST['prenom'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$telephone = trim((string) ($_POST['telephone'] ?? ''));
$societe = trim((string) ($_POST['societe'] ?? ''));
$acceptJeu = !empty($_POST['accept_jeu']);
$acceptCom = !empty($_POST['accept_com']);

$valid = $nom !== ''
    && $prenom !== ''
    && $societe !== ''
    && $telephone !== ''
    && filter_var($email, FILTER_VALIDATE_EMAIL)
    && $acceptJeu;

if (!$valid) {
    header('Location: ' . $basePath . '/?' . $langQuery . '&error=1');
    exit;
}

try {
    sv_register([
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone,
        'societe' => $societe,
        'accept_jeu' => $acceptJeu,
        'accept_com' => $acceptCom,
    ]);
} catch (Throwable $e) {
    header('Location: ' . $basePath . '/?' . $langQuery . '&error=1');
    exit;
}

sv_set_registered_cookie($email);

header('Location: ' . $basePath . '/?' . $langQuery . '&ok=1');
exit;
