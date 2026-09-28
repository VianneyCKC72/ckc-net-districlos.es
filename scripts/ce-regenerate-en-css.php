<?php
/**
 * CKC Vianney — régénère le CSS Creative Elements des contenus anglais.
 *
 * Ne modifie pas `_elementor_data`. Pour chaque uid EN :
 * supprime `_elementor_css` + le fichier, puis PostCSS::update()
 * dans le contexte langue EN / boutique du uid.
 *
 * Dry-run (défaut) :
 *   php scripts/ce-regenerate-en-css.php
 *
 * Écriture :
 *   php scripts/ce-regenerate-en-css.php --apply
 *
 * Options :
 *   --iso=en          langue cible
 *   --shops=1,2       boutiques (défaut : toutes les boutiques actives)
 *   --types=cms,product,category,theme,content
 *   --uid=123070201   un seul uid
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.inc.php';
require_once _PS_MODULE_DIR_ . 'creativeelements/classes/wrappers/UId.php';

$apply = in_array('--apply', $argv, true);
$iso = 'en';
$shops = null;
$typesFilter = null;
$onlyUid = null;

foreach ($argv as $arg) {
    if (preg_match('/^--iso=([a-z]{2})$/i', $arg, $m)) {
        $iso = strtolower($m[1]);
    } elseif (preg_match('/^--shops=([0-9,]+)$/', $arg, $m)) {
        $shops = array_values(array_unique(array_filter(array_map('intval', explode(',', $m[1])))));
    } elseif (preg_match('/^--types=([a-z0-9,]+)$/i', $arg, $m)) {
        $typesFilter = array_values(array_filter(array_map('strtolower', explode(',', $m[1]))));
    } elseif (preg_match('/^--uid=(\d+)$/', $arg, $m)) {
        $onlyUid = $m[1];
    }
}

$typeNames = [
    CE\UId::CONTENT => 'content',
    CE\UId::PRODUCT => 'product',
    CE\UId::CATEGORY => 'category',
    CE\UId::MANUFACTURER => 'manufacturer',
    CE\UId::SUPPLIER => 'supplier',
    CE\UId::CMS => 'cms',
    CE\UId::CMS_CATEGORY => 'cms_category',
    CE\UId::YBC_BLOG_POST => 'ybc_blog',
    CE\UId::XIPBLOG_POST => 'xipblog',
    CE\UId::STBLOG_POST => 'stblog',
    CE\UId::ADVANCEBLOG_POST => 'advanceblog',
    CE\UId::PRESTABLOG_POST => 'prestablog',
    CE\UId::SIMPLEBLOG_POST => 'simpleblog',
    CE\UId::PSBLOG_POST => 'psblog',
    CE\UId::HIBLOG_POST => 'hiblog',
    CE\UId::THEME => 'theme',
    CE\UId::TVCMSBLOG_POST => 'tvcmsblog',
    CE\UId::ETS_BLOG_POST => 'ets_blog',
];

$idLang = (int) Language::getIdByIso($iso);
if (!$idLang || !Language::getLanguage($idLang)) {
    fwrite(STDERR, "Langue introuvable pour l'iso {$iso}.\n");
    exit(1);
}

$activeShops = array_map('intval', Shop::getShops(true, null, true));
if ($shops === null) {
    $shops = $activeShops;
}
$unknownShops = array_diff($shops, $activeShops);
if ($unknownShops) {
    fwrite(STDERR, 'Boutique(s) inactive(s) ou inconnue(s) : ' . implode(',', $unknownShops) . "\n");
    exit(1);
}

Shop::setContext(Shop::CONTEXT_ALL);

$langPad = sprintf('%02d', $idLang);
$rows = Db::getInstance()->executeS(
    'SELECT `id` FROM `' . _DB_PREFIX_ . 'ce_meta` WHERE `name` = "_elementor_data" AND SUBSTRING(`id`, -4, 2) = "' . pSQL($langPad) . '"'
);

$groups = [];
foreach ($rows as $row) {
    $uid = (string) $row['id'];
    if ($onlyUid !== null && $uid !== $onlyUid) {
        continue;
    }
    if (strlen($uid) <= 6) {
        continue;
    }

    $idType = (int) substr($uid, -6, 2);
    $idShop = (int) substr($uid, -2);
    if ($idType === CE\UId::REVISION || $idType === CE\UId::TEMPLATE) {
        continue;
    }

    $typeName = isset($typeNames[$idType]) ? $typeNames[$idType] : ('type' . $idType);
    if ($typesFilter && !in_array($typeName, $typesFilter, true)) {
        continue;
    }

    // uid boutique 0 : PostCSS::toDefault() écrit le CSS sur la boutique du contenu
    if ($idShop === 0) {
        $parsed = CE\UId::parse($uid);
        $shopKey = $parsed ? (int) $parsed->getDefaultShopId() : 0;
    } else {
        $shopKey = $idShop;
    }
    if (!in_array($shopKey, $shops, true)) {
        continue;
    }

    $groups[$shopKey][] = [
        'uid' => $uid,
        'type' => $typeName,
        'id_shop_raw' => $idShop,
    ];
}

ksort($groups);
$total = 0;
foreach ($groups as $items) {
    $total += count($items);
}

echo 'Langue ' . $iso . ' (id ' . $idLang . ') — ' . $total . " uid\n";
foreach ($groups as $idShop => $items) {
    $byType = [];
    foreach ($items as $item) {
        $byType[$item['type']] = (isset($byType[$item['type']]) ? $byType[$item['type']] : 0) + 1;
    }
    $parts = [];
    foreach ($byType as $name => $count) {
        $parts[] = $name . ':' . $count;
    }
    echo '  boutique ' . $idShop . ' — ' . count($items) . ' (' . implode(', ', $parts) . ")\n";
}

if (!$apply) {
    echo "Dry-run. Relancer avec --apply pour écrire les CSS.\n";
    exit(0);
}

if (!$total) {
    echo "Rien à régénérer.\n";
    exit(0);
}

// Un processus par boutique : le kit / la config CE sont lus au démarrage.
if (count($groups) > 1 && !getenv('CE_CSS_SHOP_CHILD')) {
    $php = PHP_BINARY;
    $failed = 0;
    foreach (array_keys($groups) as $idShop) {
        $cmd = escapeshellarg($php) . ' ' . escapeshellarg(__FILE__);
        $cmd .= ' --apply --iso=' . $iso . ' --shops=' . (int) $idShop;
        if ($typesFilter) {
            $cmd .= ' --types=' . implode(',', $typesFilter);
        }
        if ($onlyUid !== null) {
            $cmd .= ' --uid=' . $onlyUid;
        }
        echo "\n--- boutique {$idShop} ---\n";
        passthru($cmd, $code);
        if ($code !== 0) {
            $failed++;
        }
    }
    exit($failed ? 1 : 0);
}

$idShop = (int) array_key_first($groups);
switchCeContext($idShop, $idLang);

if (!Module::isInstalled('creativeelements') || !Module::isEnabled('creativeelements')) {
    fwrite(STDERR, "Module creativeelements inactif.\n");
    exit(1);
}

Module::getInstanceByName('creativeelements');
CE\Plugin::instance();

@set_time_limit(0);
$ok = 0;
$empty = 0;
$errors = 0;
$n = 0;

foreach ($groups[$idShop] as $item) {
    $n++;
    $uid = $item['uid'];
    try {
        $css = new CE\CoreXFilesXCSSXPost($uid);
        $css->delete();
        $css->update();
        $status = (string) $css->getMeta('status');
        if ($status === CE\CoreXFilesXCSSXBase::CSS_STATUS_EMPTY) {
            $empty++;
            echo "[{$n}/{$total}] {$uid} ({$item['type']}) vide\n";
        } else {
            $ok++;
            if ($n % 25 === 0 || $total <= 30) {
                echo "[{$n}/{$total}] {$uid} ({$item['type']}) {$status}\n";
            }
        }
    } catch (Throwable $e) {
        $errors++;
        fwrite(STDERR, "[{$n}/{$total}] {$uid} ERREUR : " . $e->getMessage() . "\n");
    }

    if ($n % 20 === 0) {
        clearCeDocumentCache();
    }
}

clearCeDocumentCache();
echo "Boutique {$idShop} — ok: {$ok}, vides: {$empty}, erreurs: {$errors}\n";
exit($errors ? 1 : 0);

function switchCeContext($idShop, $idLang)
{
    $context = Context::getContext();
    $shop = new Shop((int) $idShop);
    if (!Validate::isLoadedObject($shop)) {
        fwrite(STDERR, "Boutique {$idShop} introuvable.\n");
        exit(1);
    }
    Shop::setContext(Shop::CONTEXT_SHOP, (int) $idShop);
    $context->shop = $shop;

    $language = new Language((int) $idLang);
    if (!Validate::isLoadedObject($language)) {
        fwrite(STDERR, "Langue {$idLang} introuvable.\n");
        exit(1);
    }
    $context->language = $language;
    $GLOBALS['language'] = $language;

    $currency = Currency::getDefaultCurrency();
    if (Validate::isLoadedObject($currency)) {
        $context->currency = $currency;
    }
}

function clearCeDocumentCache()
{
    if (!CE\Plugin::$instance || !CE\Plugin::$instance->documents) {
        return;
    }
    Closure::bind(function () {
        $this->documents = [];
    }, CE\Plugin::$instance->documents, CE\CoreXDocumentsManager::class)->__invoke();
}
