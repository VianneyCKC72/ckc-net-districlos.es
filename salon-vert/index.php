<?php
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/bootstrap.php';

$basePath = sv_base_path();
$lang = sv_lang();
$shopRoot = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
if ($shopRoot === '/' || $shopRoot === '\\') {
    $shopRoot = '';
}
$shopUrl = 'https://pro.cloture-discount.fr';
$error = !empty($_GET['error']);
$success = !empty($_GET['ok']) || sv_is_registered_cookie();
$promoCode = 'EVESVE';

/** Asset local à /salon-vert/ */
$lp = static function (string $path) use ($basePath): string {
    return htmlspecialchars($basePath . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};

/** Asset déjà présent à la racine PrestaShop */
$shop = static function (string $path) use ($shopRoot): string {
    return htmlspecialchars($shopRoot . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= sv_e('meta_title') ?></title>
  <meta name="description" content="<?= sv_e('meta_description') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $lp('styles.css') ?>">
  <link rel="icon" href="<?= $shop('img/favicon.ico') ?>">
</head>
<body>
  <main class="sv">
    <div class="sv-lang" role="navigation" aria-label="<?= sv_e('lang_switch') ?>">
      <a class="sv-lang__link<?= $lang === 'fr' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(sv_lang_url('fr'), ENT_QUOTES, 'UTF-8') ?>" hreflang="fr" lang="fr"><?= sv_e('lang_fr') ?></a>
      <span class="sv-lang__sep" aria-hidden="true">|</span>
      <a class="sv-lang__link<?= $lang === 'en' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(sv_lang_url('en'), ENT_QUOTES, 'UTF-8') ?>" hreflang="en" lang="en"><?= sv_e('lang_en') ?></a>
    </div>

    <header class="sv-hero">
      <h1 class="sv-hero__title">
        <span class="sv-hero__title-line"><?= sv_e('hero_title_line') ?></span>
        <span class="sv-hero__title-accent"><?= sv_e('hero_title_accent') ?></span>
      </h1>
      <p class="sv-hero__invite"><?= sv_e('hero_invite') ?></p>
      <p class="sv-hero__place">
        <svg class="sv-hero__pin" width="14" height="18" viewBox="0 0 14 18" aria-hidden="true" focusable="false">
          <path fill="currentColor" d="M7 0C3.13 0 0 3.13 0 7c0 5.25 7 11 7 11s7-5.75 7-11c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 7 4.5a2.5 2.5 0 0 1 0 5z"/>
        </svg>
        <?= sv_e('hero_place') ?>
      </p>
    </header>

    <section class="sv-form-section" aria-label="<?= $success ? sv_e('aria_success') : sv_e('aria_form') ?>">
      <div class="sv-form-card<?= $success ? ' sv-form-card--success' : '' ?>">
        <?php if ($success) : ?>
          <div class="sv-success">
            <p class="sv-success__eyebrow"><?= sv_e('success_eyebrow') ?></p>
            <p class="sv-success__lead"><?= sv_e('success_lead') ?></p>
            <p class="sv-success__offer">
              <?= sv_l('success_offer') ?>
              <strong class="sv-success__code"><?= htmlspecialchars($promoCode, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
            <p class="sv-success__draw"><?= sv_e('success_draw') ?></p>
            <p class="sv-success__stand"><?= sv_l('success_stand') ?></p>
          </div>
        <?php else : ?>
          <div class="sv-form-card__links">
            <a href="<?= htmlspecialchars(sv_page_url('reglement.php'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= sv_e('link_rules') ?></a>
            <a href="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/content/16-politique-de-confidentialite-et-de-cookies" target="_blank" rel="noopener"><?= sv_e('link_privacy') ?></a>
          </div>

          <?php if ($error) : ?>
            <p class="sv-form__error" role="alert"><?= sv_e('form_error') ?></p>
          <?php endif; ?>

          <form class="sv-form" action="<?= $lp('submit.php') ?>" method="post">
            <input type="hidden" name="lang" value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
            <div class="sv-form__main">
              <div class="sv-form__grid">
                <label class="sv-field">
                  <span class="sv-field__label"><?= sv_e('label_lastname') ?></span>
                  <input type="text" name="nom" required autocomplete="family-name" maxlength="100">
                </label>
                <label class="sv-field">
                  <span class="sv-field__label"><?= sv_e('label_firstname') ?></span>
                  <input type="text" name="prenom" required autocomplete="given-name" maxlength="100">
                </label>
                <label class="sv-field">
                  <span class="sv-field__label"><?= sv_e('label_email') ?></span>
                  <input type="email" name="email" required autocomplete="email" maxlength="150">
                </label>
                <label class="sv-field">
                  <span class="sv-field__label"><?= sv_e('label_phone') ?></span>
                  <input type="tel" name="telephone" required autocomplete="tel" maxlength="30">
                </label>
                <label class="sv-field sv-field--full">
                  <span class="sv-field__label"><?= sv_e('label_company') ?></span>
                  <input type="text" name="societe" required autocomplete="organization" maxlength="150">
                </label>
              </div>

              <label class="sv-check">
                <input type="checkbox" name="accept_jeu" value="1" required aria-required="true">
                <span><?= sv_e('check_rules') ?></span>
              </label>

              <label class="sv-check">
                <input type="checkbox" name="accept_com" value="1">
                <span><?= sv_e('check_com') ?></span>
              </label>
            </div>

            <button type="submit" class="sv-cta"><?= sv_e('cta_submit') ?></button>
          </form>
        <?php endif; ?>
      </div>
    </section>

    <section class="sv-banner" aria-label="<?= sv_e('aria_banner') ?>">
      <p class="sv-banner__text"><?= sv_e('banner_text') ?></p>
    </section>

    <section class="sv-perks" aria-label="<?= sv_e('aria_perks') ?>">
      <div class="sv-perks__grid">
        <article class="sv-perk">
          <p class="sv-perk__accent"><?= sv_e('perk_discount') ?></p>
          <p class="sv-perk__text"><?= sv_e('perk_discount_sub') ?></p>
        </article>
        <article class="sv-perk">
          <p class="sv-perk__text"><strong><?= sv_e('perk_tablet_label') ?></strong></p>
          <p class="sv-perk__accent"><?= sv_e('perk_tablet') ?></p>
        </article>
      </div>
    </section>

    <section class="sv-categories" aria-label="<?= sv_e('aria_categories') ?>">
      <div class="sv-categories__grid">
        <a class="sv-cat" href="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/3-cloture-rigide">
          <span class="sv-cat__title"><?= sv_l('cat_rigid') ?></span>
          <img src="<?= $lp('img/products/product-1.png') ?>" alt="<?= sv_e('cat_rigid_alt') ?>" width="280" height="280" loading="lazy" decoding="async">
        </a>
        <a class="sv-cat" href="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/2-grillage-souple">
          <span class="sv-cat__title"><?= sv_l('cat_soft') ?></span>
          <img src="<?= $lp('img/products/product-2.png') ?>" alt="<?= sv_e('cat_soft_alt') ?>" width="280" height="280" loading="lazy" decoding="async">
        </a>
        <a class="sv-cat" href="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/4-portail-et-portillon">
          <span class="sv-cat__title"><?= sv_l('cat_gate') ?></span>
          <img src="<?= $lp('img/products/product-3.png') ?>" alt="<?= sv_e('cat_gate_alt') ?>" width="280" height="280" loading="lazy" decoding="async">
        </a>
        <a class="sv-cat" href="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/6-brise-vue">
          <span class="sv-cat__title"><?= sv_l('cat_screen') ?></span>
          <img src="<?= $lp('img/products/product-4.png') ?>" alt="<?= sv_e('cat_screen_alt') ?>" width="280" height="280" loading="lazy" decoding="async">
        </a>
        <a class="sv-cat" href="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/215-cloture-aluminium">
          <span class="sv-cat__title"><?= sv_l('cat_alu') ?></span>
          <img src="<?= $lp('img/products/product-5.png') ?>" alt="<?= sv_e('cat_alu_alt') ?>" width="280" height="280" loading="lazy" decoding="async">
        </a>
        <a class="sv-cat" href="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/438-cloture-composite">
          <span class="sv-cat__title"><?= sv_l('cat_composite') ?></span>
          <img src="<?= $lp('img/products/product-6.png') ?>" alt="<?= sv_e('cat_composite_alt') ?>" width="280" height="280" loading="lazy" decoding="async">
        </a>
      </div>
    </section>

    <section class="sv-avantages" aria-label="<?= sv_e('aria_benefits') ?>">
      <div class="sv-avantages__inner">
        <h2 class="sv-avantages__title"><?= sv_e('benefits_title') ?></h2>
        <p class="sv-avantages__intro"><?= sv_e('benefits_intro') ?></p>

        <div class="sv-tiers">
          <article class="sv-tier sv-tier--bronze">
            <img src="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/modules/dtc_paliers_remise/views/img/star-bronze.png" alt="" width="72" height="72" loading="lazy" decoding="async">
            <h3><?= sv_e('tier_bronze') ?></h3>
            <p class="sv-tier__discount">-5%</p>
          </article>
          <article class="sv-tier sv-tier--argent">
            <img src="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/modules/dtc_paliers_remise/views/img/star-argent.png" alt="" width="72" height="72" loading="lazy" decoding="async">
            <h3><?= sv_e('tier_silver') ?></h3>
            <p class="sv-tier__discount">-7%</p>
          </article>
          <article class="sv-tier sv-tier--or">
            <img src="<?= htmlspecialchars($shopUrl, ENT_QUOTES) ?>/modules/dtc_paliers_remise/views/img/star-or.png" alt="" width="72" height="72" loading="lazy" decoding="async">
            <h3><?= sv_e('tier_gold') ?></h3>
            <p class="sv-tier__discount">-10%</p>
          </article>
        </div>

        <div class="sv-benefits">
          <article class="sv-benefit">
            <span class="sv-benefit__icon" aria-hidden="true">
              <img src="<?= $shop('img/cms/picto_camion_livraison_offerte.svg') ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
            </span>
            <div>
              <h3><?= sv_e('benefit_delivery_title') ?></h3>
              <p><?= sv_e('benefit_delivery_text') ?></p>
            </div>
          </article>
          <article class="sv-benefit">
            <span class="sv-benefit__icon" aria-hidden="true">
              <img src="<?= $shop('img/cms/picto_pourcentage_100_offerts.svg') ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
            </span>
            <div>
              <h3><?= sv_e('benefit_price_title') ?></h3>
              <p><?= sv_e('benefit_price_text') ?></p>
            </div>
          </article>
          <article class="sv-benefit">
            <span class="sv-benefit__icon" aria-hidden="true">
              <img src="<?= $shop('modules/ckc_espacepro/views/img/icons/panier.png') ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
            </span>
            <div>
              <h3><?= sv_e('benefit_return_title') ?></h3>
              <p><?= sv_e('benefit_return_text') ?></p>
            </div>
          </article>
          <article class="sv-benefit">
            <span class="sv-benefit__icon" aria-hidden="true">
              <img src="<?= $shop('modules/ckc_espacepro/views/img/icons/pouce.png') ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
            </span>
            <div>
              <h3><?= sv_e('benefit_flex_title') ?></h3>
              <p><?= sv_e('benefit_flex_text') ?></p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <p class="sv-legal"><?= sv_e('legal') ?></p>
  </main>
</body>
</html>
