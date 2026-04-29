<?php
header('Content-Type: text/html; charset=utf-8');

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/devis-tentez-votre-chance/confirmation/index.php')), '/');
$basePath = preg_replace('~/(confirmation)$~', '', $basePath) ?? $basePath;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Demande envoyée</title>
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
  <meta name="robots" content="noindex, nofollow">
  <style>
    body {
      min-height: 100vh;
      margin: 0;
      background:
        radial-gradient(1200px 520px at 50% -140px, rgba(121, 192, 70, 0.22), rgba(2, 100, 115, 0.08) 55%, rgba(255, 255, 255, 0) 70%),
        linear-gradient(180deg, rgba(2, 100, 115, 0.06), rgba(255, 255, 255, 0) 45%),
        #fff;
    }

    .gfu-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 16px;
    }

    .gfu-thanks {
      margin: 0 auto;
      width: min(980px, 100%);
      background: var(--gfu-beige);
      border-radius: 12px;
      padding: 46px 34px;
      text-align: center;
      box-shadow: 0 18px 55px rgba(0, 0, 0, 0.12);
      position: relative;
    }

    .gfu-thanks-icon {
      width: 86px;
      height: 86px;
      margin: 0 auto 14px;
      border-radius: 999px;
      background: var(--gfu-green);
      display: grid;
      place-items: center;
    }

    .gfu-thanks-icon svg {
      width: 40px;
      height: 40px;
      display: block;
    }

    .gfu-thanks h1 {
      margin: 0 0 12px;
      font: 700 34px/1.15 Poppins, sans-serif;
      color: #000;
    }

    .gfu-thanks-sub {
      margin: 0 0 6px;
      font: 600 16px/1.4 Poppins, sans-serif;
      color: var(--gfu-teal);
    }
    .gfu-thanks p {
      margin: 0;
      font: 400 18px/1.6 Poppins, sans-serif;
      color: #000;
      opacity: 0.85;
    }
    @media (max-width: 640px) {
      .gfu-thanks {
        padding: 38px 20px;
      }
      .gfu-thanks h1 {
        font-size: 28px;
      }
      .gfu-thanks p {
        font-size: 16px;
      }
    }
  </style>
</head>
<body>
  <div class="gfu-wrap">
    <div class="gfu-thanks" role="status" aria-live="polite">
      <div class="gfu-thanks-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M20 7L10.5 16.5L4 10" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <p class="gfu-thanks-sub">Demande envoyée</p><br>
      <h1>Merci, votre demande a bien été envoyée.</h1><br>
      <p>Votre participation a bien été enregistrée pour le tirage au sort.</p><br>
      <p>Nos équipes reviendront vers vous dans les meilleurs délais.</p>
    </div>
  </div>
</body>
</html>
