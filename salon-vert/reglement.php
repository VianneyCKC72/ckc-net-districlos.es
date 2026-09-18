<?php
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/bootstrap.php';

$basePath = sv_base_path();
$lang = sv_lang();

/** Asset local à /salon-vert/ */
$lp = static function (string $path) use ($basePath): string {
    return htmlspecialchars($basePath . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
};

$shopRoot = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
if ($shopRoot === '/' || $shopRoot === '\\') {
    $shopRoot = '';
}
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
  <title><?= sv_e('rules_meta_title') ?></title>
  <meta name="description" content="<?= sv_e('rules_meta_description') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $lp('styles.css') ?>">
  <link rel="icon" href="<?= $shop('img/favicon.ico') ?>">
</head>
<body>
  <main class="sv sv--rules">
    <div class="sv-lang" role="navigation" aria-label="<?= sv_e('lang_switch') ?>">
      <a class="sv-lang__link<?= $lang === 'fr' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(sv_lang_url('fr'), ENT_QUOTES, 'UTF-8') ?>" hreflang="fr" lang="fr"><?= sv_e('lang_fr') ?></a>
      <span class="sv-lang__sep" aria-hidden="true">|</span>
      <a class="sv-lang__link<?= $lang === 'en' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(sv_lang_url('en'), ENT_QUOTES, 'UTF-8') ?>" hreflang="en" lang="en"><?= sv_e('lang_en') ?></a>
    </div>

    <div class="sv-rules">
      <p class="sv-rules__back">
        <a href="<?= htmlspecialchars(sv_page_url(), ENT_QUOTES, 'UTF-8') ?>"><?= sv_e('rules_back') ?></a>
      </p>

      <header class="sv-rules__header">
        <h1 class="sv-rules__title"><?= sv_e('rules_title') ?></h1>
        <p class="sv-rules__subtitle"><?= sv_e('rules_subtitle') ?></p>
      </header>

      <article class="sv-rules__body">
        <?= sv_l('rules_html') ?>
      </article>
    </div>
  </main>
</body>
</html>
