<?php

// PHPMailer pour envoi des documents en PJ
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function safe($key) {
        return isset($_POST[$key]) ? htmlspecialchars(trim($_POST[$key])) : '';
    }

    $fields = [
        'Société' => safe('societe'),
        'Nom' => safe('nom'),
        'Prénom' => safe('prenom'),
        'Adresse' => safe('adresse'),
        'Code postal' => safe('code_postal'),
        'Ville' => safe('ville'),
        'Pays' => safe('pays'),
        'Téléphone' => safe('telephone'),
        'Email' => safe('email'),
        'Statut de l\'entreprise' => safe('statut'),
        'SIRET' => safe('siret'),
        'N° TVA' => safe('tva'),
        'Régime de TVA' => safe('regime_tva'),
        'Code APE (NAF)' => safe('ape'),
        'Site web' => safe('site_web'),
        'Adresse siège' => safe('adresse_siege'),
        'Code postal siège' => safe('code_postal_siege'),
        'Ville siège' => safe('ville_siege'),
        'Pays siège' => safe('pays_siege'),
        'Nombre de chantiers annuels' => safe('nb_chantiers'),
        'Mode de règlement' => safe('mode_reglement'),
        'Montant en cours souhaité (HT)' => safe('montant_encours')
    ];

    // Vérification des fichiers obligatoires
    $maxSize = 5 * 1024 * 1024; // 5 Mo

    function check_upload($fieldName, $label, $required = true) {
        global $maxSize;
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            if ($required) {
                header("Location: index.php?error=missing_$fieldName");
                exit;
            }
            return null;
        }
        if ($_FILES[$fieldName]['size'] > $maxSize) {
            header("Location: index.php?error=size_$fieldName");
            exit;
        }
        return $_FILES[$fieldName];
    }

    $kbisFile = check_upload('kbis', 'KBIS');
    $ribFile = check_upload('rib', 'RIB');
    $bilanFile = check_upload('bilan', 'Bilan', false); // facultatif

    if (empty($fields['Email'])) {
        header("Location: index.php?error=email");
        exit;
    }

    // Compteur de formulaires
    $compteurFile = 'compteur.txt';
    if (!file_exists($compteurFile)) {
        file_put_contents($compteurFile, "0");
    }
    $numero = (int) file_get_contents($compteurFile);
    $numero++;
    file_put_contents($compteurFile, $numero);
    $numero_formate = str_pad($numero, 2, "0", STR_PAD_LEFT);

    // Construction du message
    $message = "Nouvelle demande d'encours reçue :\n\n";
    foreach ($fields as $label => $value) {
        $message .= "$label : " . (!empty($value) ? $value : "Champ non renseigné") . "\n";
    }

    // Envoi avec PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->setFrom('formulaire@cloture-discount.fr', 'Formulaire PRO');
        $mail->addAddress('clients@districlos.com');
        $mail->addAddress('yan.ledoux@districlos.com');
        $mail->addAddress('kevin.ballari@districlos.com');
        $mail->addReplyTo($fields['Email']);

        $mail->isHTML(false);
        $mail->Subject = "Demande d'encours #$numero_formate - Formulaire PRO";
        $mail->Body = $message;

        // Ajout des pièces jointes
        $mail->addAttachment($kbisFile['tmp_name'], 'KBIS_' . basename($kbisFile['name']));
        $mail->addAttachment($ribFile['tmp_name'], 'RIB_' . basename($ribFile['name']));
        if ($bilanFile) {
            $mail->addAttachment($bilanFile['tmp_name'], 'Bilan_' . basename($bilanFile['name']));
        }

        $mail->send();
        header("Location: index.php?success=1");
        exit;
    } catch (Exception $e) {
        header("Location: index.php?error=sendmail");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'encours - PRO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<form action="index.php" method="POST" enctype="multipart/form-data">
    <div class="pos_logo">
        <img src="DISTRICLOS_pro_logo.webp" class="logo">
    </div>

    <h1>Demande d’encours pour les comptes PRO</h1>

    <h2>Coordonnées</h2>
    <label>
        <input type="text" name="societe" placeholder="Société *" required>
    </label>
    <label>
        <input type="text" name="nom" placeholder="Nom *" required>
    </label>
    <label>
        <input type="text" name="prenom" placeholder="Prénom *" required>
    </label>
    <label>
        <input type="text" name="adresse" placeholder="Adresse *" required>
    </label>
    <label>
        <input type="text" name="code_postal" placeholder="Code postal *" required>
    </label>
    <label>
        <input type="text" name="ville" placeholder="Ville *" required>
    </label>
    <label>
        <input type="text" name="pays" placeholder="Pays *" required>
    </label>
    <label>
        <input type="text" name="telephone" placeholder="Téléphone *" required>
    </label>
    <label>
        <input type="email" name="email" placeholder="Email *" required>
    </label>

    <h2>Votre entreprise</h2>
    <label><input type="text" name="statut" placeholder="Statut de l'entreprise *" required></label>
    <label><input type="text" name="siret" placeholder="Siret *" required></label>
    <label><input type="text" name="tva" placeholder="No TVA intracommunautaire" required></label>
    <label><input type="text" name="regime_tva" placeholder="Régime de TVA" required></label>
    <label><input type="text" name="ape" placeholder="Code APE (NAF) *" required></label>
    <label><input type="url" name="site_web" placeholder="Site web"></label>

    <label style="display: flex; align-items: center"> <span>Adresse siège différente</span><input type="checkbox" id="diff_adresse" onchange="toggleSiege()" style="width: auto; margin-left: 10px"> </label>
    <div id="siege_fields" style="display:none">
        <label><input type="text" name="adresse_siege" placeholder="Adresse siège"></label>
        <label><input type="text" name="code_postal_siege" placeholder="Code postal"></label>
        <label><input type="text" name="ville_siege" placeholder="Ville"></label>
        <label><input type="text" name="pays_siege" placeholder="Pays"></label>
    </div>

    <h2>Documents</h2>

    <label>
        KBIS (moins de 3 mois) *<br>
        <input type="file" name="kbis" accept=".pdf,image/*" required>
    </label>
    <label>
        RIB *<br>
        <input type="file" name="rib" accept=".pdf,image/*" required>
    </label>
    <label>
        Bilan (facultatif)<br>
        <input type="file" name="bilan" accept=".pdf,image/*">
    </label>

    <h2>Encours</h2>
    <label><input type="number" name="nb_chantiers" placeholder="Nombre de chantier annuel"></label>
    <label>
        <select name="mode_reglement" style="width: 30%" required>
            <option value="">Mode de réglement *</option>
            <option value="virement">Virement à 30 jours</option>
            <option value="lcr">LCR 30 jours fin de mois</option>
        </select>
    </label>
    <label><input type="number" name="montant_encours" placeholder="Montant en cours souhaité (HT) *" required></label>

    <button type="submit" style="background: #ffa400">Envoyer</button>
</form>

<script>
    function toggleSiege() {
        const fields = document.getElementById('siege_fields');
        fields.style.display = document.getElementById('diff_adresse').checked ? 'block' : 'none';
    }

    function onSubmit(token) {
        document.querySelector("form").submit();
    }
</script>
</body>
</html>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <div class="icon-circle">
                <span class="checkmark">✔</span>
            </div>
            <h2>Demande envoyée avec succès !</h2>
            <p>Votre demande a bien été prise en compte.<br>
            Notre équipe va la traiter dans les plus brefs délais,<br>
            généralement sous 48 heures ouvrées.</p>
            <button onclick="closeModal()">Fermer</button>
        </div>
    </div>

    <style>
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex; justify-content: center; align-items: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            animation: slideIn 0.4s ease-out;
        }

        .icon-circle {
            background: #4CAF50;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: popIn 0.3s ease;
        }

        .checkmark {
            color: white;
            font-size: 30px;
            line-height: 1;
        }

        .modal-content h2 {
            margin-top: 0;
            color: #2e7d32;
        }

        .modal-content p {
            margin: 10px 0 20px;
            font-size: 15px;
            color: #444;
        }

        .modal-content button {
            padding: 10px 20px;
            background: #4CAF50;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .modal-content button:hover {
            background: #388e3c;
        }

        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>

    <script>
        function closeModal() {
            document.getElementById("successModal").style.display = "none";
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
<?php endif; ?>
