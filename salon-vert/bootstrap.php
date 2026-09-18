<?php
/**
 * Bootstrap Salon Vert : PDO PrestaShop + table inscriptions.
 */

function sv_base_path(): string
{
    return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/salon-vert')), '/');
}

/** @return list<string> */
function sv_available_langs(): array
{
    return ['fr', 'en'];
}

function sv_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $allowed = sv_available_langs();
    $candidate = strtolower(trim((string) ($_GET['lang'] ?? $_POST['lang'] ?? '')));
    if (!in_array($candidate, $allowed, true)) {
        $candidate = strtolower(trim((string) ($_COOKIE['sv_lang'] ?? '')));
    }
    $lang = in_array($candidate, $allowed, true) ? $candidate : 'fr';

    if (!isset($_COOKIE['sv_lang']) || $_COOKIE['sv_lang'] !== $lang) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $path = sv_base_path() !== '' ? sv_base_path() . '/' : '/';
        setcookie('sv_lang', $lang, [
            'expires' => time() + 60 * 60 * 24 * 180,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['sv_lang'] = $lang;
    }

    return $lang;
}

/** @return array<string, string> */
function sv_translations(): array
{
    static $cache = [];
    $lang = sv_lang();
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }

    $file = __DIR__ . '/lang/' . $lang . '.php';
    $cache[$lang] = is_file($file) ? (require $file) : [];

    return $cache[$lang];
}

/**
 * Traduction (source FR dans lang/fr.php). Placeholders : %name%.
 */
function sv_l(string $key, array $replace = []): string
{
    $translations = sv_translations();
    $text = $translations[$key] ?? $key;
    foreach ($replace as $name => $value) {
        $text = str_replace('%' . $name . '%', (string) $value, $text);
    }

    return $text;
}

function sv_e(string $key, array $replace = []): string
{
    return htmlspecialchars(sv_l($key, $replace), ENT_QUOTES, 'UTF-8');
}

function sv_lang_url(string $lang, array $extra = []): string
{
    $params = array_merge($_GET, $extra, ['lang' => $lang]);
    $query = http_build_query($params);
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    $path = sv_base_path() . '/';
    if ($script !== '' && $script !== 'index.php') {
        $path .= $script;
    }

    return $path . ($query !== '' ? '?' . $query : '');
}

/** URL relative à /salon-vert/ avec langue courante. */
function sv_page_url(string $page = '', ?string $lang = null): string
{
    $lang = $lang ?? sv_lang();
    $path = sv_base_path() . '/';
    $page = ltrim($page, '/');
    if ($page !== '' && $page !== 'index.php') {
        $path .= $page;
    }
    $query = http_build_query(['lang' => $lang]);

    return $path . ($query !== '' ? '?' . $query : '');
}

function sv_params(): array
{
    static $params = null;
    if ($params === null) {
        $all = require dirname(__DIR__) . '/app/config/parameters.php';
        $params = $all['parameters'] ?? $all;
    }

    return $params;
}

