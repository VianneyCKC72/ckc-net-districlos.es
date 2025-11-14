<?php
/**
 * controllers/front/whoami.php
 */
class Ckc_EspaceproWhoamiModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    /* ===========================
     * Utils
     * =========================== */

    /** No-cache: évite un retour proxy/CDN */
    private function sendNoCacheHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    /** Retourne le nom du cookie PrestaShop courant via Reflection si nécessaire */
    private function getPsCookieName(Cookie $cookie): ?string
    {
        if (method_exists($cookie, 'getName')) {
            // Sur certaines versions personnalisées
            try { return (string)$cookie->getName(); } catch (\Throwable $e) {}
        }
        try {
            $ref = new ReflectionClass($cookie);
            foreach (['name','_name'] as $p) {
                if ($ref->hasProperty($p)) {
                    $rp = $ref->getProperty($p);
                    $rp->setAccessible(true);
                    $val = $rp->getValue($cookie);
                    if (is_string($val) && $val !== '') {
                        return $val;
                    }
                }
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /** Métadonnées internes du cookie (nom, domaine, path) */
    private function getPsCookieMeta(Cookie $cookie): array
    {
        $meta = [];
        try {
            $ref = new ReflectionClass($cookie);
            foreach (['name','_name','domain','_domain','path','_path'] as $p) {
                if ($ref->hasProperty($p)) {
                    $rp = $ref->getProperty($p);
                    $rp->setAccessible(true);
                    $meta[$p] = $rp->getValue($cookie);
                }
            }
        } catch (\Throwable $e) {}
        return $meta;
    }

    /** Liste les Set-Cookie envoyés par la page courante (utile pour repérer un écrasement) */
    private function listSetCookieHeaders(): array
    {
        $out = [];
        foreach (headers_list() as $h) {
            if (stripos($h, 'Set-Cookie:') === 0) {
                $out[] = $h;
            }
        }
        return $out;
    }

    /* ===========================
     * Rendus
     * =========================== */

    /** Rendu minimal (tes logs “whoami” habituels) */
    private function renderWhoamiSimple(): void
    {
        $this->sendNoCacheHeaders();
        header('Content-Type: text/plain; charset=utf-8');

        $c = $this->context->cookie;
        $cookieName = $this->getPsCookieName($c);

        echo 'whoami (shopId='.((int)$this->context->shop->id).")\n\n";

        echo 'cookie_name: '.($cookieName ?? '(inconnu)')."\n";
        echo 'isLogged: '.((int)$this->context->customer->isLogged())."\n";
        echo 'id_customer: '.((int)$this->context->customer->id)."\n";
        echo 'email: '.(string)$this->context->customer->email."\n";
        echo 'cookie.id_customer: '.((int)$c->id_customer)."\n";
        echo 'cookie.logged: '.((int)$c->logged)."\n\n";

        echo '_COOKIES keys: '.(implode(', ', array_keys($_COOKIE)))."\n";
        exit;
    }

    /** Rendu debug détaillé activé via ?ckc_debug=who */
    private function renderWhoamiDebug(): void
    {
        $this->sendNoCacheHeaders();
        header('Content-Type: text/plain; charset=utf-8');

        $c     = $this->context->cookie;
        $name  = $this->getPsCookieName($c);
        $meta  = $this->getPsCookieMeta($c);
        $raw   = $name ? ($_COOKIE[$name] ?? null) : null;
        $sets  = $this->listSetCookieHeaders();

        $out = [
            'whoami_shopId'         => (int)$this->context->shop->id,
            'host'                  => ($_SERVER['HTTP_HOST'] ?? ''),
            'request_uri'           => ($_SERVER['REQUEST_URI'] ?? ''),
            'cookie_name'           => $name,
            'has_raw_cookie'        => $raw !== null ? 1 : 0,
            'raw_cookie_len'        => $raw !== null ? strlen($raw) : 0,
            'cookie_logged'         => (int)$c->logged,
            'cookie_id_customer'    => (int)$c->id_customer,
            'cookie_email'          => (string)$c->email,
            'cookie_key_sha256'     => defined('_COOKIE_KEY_') ? hash('sha256', _COOKIE_KEY_) : 'n/a',
            'cookie_iv_sha256'      => defined('_COOKIE_IV_')  ? hash('sha256', _COOKIE_IV_)  : 'n/a',
            'PS_COOKIE_CHECKIP'     => (int)Configuration::get('PS_COOKIE_CHECKIP'),
            'client_ip'             => Tools::getRemoteAddr(),
            'server_addr'           => ($_SERVER['SERVER_ADDR'] ?? ''),
            'php_version'           => PHP_VERSION,
            'ps_version'            => _PS_VERSION_,
            '_COOKIES_keys'         => array_keys($_COOKIE),
            'cookie_meta'           => $meta,
            'set_cookie_headers'    => $sets,
        ];

        echo "CKC EP DEBUG · whoami (shopId={$out['whoami_shopId']})\n\n";
        foreach ($out as $k => $v) {
            echo $k.': '.(is_array($v) ? json_encode($v, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) : $v)."\n";
        }
        exit;
    }

    /* ===========================
     * Entrée front-controller
     * =========================== */

    public function initContent()
    {
        parent::initContent();

        // Mode debug verbeux
        if (Tools::getValue('ckc_debug') === 'who') {
            $this->renderWhoamiDebug(); // exit
        }

        // Rendu “simple” (comme tes logs existants)
        $this->renderWhoamiSimple(); // exit
    }
}
