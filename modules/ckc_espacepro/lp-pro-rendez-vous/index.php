<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

$assets = isset($baseUrl) ? rtrim($baseUrl, '/') . '/' : '';

$formSent  = $isConfirm ?? false;
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lp_submit'])) {
    $nom     = htmlspecialchars(trim($_POST['nom']       ?? ''), ENT_QUOTES);
    $prenom  = htmlspecialchars(trim($_POST['prenom']    ?? ''), ENT_QUOTES);
    $societe = htmlspecialchars(trim($_POST['societe']   ?? ''), ENT_QUOTES);
    $typePro = htmlspecialchars(trim($_POST['type_pro']  ?? ''), ENT_QUOTES);
    $tel     = htmlspecialchars(trim($_POST['telephone'] ?? ''), ENT_QUOTES);
    $email   = trim($_POST['email'] ?? '');
    $adresse = htmlspecialchars(trim($_POST['adresse']   ?? ''), ENT_QUOTES);

    if (empty($nom) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Veuillez renseigner au minimum votre nom et une adresse email valide.';
    } else {
        $sent = _lp_pro_send_mail($nom, $prenom, $societe, $typePro, $tel, $email, $adresse);
        if ($sent) {
            $scheme = Tools::usingSecureMode() ? 'https' : 'http';
            header('Location: ' . $scheme . '://' . Tools::getHttpHost() . rtrim(__PS_BASE_URI__, '/') . '/lp-pro-rendez-vous/remerciements');
            exit;
        }
        $formError = 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.';
    }
}