function sv_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $p = sv_params();
    $host = $p['database_host'] ?? 'localhost';
    $port = $p['database_port'] ?? '';
    $name = $p['database_name'] ?? '';
    $user = $p['database_user'] ?? '';
    $pass = $p['database_password'] ?? '';

    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
    if ($port !== '' && $port !== null) {
        $dsn .= ';port=' . $port;
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function sv_table(): string
{
    $prefix = sv_params()['database_prefix'] ?? 'ps_';

    return $prefix . 'salon_vert_inscription';
}

function sv_ensure_table(): void
{
    $table = sv_table();
    $safe = str_replace('`', '``', $table);
    $sql = 'CREATE TABLE IF NOT EXISTS `' . $safe . '` (
        `id_salon_vert_inscription` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nom` VARCHAR(100) NOT NULL,
        `prenom` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL,
        `telephone` VARCHAR(30) NOT NULL,
        `societe` VARCHAR(150) NOT NULL,
        `accept_jeu` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
        `accept_com` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
        `date_add` DATETIME NOT NULL,
        PRIMARY KEY (`id_salon_vert_inscription`),
        UNIQUE KEY `uniq_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $pdo = sv_pdo();
    $pdo->exec($sql);

    // Migration : retirer l’IP si la colonne existe encore
    $check = $pdo->query('SHOW COLUMNS FROM `' . $safe . '` LIKE \'ip\'');
    if ($check && $check->fetch()) {
        $pdo->exec('ALTER TABLE `' . $safe . '` DROP COLUMN `ip`');
    }
}

function sv_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function sv_cookie_name(): string
{
    return 'sv_salon_vert_inscrit';
}

function sv_is_registered_cookie(): bool
{
    return !empty($_COOKIE[sv_cookie_name()]);
}

function sv_set_registered_cookie(string $email): void
{
    $value = hash('sha256', sv_normalize_email($email));
    $expire = time() + 60 * 60 * 24 * 90; // 90 jours (durée salon + marge)
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $path = sv_base_path() !== '' ? sv_base_path() . '/' : '/';

    setcookie(sv_cookie_name(), $value, [
        'expires' => $expire,
        'path' => $path,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[sv_cookie_name()] = $value;
}

function sv_email_exists(string $email): bool
{
    $stmt = sv_pdo()->prepare(
        'SELECT 1 FROM `' . str_replace('`', '``', sv_table()) . '` WHERE `email` = :email LIMIT 1'
    );
    $stmt->execute(['email' => sv_normalize_email($email)]);

    return (bool) $stmt->fetchColumn();
}

/**
 * Contexte boutique Pro (newsletter ps_emailsubscription / customer.newsletter).
 *
 * @return array{id_shop:int,id_shop_group:int,id_lang:int}
 */
function sv_shop_context(): array
{
    static $ctx = null;
    if ($ctx !== null) {
        return $ctx;
    }

    $prefix = sv_params()['database_prefix'] ?? 'ps_';
    $pdo = sv_pdo();
    $domain = 'pro.cloture-discount.fr';

    $stmt = $pdo->prepare(
        'SELECT s.`id_shop`, s.`id_shop_group`
         FROM `' . str_replace('`', '``', $prefix . 'shop_url') . '` u
         INNER JOIN `' . str_replace('`', '``', $prefix . 'shop') . '` s ON s.`id_shop` = u.`id_shop`
         WHERE u.`domain` = :d OR u.`domain_ssl` = :d
         LIMIT 1'
    );
    $stmt->execute(['d' => $domain]);
    $row = $stmt->fetch();

    $idShop = $row ? (int) $row['id_shop'] : 2;
    $idShopGroup = $row ? (int) $row['id_shop_group'] : 1;

    $idLang = (int) $pdo->query(
        'SELECT `id_lang` FROM `' . str_replace('`', '``', $prefix . 'lang') . '`
         WHERE `iso_code` = \'fr\' AND `active` = 1 LIMIT 1'
    )->fetchColumn();
    if ($idLang < 1) {
        $idLang = 1;
    }

    $ctx = [
        'id_shop' => $idShop,
        'id_shop_group' => $idShopGroup,
        'id_lang' => $idLang,
    ];

    return $ctx;
}

/**
 * Relie accept_com au mécanisme newsletter PrestaShop déjà en place :
 * - client existant → customer.newsletter = 1
 * - sinon → ps_emailsubscription (comme le bandeau newsletter)
 *
 * Pas d’IP stockée (volontaire).
 */
function sv_subscribe_newsletter(string $email): void
{
    $email = sv_normalize_email($email);
    if ($email === '') {
        return;
    }

    $prefix = sv_params()['database_prefix'] ?? 'ps_';
    $pdo = sv_pdo();
    $shop = sv_shop_context();
    $customerTable = str_replace('`', '``', $prefix . 'customer');
    $guestTable = str_replace('`', '``', $prefix . 'emailsubscription');

    $stmt = $pdo->prepare(
        'SELECT `id_customer`, `newsletter` FROM `' . $customerTable . '`
         WHERE `email` = :email AND `id_shop` = :id_shop LIMIT 1'
    );
    $stmt->execute([
        'email' => $email,
        'id_shop' => $shop['id_shop'],
    ]);
    $customer = $stmt->fetch();

    if ($customer) {
        if ((int) $customer['newsletter'] !== 1) {
            $upd = $pdo->prepare(
                'UPDATE `' . $customerTable . '`
                 SET `newsletter` = 1, `newsletter_date_add` = NOW()
                 WHERE `id_customer` = :id'
            );
            $upd->execute(['id' => (int) $customer['id_customer']]);
        }

        return;
    }

    $chk = $pdo->prepare(
        'SELECT `id`, `active` FROM `' . $guestTable . '`
         WHERE `email` = :email AND `id_shop` = :id_shop LIMIT 1'
    );
    $chk->execute([
        'email' => $email,
        'id_shop' => $shop['id_shop'],
    ]);
    $guest = $chk->fetch();

    if ($guest) {
        if ((int) $guest['active'] !== 1) {
            $act = $pdo->prepare(
                'UPDATE `' . $guestTable . '` SET `active` = 1 WHERE `id` = :id'
            );
            $act->execute(['id' => (int) $guest['id']]);
        }

        return;
    }

    $ins = $pdo->prepare(
        'INSERT INTO `' . $guestTable . '`
            (`id_shop`, `id_shop_group`, `email`, `newsletter_date_add`, `ip_registration_newsletter`, `http_referer`, `active`, `id_lang`)
         VALUES
            (:id_shop, :id_shop_group, :email, NOW(), \'\', NULL, 1, :id_lang)'
    );
    $ins->execute([
        'id_shop' => $shop['id_shop'],
        'id_shop_group' => $shop['id_shop_group'],
        'email' => $email,
        'id_lang' => $shop['id_lang'],
    ]);
}

/**
 * @return string 'created'|'exists'
 */
function sv_register(array $data): string
{
    sv_ensure_table();

    $email = sv_normalize_email($data['email']);
    $acceptCom = !empty($data['accept_com']);
    $status = 'created';

    if (sv_email_exists($email)) {
        $status = 'exists';
    } else {
        $sql = 'INSERT INTO `' . str_replace('`', '``', sv_table()) . '`
            (`nom`, `prenom`, `email`, `telephone`, `societe`, `accept_jeu`, `accept_com`, `date_add`)
            VALUES
            (:nom, :prenom, :email, :telephone, :societe, :accept_jeu, :accept_com, NOW())';

        try {
            $stmt = sv_pdo()->prepare($sql);
            $stmt->execute([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $email,
                'telephone' => $data['telephone'],
                'societe' => $data['societe'],
                'accept_jeu' => !empty($data['accept_jeu']) ? 1 : 0,
                'accept_com' => $acceptCom ? 1 : 0,
            ]);
        } catch (PDOException $e) {
            // Course condition / doublon
            if ((int) $e->getCode() === 23000) {
                $status = 'exists';
            } else {
                throw $e;
            }
        }
    }

    if ($acceptCom) {
        sv_subscribe_newsletter($email);
    }

    return $status;
}
