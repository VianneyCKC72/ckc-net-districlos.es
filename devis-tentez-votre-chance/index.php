<?php
header('Content-Type: text/html; charset=utf-8');

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/devis-tentez-votre-chance/index.php')), '/');
$devisTypeformUrl = 'https://form.typeform.com/to/yOwTEWUh';
$devisTypeformId = 'yOwTEWUh';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Districlos x Groupama-FDJ United</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/landing.css">
  <script>
    (function (w, d, s, l, i) {
      w[l] = w[l] || [];
      w[l].push({
        'gtm.start': new Date().getTime(), event: 'gtm.js'
      });
      var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
      j.async = true;
      j.src = 'https://cdn.cloture-discount.fr/lj59djkyo5o1ip3.js?aw=' + i.replace(/^GTM-/, '') + dl;
      f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-T3HJWLD');
  </script>
</head>
<body>
  <div class="gfu-wrap">
    <header class="gfu-header">
      <h1>Districlos x Groupama-FDJ United</h1>
      <p class="gfu-tagline">L&rsquo;exigence des champions pour vos projets de cl&ocirc;ture</p>
      <p class="gfu-stat">+ de 35 000 clients d&eacute;j&agrave; accompagn&eacute;s</p>
    </header>

    <div class="gfu-jeu-wrap">
      <section class="gfu-jeu-card" aria-label="Jeu concours">
        <div class="gfu-jeu-card-head">
          <span class="gfu-jeu-badge">Jeu concours</span>
        </div>
        <div class="gfu-jeu-left">
          <div class="gfu-trophy-block">
            <div class="gfu-trophy-icon">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/Groupe%208.png" alt="Trophée">
            </div>
            <h2 class="gfu-prize-title">2 places VIP pour l&rsquo;arriv&eacute;e du Tour de France &agrave; Paris&nbsp;!</h2>
            <p class="gfu-prize-detail">Transport + h&ocirc;tel + repas inclus</p>
          </div>
          <p class="gfu-prize-banner">Exp&eacute;rience exceptionnelle au plus pr&egrave;s de l&rsquo;&eacute;v&egrave;nement</p>
          <div class="gfu-cyclist-row">
            <div class="gfu-cyclist-cell">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/DSC_6906.png" alt="Coureurs Groupama-FDJ" width="300" height="400" loading="lazy">
            </div>
            <div class="gfu-cyclist-cell">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/DSC_9302.png" alt="Coureurs Groupama-FDJ" width="300" height="400" loading="lazy">
            </div>
          </div>
        </div>

        <div class="gfu-jeu-right" id="gfu-mobile-drawer">
          <button class="gfu-drawer-grip" type="button" aria-label="Ouvrir / fermer le menu du jeu concours"></button>
          <form class="gfu-form" action="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/submit.php" method="post" novalidate>
            <p class="gfu-form-head">Remplissez le formulaire<br>pour participer</p>
            <div class="gfu-form-fields">
              <label class="gfu-field">
                <span class="gfu-field-label">Nom</span>
                <input type="text" name="nom" required autocomplete="family-name">
              </label>
              <label class="gfu-field">
                <span class="gfu-field-label">Pr&eacute;nom</span>
                <input type="text" name="prenom" required autocomplete="given-name">
              </label>
              <label class="gfu-field">
                <span class="gfu-field-label">Email</span>
                <input type="email" name="email" required autocomplete="email">
              </label>
            </div>
            <button type="submit" class="gfu-devis-btn">Je confirme ma participation&nbsp;!</button>
          </form>
        </div>
      </section>
    </div>

    <div class="gfu-below" id="gfu-below">
      <section class="gfu-categories" aria-label="Catégories produits">
        <h2 class="gfu-categories-title">Districlos, votre expert clôtures, portails et brise-vues<br>depuis plus de 15 ans</h2>

        <div class="gfu-categories-grid">
          <div class="gfu-category-card">
            <div class="gfu-category-thumb">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/products/Plan%20de%20travail%201.png" alt="Grillage rigide" loading="lazy">
            </div>
            <p class="gfu-category-label">Grillage rigide</p>
          </div>

          <div class="gfu-category-card">
            <div class="gfu-category-thumb">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/products/Plan%20de%20travail%202.png" alt="Grillage souple" loading="lazy">
            </div>
            <p class="gfu-category-label">Grillage souple</p>
          </div>

          <div class="gfu-category-card">
            <div class="gfu-category-thumb">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/products/Plan%20de%20travail%203.png" alt="Portail &amp; portillon" loading="lazy">
            </div>
            <p class="gfu-category-label">Portail &amp; portillon</p>
          </div>

          <div class="gfu-category-card">
            <div class="gfu-category-thumb">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/products/Plan%20de%20travail%204.png" alt="Brise-vue" loading="lazy">
            </div>
            <p class="gfu-category-label">Brise-vue</p>
          </div>

          <div class="gfu-category-card">
            <div class="gfu-category-thumb">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/products/Plan%20de%20travail%205.png" alt="Clôture aluminium" loading="lazy">
            </div>
            <p class="gfu-category-label">Clôture aluminium</p>
          </div>

          <div class="gfu-category-card">
            <div class="gfu-category-thumb">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/products/Plan%20de%20travail%206.png" alt="Clôture composite" loading="lazy">
            </div>
            <p class="gfu-category-label">Clôture composite</p>
          </div>
        </div>

        <div class="gfu-categories-cta">
          <a class="gfu-cta-pill" href="<?= htmlspecialchars($devisTypeformUrl, ENT_QUOTES) ?>" data-tf-popup="<?= htmlspecialchars($devisTypeformId, ENT_QUOTES) ?>" data-tf-size="70" data-tf-opacity="100" data-tf-keep-session="true">Demander un devis</a>
        </div>
      </section>

      <section class="gfu-partner" aria-label="Partenariat Groupama-FDJ United">
        <div class="gfu-partner-top">
          <div class="gfu-partner-card">Partenaire d&rsquo;une &eacute;quipe<br>de haut niveau</div>
          <p class="gfu-partner-text">Depuis 2015, Districlos est partenaire officiel de l&rsquo;&eacute;quipe cycliste Groupama-FDJ United. Une collaboration durable, jusqu&rsquo;en 2027, qui refl&egrave;te notre engagement sur du long terme&nbsp;!</p>
        </div>

        <div class="gfu-partner-media">
          <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/DSC_4292.png" alt="Équipe cycliste Groupama-FDJ United" loading="lazy">
        </div>

        <div class="gfu-benefits" aria-label="Avantages Districlos">
          <div class="gfu-benefit">
            <div class="gfu-benefit-icon">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/icones/garanti.png" alt="" aria-hidden="true">
            </div>
            <div>
              <h3 class="gfu-benefit-title">Des produits fiables et performants</h3>
              <p class="gfu-benefit-desc">Comme dans le sport de haut niveau, nous s&eacute;lectionnons des produits durables et de qualit&eacute; pour vos ext&eacute;rieurs.</p>
            </div>
          </div>

          <div class="gfu-benefit">
            <div class="gfu-benefit-icon">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/icones/drapeauEU.png" alt="" aria-hidden="true">
            </div>
            <div>
              <h3 class="gfu-benefit-title">Des produits de fabrication europ&eacute;enne</h3>
              <p class="gfu-benefit-desc">Pour garantir qualit&eacute; et durabilit&eacute; dans le temps.</p>
            </div>
          </div>

          <div class="gfu-benefit">
            <div class="gfu-benefit-icon">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/icones/teleoperateur.png" alt="" aria-hidden="true">
            </div>
            <div>
              <h3 class="gfu-benefit-title">Un accompagnement d&rsquo;&eacute;quipe</h3>
              <p class="gfu-benefit-desc">Nos experts vous conseillent &agrave; chaque &eacute;tape de votre projet, du choix &agrave; la pose.</p>
            </div>
          </div>

          <div class="gfu-benefit">
            <div class="gfu-benefit-icon">
              <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/icones/france_pin2.png" alt="" aria-hidden="true">
            </div>
            <div>
              <h3 class="gfu-benefit-title">Proximit&eacute; et disponibilit&eacute;</h3>
              <p class="gfu-benefit-desc">Avec 18 magasins en France, nous sommes proches de chez vous.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="gfu-impact" aria-label="Concrètement, qu’est-ce que ça change pour vous ?">
        <div class="gfu-impact-top">
          <div class="gfu-impact-left">
            <h2 class="gfu-impact-title">Concrètement, qu&rsquo;est-ce que ça change pour vous&nbsp;?</h2>
            <p class="gfu-impact-text">Avec des millions de visiteurs sur notre site et des milliers de projets r&eacute;alis&eacute;s chaque ann&eacute;e, Districlos accompagne particuliers et professionnels dans leurs am&eacute;nagements ext&eacute;rieurs.</p>
            <p class="gfu-impact-text">Notre objectif&nbsp;: vous proposer des solutions simples, esth&eacute;tiques et durables pour s&eacute;curiser et valoriser votre maison&nbsp;!</p>
            <a class="gfu-impact-cta" href="<?= htmlspecialchars($devisTypeformUrl, ENT_QUOTES) ?>" data-tf-popup="<?= htmlspecialchars($devisTypeformId, ENT_QUOTES) ?>" data-tf-size="70" data-tf-opacity="100" data-tf-keep-session="true">Demander un devis</a>
          </div>

          <div class="gfu-impact-right" aria-label="Illustrations">
            <div class="gfu-impact-photos">
              <div class="gfu-impact-photo gfu-impact-photo--left">
                <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/groupama.png" alt="Groupama-FDJ United" loading="lazy">
              </div>
              <div class="gfu-impact-photo gfu-impact-photo--right">
                <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/biker.png" alt="Cycliste" loading="lazy">
              </div>
            </div>
          </div>
        </div>

        <div class="gfu-impact-banner">
          <img src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/img/DSC_6071.png" alt="Districlos x Groupama-FDJ United" loading="lazy">
        </div>
      </section>

      <section class="gfu-values" aria-label="Une correspondance des valeurs">
        <h2 class="gfu-values-title">Une correspondance des valeurs</h2>
        <p class="gfu-values-text">
          Depuis l&rsquo;arriv&eacute;e de Groupama en 2017 dans le cyclisme professionnel, l&rsquo;&eacute;quipe Groupama-FDJ accueille pour la premi&egrave;re fois un nouveau partenaire officiel sur son &eacute;quipement. Marc Madiot, manager g&eacute;n&eacute;ral, d&eacute;clare&nbsp;: &laquo;&nbsp;Nous sommes tr&egrave;s heureux d&rsquo;int&eacute;grer Districlos parmi nos partenaires. Ce partenariat est d&rsquo;autant plus fort gr&acirc;ce &agrave; la convergence de nos valeurs&nbsp;: l&rsquo;effort, la fid&eacute;lit&eacute; &agrave; nos racines, la proximit&eacute; avec notre territoire et la volont&eacute; de progresser ensemble. Nous embarquons toute la famille Districlos dans notre aventure&nbsp;&raquo;, a commenc&eacute; par les championnats de France. C&rsquo;est une association naturelle qui commence aujourd&rsquo;hui, l&rsquo;histoire ne fait que d&eacute;buter.&nbsp;&raquo; De son c&ocirc;t&eacute;, Thierry Cornec, directeur g&eacute;n&eacute;ral adjoint, ajoute&nbsp;: &laquo;&nbsp;L&rsquo;arriv&eacute;e de Districlos en tant que partenaire majeur marque une &eacute;tape cl&eacute; dans l&rsquo;&eacute;volution de notre projet sportif. Ce partenariat illustre la solidit&eacute; et l&rsquo;attractivit&eacute; de notre mod&egrave;le, ainsi que la confiance que des entreprises ambitieuses accordent au cyclisme de haut niveau.&nbsp;&raquo;
        </p>
      </section>
    </div>
  </div>

  <script src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>/landing.min.js" defer></script>
  <script src="//embed.typeform.com/next/embed.js" defer></script>
</body>
</html>
