<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Récupération et sécurisation des champs
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

    // Vérification email obligatoire
    if (empty($fields['Email'])) {
        header("Location: index.php?error=email");
        exit;
    }

    // Lecture / Incrémentation du compteur
    $compteurFile = 'compteur.txt';
    if (!file_exists($compteurFile)) {
        file_put_contents($compteurFile, "0");
    }

    $numero = (int) file_get_contents($compteurFile);
    $numero++;
    file_put_contents($compteurFile, $numero);
    $numero_formate = str_pad($numero, 2, "0", STR_PAD_LEFT);

    $to = "clients@districlos.com, yan.ledoux@districlos.com";
    $subject = "Demande d'encours #$numero_formate - Formulaire PRO";
    $headers = "From: formulaire@cloture-discount.fr\r\n";
    $headers .= "Reply-To: " . $fields['Email'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Construction du message
    $message = "Nouvelle demande d'encours reçue :\n\n";
    foreach ($fields as $label => $value) {
        $message .= "$label : " . (!empty($value) ? $value : "Champ non renseigné") . "\n";
    }

    // Envoi
    if (mail($to, $subject, $message, $headers)) {
        header("Location: index.php?success=1");
        exit;
    } else {
        header("Location: index.php?error=send");
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
<form action="index.php" method="POST">
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
        <label><input type="text" name="adresse_siege" placeholder="Adresse siége"></label>
        <label><input type="text" name="code_postal_siege" placeholder="Code postal"></label>
        <label><input type="text" name="ville_siege" placeholder="Ville"></label>
        <label><input type="text" name="pays_siege" placeholder="Pays"></label>
    </div>

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
