<?php
/**
 * Landing page "Rendez-vous PRO" – page stand-alone sans layout PrestaShop.
 * Servi par Ckc_EspaceproLprendezvousModuleFrontController.
 * La variable $baseUrl est injectée par le contrôleur.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$assets = isset($baseUrl) ? rtrim($baseUrl, '/') . '/' : '';

// ─── Traitement du formulaire ────────────────────────────────────────────────
$formSent  = false;
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
        $formSent = _lp_pro_send_mail($nom, $prenom, $societe, $typePro, $tel, $email, $adresse);
        if (!$formSent) {
            $formError = 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.';
        }
    }
}

/**
 * Envoi du formulaire PRO via Mail::Send() de PrestaShop.
 * Utilise le template mails/{lang}/lp_pro_contact.html du module.
 */
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
    $subject  = 'Nouvelle demande PRO – ' . $nom . ' ' . $prenom;

    $templateVars = [
        '{nom}'     => $nom,
        '{prenom}'  => $prenom,
        '{societe}' => $societe ?: '—',
        '{type}'    => $typePro,
        '{tel}'     => $tel ?: '—',
        '{email}'   => $email,
        '{adresse}' => $adresse ?: '—',
    ];

    $idLang    = (int) Context::getContext()->language->id;
    $idShop    = (int) Context::getContext()->shop->id;
    $tplPath   = _PS_MODULE_DIR_ . 'ckc_espacepro/mails/';
    $success   = true;

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
            $idShop
        );
        if ($result === false) {
            $success = false;
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
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Poppins', sans-serif;
            color: #1a1a1a;
            background: #fff;
            line-height: 1.55;
        }

        /* ─── HEADER ──────────────────────────────────────────────────────── */
        .lp-header {
            padding: 28px 20px 20px;
            text-align: center;
            background: #fff;
        }
        .lp-header img { max-height: 72px; width: auto; }
        .lp-header__fallback {
            display: none;
            font-size: 1.7rem;
            font-weight: 900;
            letter-spacing: -.5px;
        }
        .lp-header__fallback .badge {
            background: #FFA400;
            color: #fff;
            font-size: 1rem;
            padding: 2px 10px;
            border-radius: 5px;
            vertical-align: middle;
            margin-left: 4px;
        }

        /* ─── HERO ────────────────────────────────────────────────────────── */
        .lp-hero {
            padding: 30px 6% 10px;
            text-align: center;
        }
        .lp-hero__title {
            font-size: clamp(1.05rem, 2vw, 1.4rem);
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        .lp-hero__subtitle {
            font-size: clamp(1.15rem, 2.3vw, 1.55rem);
            font-weight: 800;
            color: #FFA400;
            margin-bottom: 14px;
        }
        .lp-hero__desc {
            font-size: .92rem;
            margin: 0 auto;
        }

        /* ─── FORM SECTION ────────────────────────────────────────────────── */
        .lp-form-section {
            margin: 28px 6%;
            background: #F5EDE0;
            border-radius: 14px;
            padding: 40px 48px;
            display: flex;
            gap: 48px;
            align-items: flex-start;
            justify-content: center;
            position: relative;
        }
        .lp-form-inner {
            flex: 0 1 360px;
            min-width: 0;
        }

        .lp-form-row {
            display: flex;
            gap: 18px;
            margin-bottom: 18px;
        }
        .lp-field {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }
        .lp-field label {
            font-size: .82rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .lp-field input,
        .lp-field select {
            padding: 10px 14px;
            border: none;
            border-radius: 26px;
            background: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: .88rem;
            color: #1a1a1a;
            outline: none;
            transition: box-shadow .2s;
        }
        .lp-field input:focus,
        .lp-field select:focus {
            box-shadow: 0 0 0 2.5px #FFA40066;
        }
        .lp-field--md  { max-width: 100%; }
        .lp-field--sm  { max-width: 180px; }

        .lp-submit {
            margin-top: 12px;
            background: #FFA400;
            color: #fff;
            border: none;
            padding: 13px 36px;
            border-radius: 30px;
            font-family: 'Poppins', sans-serif;
            font-size: .95rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 14px rgba(247,148,29,.35);
        }
        .lp-submit:hover {
            background: #e07e10;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247,148,29,.45);
        }

        .lp-alert {
            padding: 14px 18px;
            border-radius: 8px;
            font-size: .88rem;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .lp-alert--error { background: #fdecea; border: 1px solid #ef9a9a; color: #c62828; }

        /* ─── CATALOGUE ───────────────────────────────────────────────────── */
        .lp-catalogue {
            position: absolute;
            bottom: 20px;
            right: 42px;
            text-align: center;
            width: 160px;
        }
        .lp-catalogue a { display: inline-block; text-decoration: none; }
        .lp-catalogue img {
            width: 100%;
        }
        .lp-catalogue__label {
            margin-top: 10px;
            font-family: 'Caveat', cursive;
            font-size: 1.15rem;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
        }
        .lp-catalogue__label svg { flex-shrink: 0; }

        /* ─── AVANTAGES CARDS ─────────────────────────────────────────────── */
        .lp-cards {
            display: grid;
            grid-template-columns: 922fr 467fr 467fr;
            gap: 48px;
        }
        .lp-card { overflow: hidden; }
        .lp-card__bg {
            display: block;
            width: 100%;
            height: auto;
        }

        /* ─── POURQUOI DISTRICLOS ─────────────────────────────────────────── */
        .lp-why {
            padding: 60px 6%;
            background: #F6F2EB 0% 0% no-repeat padding-box;
            margin: 70px 0;
        }
        .lp-why__title {
            font-size: clamp(1rem, 2vw, 1.2rem);
            font-weight: 800;
            margin-bottom: 40px;
        }
        .lp-why__grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 70px;
        }
        .lp-why__item {
            display: flex;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 32px;
        }
        .lp-why__num {
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #FFA400;
            color: #fff;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2px;
        }
        .lp-why__text h4 {
            font-size: .95rem;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
            margin-bottom: 8px;
        }
        .lp-why__text p,
        .lp-why__text ul {
            font-size: .88rem;
            color: #555;
            line-height: 1.7;
        }
        .lp-why__text ul { list-style: none; }
        .lp-why__text ul li::before { content: '• '; color: #FFA400; font-weight: 700; }

        /* ─── BANDE ORANGE ────────────────────────────────────────────────── */
        .lp-bar {
            background: #FFA400;
            padding: 32px 5%;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 24px;
        }
        .lp-bar__item {
            text-align: center;
            color: #fff;
            max-width: 130px;
        }
        .lp-bar__icon {
            width: auto;
            height: 92px;
            margin: 0 auto 10px;
            display: block;
        }
        .lp-bar__item:nth-child(3) .lp-bar__icon {
            height: 58px;
            margin: 20px auto 24px;
        }
        .lp-bar__label {
            font-size: .82rem;
            font-weight: 600;
            line-height: 1.45;
        }

        /* ─── SECTION VIDÉO ───────────────────────────────────────────────── */
        .lp-video {
            padding: 60px 6%;
            display: flex;
            gap: 60px;
            align-items: center;
        }
        .lp-video__text { flex: 1; }
        .lp-video__text h3 {
            font-size: clamp(1rem, 2vw, 1.2rem);
            font-weight: 800;
            margin-bottom: 14px;
        }
        .lp-video__text p {
            font-size: .9rem;
            color: #555;
            line-height: 1.75;
        }
        .lp-video__frame {
            flex: 1;
            position: relative;
            border: 4px solid #FFA400;
            border-radius: 6px;
            overflow: hidden;
            max-width: 480px;
        }
        .lp-video__frame video,
        .lp-video__frame iframe {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 9;
            border: none;
            background: #000;
        }
        .lp-video__overlay {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: rgba(0,0,0,.55);
            color: #fff;
            text-align: center;
            padding: 10px 16px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            pointer-events: none;
        }

        /* ─── RESPONSIVE ──────────────────────────────────────────────────── */
        @media (max-width: 960px) {
            .lp-form-section { flex-direction: column; padding: 28px 24px; }
            .lp-catalogue    { flex: none; width: 100%; max-width: 200px; margin: 0 auto; }
            .lp-cards        { grid-template-columns: 1fr; }
            .lp-cards .lp-card__bg { width: 100%; }
            .lp-why__grid    { grid-template-columns: 1fr; gap: 0; }
            .lp-video        { flex-direction: column; }
            .lp-video__frame { max-width: 100%; }
        }
        @media (max-width: 600px) {
            .lp-form-section  { margin: 16px 4%; padding-bottom: 28px; }
            .lp-form-row      { flex-direction: column; gap: 14px; }
            .lp-field--md,
            .lp-field--sm     { max-width: none; }
            .lp-hero, .lp-why, .lp-video { padding-left: 4%; padding-right: 4%; }

            /* Catalogue : sort du flux absolu, s'affiche sous le formulaire */
            .lp-catalogue {
                position: static;
                width: 100%;
                margin-top: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 16px;
            }
            .lp-catalogue a { flex-shrink: 0; width: 90px; }
            .lp-catalogue__label {
                white-space: normal;
                text-align: left;
                justify-content: flex-start;
                font-size: 1.05rem;
            }
        }

        /* ─── CONFIRMATION ────────────────────────────────────────────────── */
        .lp-confirm {
            margin: 28px 6%;
            background: #F5EDE0;
            border-radius: 14px;
            min-height: 400px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 60px 300px;
        }
        .lp-confirm__img {
            position: absolute;
            bottom: 0;
            height: 100%;
            width: auto;
            object-fit: contain;
            object-position: bottom;
        }
        .lp-confirm__img--left  { left: 0; }
        .lp-confirm__img--right { right: 0; height: 80%; }
        .lp-confirm__content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .lp-confirm__icon {
            font-size: 2.2rem;
            margin-bottom: 18px;
            display: block;
        }
        .lp-confirm__title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        .lp-confirm__text {
            font-size: .92rem;
            line-height: 1.65;
            max-width: 340px;
            margin: 0 auto;
        }
        @media (max-width: 900px) {
            .lp-confirm { padding: 40px 20px; min-height: 300px; }
            .lp-confirm__img { height: 60%; }
        }
        @media (max-width: 600px) {
            .lp-confirm {
                padding: 36px 20px 0;
                min-height: 480px;
                align-items: flex-start;
            }
            .lp-confirm__content { width: 100%; }
            .lp-confirm__img {
                display: block;
                height: 52%;
            }
            .lp-confirm__img--left {
                left: 0;
                z-index: 1;
            }
            .lp-confirm__img--right {
                right: auto;
                left: 30%;
                height: 42%;
                bottom: -3%;
                z-index: 2;
            }
        }
    </style>
</head>
<body>

<!-- ═══ HEADER ═══════════════════════════════════════════════════════════════ -->
<header class="lp-header">
    <img src="<?= $assets ?>img/logo-pro.png" alt="Districlos PRO"
         onerror="this.style.display='none'; document.getElementById('lp-logo-fallback').style.display='block'">
    <div id="lp-logo-fallback" class="lp-header__fallback">
        DISTRICLOS <span class="badge">PRO</span>
    </div>
</header>

<?php if (!$formSent): ?>
<!-- ═══ HERO ══════════════════════════════════════════════════════════════════ -->
<section class="lp-hero">
    <p class="lp-hero__title">Vous êtes professionnel du bâtiment ou de l'aménagement extérieur&nbsp;?</p>
    <p class="lp-hero__subtitle">Rejoignez notre programme professionnel&nbsp;!</p>
    <p class="lp-hero__desc">Développez votre activité avec un partenaire solide et bénéficiez d'avantages exclusifs dès votre première commande.</p>
</section>
<?php endif; ?>

<?php if ($formSent): ?>
<!-- ═══ CONFIRMATION ══════════════════════════════════════════════════════════ -->
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
<!-- ═══ FORMULAIRE + CATALOGUE ════════════════════════════════════════════════ -->
<section class="lp-form-section">
    <div class="lp-form-inner">
        <?php if ($formError): ?>
            <div class="lp-alert lp-alert--error"><?= $formError ?></div>
        <?php endif; ?>
        <form method="post" action="">
                <div class="lp-form-row">
                    <div class="lp-field">
                        <label for="lp_nom">Nom</label>
                        <input id="lp_nom" type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                    </div>
                    <div class="lp-field">
                        <label for="lp_prenom">Prénom</label>
                        <input id="lp_prenom" type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                    </div>
                </div>
                <div class="lp-form-row">
                    <div class="lp-field lp-field--md">
                        <label for="lp_societe">Société</label>
                        <input id="lp_societe" type="text" name="societe" value="<?= htmlspecialchars($_POST['societe'] ?? '') ?>">
                    </div>
                </div>
                <div class="lp-form-row">
                    <div class="lp-field lp-field--sm">
                        <label for="lp_type">Type de professionnel</label>
                        <select id="lp_type" name="type_pro">
                            <?php foreach (['installateur' => 'Installateur', 'artisan' => 'Artisan', 'paysagiste' => 'Paysagiste', 'macon' => 'Maçon'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= (($_POST['type_pro'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="lp-form-row">
                    <div class="lp-field">
                        <label for="lp_tel">Téléphone</label>
                        <input id="lp_tel" type="tel" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                    </div>
                    <div class="lp-field">
                        <label for="lp_email">Email</label>
                        <input id="lp_email" type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="lp-form-row">
                    <div class="lp-field lp-field--md">
                        <label for="lp_adresse">Adresse postale</label>
                        <input id="lp_adresse" type="text" name="adresse" value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" name="lp_submit" class="lp-submit">Je veux être recontacté</button>
        </form>
    </div>

    <div class="lp-catalogue">
        <a href="<?= $assets ?>docs/Cat_Districlos_2026.pdf" target="_blank" rel="noopener" title="Télécharger le catalogue Pro">
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
<!-- ═══ CARTES AVANTAGES ══════════════════════════════════════════════════════ -->
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

<!-- ═══ POURQUOI DISTRICLOS ═══════════════════════════════════════════════════ -->
<section class="lp-why">
    <h2 class="lp-why__title">Pourquoi travailler avec Districlos&nbsp;?</h2>
    <div class="lp-why__grid">

        <div>
            <div class="lp-why__item">
                <div class="lp-why__num">1</div>
                <div class="lp-why__text">
                    <h4>Un partenaire fiable pour vos chantiers</h4>
                    <p>Nous sélectionnons des matériaux de qualité (aluminium, composite, bois, bambou…) majoritairement européens et garantis jusqu'à 10 ans. Avec 17 magasins en France et une équipe dédiée, nous assurons un suivi sérieux et réactif de vos commandes.</p>
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
                    <p style="margin-top:10px">Que ce soit pour des projets résidentiels, collectifs ou commerciaux, nous avons les solutions adaptées à vos contraintes techniques et budgétaires.</p>
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

<!-- ═══ BANDE ORANGE ══════════════════════════════════════════════════════════ -->
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

<!-- ═══ VIDÉO + TEXTE ═════════════════════════════════════════════════════════ -->
<section class="lp-video">
    <div class="lp-video__text">
        <h3>Districlos, votre partenaire clôture partout en France</h3>
        <p>Avec 17 magasins et une expertise reconnue dans l'aménagement extérieur, nous accompagnons déjà des centaines de professionnels dans leurs projets.</p>
    </div>
    <div class="lp-video__frame">
        <iframe src="https://www.youtube.com/embed/RLDv5ANGbdg?rel=0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                title="Districlos est fier d'accompagner au quotidien ses pros"></iframe>
        <div class="lp-video__overlay">DISTRICLOS EST FIER D'ACCOMPAGNER AU QUOTIDIEN SES PROS</div>
    </div>
</section>

</body>
</html>
