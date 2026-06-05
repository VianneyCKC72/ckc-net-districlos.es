<?php
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/devis-tentez-votre-chance/submit.php')), '/');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . $basePath . '/');
    exit;
}

$nom = trim((string) ($_POST['nom'] ?? ''));
$prenom = trim((string) ($_POST['prenom'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

$valid = $nom !== '' && $prenom !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);

if (!$valid) {
    header('Location: ' . $basePath . '/?error=1');
    exit;
}

$row = [
    date('c'),
    $_SERVER['REMOTE_ADDR'] ?? '',
    $nom,
    $prenom,
    $email,
];

$file = __DIR__ . '/participations.csv';
$handle = @fopen($file, 'a');
if ($handle !== false) {
    if (@flock($handle, LOCK_EX)) {
        if (filesize($file) === 0) {
            fputcsv($handle, ['date', 'ip', 'nom', 'prenom', 'email']);
        }
        fputcsv($handle, $row);
        @flock($handle, LOCK_UN);
    }
    fclose($handle);
}

header('Location: ' . $basePath . '/confirmation/');
exit;