function _lp_pro_send_mail(
    string $nom, string $prenom, string $societe, string $typePro,
    string $tel, string $email, string $adresse
): bool {
    $recipients = [
        'commercial@districlos.com' => 'Commercial Districlos',
        'webmaster@districlos.com'  => 'Webmaster Districlos',
    ];

    $from     = 'formulaire@cloture-discount.fr';
    $fromName = 'Formulaire LP PRO';
    $subject  = 'Nouvelle demande PRO - ' . $nom . ' ' . $prenom;

    $templateVars = [
        '{nom}'     => $nom,
        '{prenom}'  => $prenom,
        '{societe}' => $societe ?: '-',
        '{type}'    => $typePro,
        '{tel}'     => $tel ?: '-',
        '{email}'   => $email,
        '{adresse}' => $adresse ?: '-',
    ];

    $idLang     = (int) Configuration::get('PS_LANG_DEFAULT');
    $idShopMain = (int) Configuration::get('PS_SHOP_DEFAULT');
    $tplPath    = _PS_MODULE_DIR_ . 'ckc_espacepro/mails/';
    $success    = true;

    foreach ($recipients as $toEmail => $toName) {
        $result = Mail::Send(
            $idLang,
            'lp_pro_contact',
            $subject,
            $templateVars,
            $toEmail,
            $toName,
            $from,
            $fromName,
            null,
            null,
            $tplPath,
            false,
            $idShopMain
        );
        if ($result === false) {
            $success = false;
            PrestaShopLogger::addLog(
                'LP PRO - Echec envoi email vers ' . $toEmail,
                3, null, 'LpProContact'
            );
        }
    }

    return $success;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programme PRO | Districlos</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Caveat:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assets ?>style.css">
</head>
<body>

<header class="lp-header">
    <img src="<?= $assets ?>img/logo-pro.png" alt="Districlos PRO"
         onerror="this.style.display='none'; document.getElementById('lp-logo-fallback').style.display='block'">
    <div id="lp-logo-fallback" class="lp-header__fallback">
        DISTRICLOS <span class="badge">PRO</span>
    </div>
</header>

<?php if (!$formSent): ?>
<section class="lp-hero">
    <p class="lp-hero__title">Vous êtes professionnel du bâtiment ou de l'aménagement extérieur&nbsp;?</p>
    <p class="lp-hero__subtitle">Accédez à nos avantages PRO dès la 1ère commande&nbsp;!</p>
    <p class="lp-hero__desc">Développez votre activité avec un partenaire solide et bénéficiez d'avantages exclusifs dès votre première commande.</p>
</section>
<?php endif; ?>

<?php if ($formSent): ?>
<section class="lp-confirm">
    <img class="lp-confirm__img lp-confirm__img--left"
         src="<?= $assets ?>img/travailleur.png" alt="Professionnel Districlos">
    <div class="lp-confirm__content">
        <span class="lp-confirm__icon">🎉</span>
        <p class="lp-confirm__title">Merci pour votre demande&nbsp;!</p>
        <p class="lp-confirm__text">Nos équipes vont traiter votre dossier et vous recontacter très prochainement.</p>
    </div>
    <img class="lp-confirm__img lp-confirm__img--right"
         src="<?= $assets ?>img/pack-portail.png" alt="Produits Districlos">
</section>
<?php else: ?>
<section class="lp-form-section">
    <div class="lp-form-inner"> 
        <?php if ($formError): ?>
            <div class="lp-alert lp-alert--error"><?= $formError ?></div>
        <?php endif; ?>
        <form method="post" action="" class="lp-form-grid">
            <div class="lp-field">
                <label for="lp_nom">Nom</label>
                <input id="lp_nom" type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
            </div>
            <div class="lp-field">
                <label for="lp_prenom">Prénom</label>
                <input id="lp_prenom" type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
            </div>
            <div class="lp-field">
                <label for="lp_societe">Société</label>
                <input id="lp_societe" type="text" name="societe" value="<?= htmlspecialchars($_POST['societe'] ?? '') ?>">
            </div>
            <div class="lp-field">
                <label for="lp_type">Type de professionnel</label>
                <select id="lp_type" name="type_pro">
                    <?php foreach (['installateur' => 'Installateur', 'artisan' => 'Artisan', 'paysagiste' => 'Paysagiste', 'macon' => 'Maçon', 'autre' => 'Autre'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= (($_POST['type_pro'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lp-field">
                <label for="lp_tel">Téléphone</label>
                <input id="lp_tel" type="tel" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
            </div>
            <div class="lp-field">
                <label for="lp_email">Email</label>
                <input id="lp_email" type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="lp-field lp-field--span2">
                <label for="lp_adresse">Adresse postale</label>
                <input id="lp_adresse" type="text" name="adresse" value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
            </div>
            <button type="submit" name="lp_submit" class="lp-submit">Je veux être recontacté</button>
        </form>
    </div>

    <div class="lp-catalogue">
        <a href="/index.php?controller=attachment&amp;id_attachment=362" target="_blank" rel="noopener" title="Télécharger le catalogue Pro">
            <img src="<?= $assets ?>img/catalogue-pro.png" alt="Catalogue Pro Districlos 2025"
                 onerror="this.style.opacity='.3'">
        </a>
        <p class="lp-catalogue__label">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <path d="M6 22 C6 14, 20 14, 22 6"/>
                <path d="M19 6 L22 6 L22 9"/>
            </svg>
            Télécharger le catalogue Pro
        </p>
    </div>
</section>

<?php endif; ?>

<?php if (!$formSent): ?>
<section class="lp-cards">
    <div class="lp-card">
        <img class="lp-card__bg" src="<?= $assets ?>img/Plan de travail 1.jpg" alt="100€ offert dès 1000€ d'achat">
    </div>
    <div class="lp-card">
        <img class="lp-card__bg" src="<?= $assets ?>img/Plan de travail 2.jpg" alt="Journées PRO">
    </div>
    <div class="lp-card">
        <img class="lp-card__bg" src="<?= $assets ?>img/Plan de travail 3.jpg" alt="Livraison offerte dès 450€">
    </div>
</section>

<section class="lp-why">
    <h2 class="lp-why__title">Pourquoi travailler avec Districlos&nbsp;?</h2>
    <div class="lp-why__grid">

        <div>
            <div class="lp-why__item">
                <div class="lp-why__num">1</div>
                <div class="lp-why__text">
                    <h4>Un partenaire fiable pour vos chantiers</h4>
                    <p>Nous sélectionnons des matériaux de qualité (aluminium, composite, bois, bambou…) majoritairement européens et garantis jusqu'à 10 ans. Avec 18 magasins en France et une équipe dédiée, nous assurons un suivi sérieux et réactif de vos commandes.</p>
                </div>
            </div>
            <div class="lp-why__item">
                <div class="lp-why__num">2</div>
                <div class="lp-why__text">
                    <h4>Une offre complète pour tous vos projets</h4>
                    <ul>
                        <li>Clôtures rigides et panneaux grillagés</li>
                        <li>Kits d'occultation et brise-vues</li>
                        <li>Portails et portillons</li>
                    </ul>
                    <p style="margin-top:10px">Que ce soit pour des projets résidentiels, collectifs, industriels ou commerciaux, nous avons les solutions adaptées à vos contraintes techniques et budgétaires.</p>
                </div>
            </div>
        </div>

        <div>
            <div class="lp-why__item">
                <div class="lp-why__num">3</div>
                <div class="lp-why__text">
                    <h4>Priorité sur vos commandes</h4>
                    <p>En tant que professionnel, vous bénéficiez d'un traitement prioritaire&nbsp;: livraison rapide et accès aux stocks.</p>
                </div>
            </div>
            <div class="lp-why__item">
                <div class="lp-why__num">4</div>
                <div class="lp-why__text">
                    <h4>Flexibilité garantie</h4>
                    <p>Suivez votre prestation en temps réel et modifiez ou annulez votre rendez-vous jusqu'à 48h à l'avance sans frais.</p>
                </div>
            </div>
            <div class="lp-why__item">
                <div class="lp-why__num">5</div>
                <div class="lp-why__text">
                    <h4>Reprise de marchandise</h4>
                    <p>En bon état et non rayé (hors soubassement). La récupération des produits s'effectue sous 3 mois maximum.</p>
                </div>
            </div>
        </div>

    </div>
</section>
<?php endif; ?>

<section class="lp-bar">
    <div class="lp-bar__item">
        <img class="lp-bar__icon" src="<?= $assets ?>img/coupon_blanc.svg" alt="Tarifs compétitifs">
        <p class="lp-bar__label">Tarifs compétitifs et avantages cumulables</p>
    </div>
    <div class="lp-bar__item">
        <img class="lp-bar__icon" src="<?= $assets ?>img/garanti_blanc.svg" alt="Produits garantis 10 ans">
        <p class="lp-bar__label">Produits garantis 10 ans</p>
    </div>
    <div class="lp-bar__item">
        <img class="lp-bar__icon" src="<?= $assets ?>img/carton_blanc.svg" alt="Large stock disponible">
        <p class="lp-bar__label">Large stock disponible</p>
    </div>
    <div class="lp-bar__item">
        <img class="lp-bar__icon" src="<?= $assets ?>img/france_pin2_blanc.svg" alt="Réseau national de magasins">
        <p class="lp-bar__label">Réseau national de magasins</p>
    </div>
    <div class="lp-bar__item">
        <img class="lp-bar__icon" src="<?= $assets ?>img/teleoperateur_blanc.svg" alt="Service client dédié">
        <p class="lp-bar__label">Service client dédié</p>
    </div>
</section>

<section class="lp-video">
    <div class="lp-video__text">
        <h3>Districlos, votre partenaire clôture partout en France</h3>
        <p>Avec 18 magasins et une expertise reconnue dans l'aménagement extérieur, nous accompagnons déjà des centaines de professionnels dans leurs projets.</p>
    </div>
    <div class="lp-video__frame">
        <iframe src="https://www.youtube.com/embed/RLDv5ANGbdg?rel=0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                title="Districlos est fier d'accompagner au quotidien ses pros"></iframe>
        <div class="lp-video__overlay">DISTRICLOS EST FIER D'ACCOMPAGNER AU QUOTIDIEN SES PROS</div>
    </div>
</section>

<section class="lp-reviews">
    <h2 class="lp-reviews__title">C'est votre avis qui compte&nbsp;!</h2>
    <p class="lp-reviews__subtitle">
        Déjà plus de <strong>2000 professionnels</strong> inscrits depuis <strong>15 ans d'expertise</strong> Districlos&nbsp;!
        Rejoignez notre communauté de professionnels dès maintenant.
    </p>

    <div class="lp-reviews__grid">
        <article class="lp-reviews__card">
            <div class="lp-reviews__avatar">
                <img src="<?= $assets ?>img/avis-pro-1.jpg" alt="Avis client professionnel 1">
            </div>
            <div class="lp-reviews__stars">★★★★★</div>
            <p class="lp-reviews__text">
                «&nbsp;Mise à dispo de la clôture super rapide, bien emballée pour éviter les rayures.
                De plus des raidisseurs sont fournis, ce qui facilite énormément la pose de la clôture&nbsp;!
                Merci beaucoup.&nbsp;»
            </p>
        </article>

        <article class="lp-reviews__card">
            <div class="lp-reviews__avatar">
                <img src="<?= $assets ?>img/avis-pro-2.jpg" alt="Avis client professionnel 2">
            </div>
            <div class="lp-reviews__stars">★★★★★</div>
            <p class="lp-reviews__text">
                «&nbsp;Produits visiblement de bonne qualité, prix plutôt compétitifs, livraison rapide et
                service client réactif. Je recommande.&nbsp;»
            </p>
        </article>

        <article class="lp-reviews__card">
            <div class="lp-reviews__avatar">
                <img src="<?= $assets ?>img/avis-pro-3.jpg" alt="Avis client professionnel 3">
            </div>
            <div class="lp-reviews__stars">★★★★★</div>
            <p class="lp-reviews__text">
                «&nbsp;Navigation fluide et simple sur le site web jusqu'à la commande, livraison rapide
                (si stock disponible), livraison soignée. La clôture est vraiment de très bonne qualité
                et simple à installer. Je recommande vivement.&nbsp;»
            </p>
        </article>
    </div>
</section>

</body>
</html>
