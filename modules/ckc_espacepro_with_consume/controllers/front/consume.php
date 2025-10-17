<?php

class Ckc_EspaceproConsumeModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    private function dump($title, $data = [])
    {
        if (!Tools::getValue('ckc_debug')) {
            return;
        }
        header('Content-Type: text/plain; charset=utf-8');
        echo "CKC EP DEBUG · {$title}\n";
        echo "shopId: " . (int)$this->context->shop->id . "\n";
        echo "host:   " . ($_SERVER['HTTP_HOST'] ?? '') . "\n";
        echo "uri:    " . ($_SERVER['REQUEST_URI'] ?? '') . "\n\n";
        if ($data) {
            print_r($data);
            echo "\n";
        }
        echo "GET:\n";
        print_r($_GET);
        echo "\n";
    }

    private function dd($title, $data = [])
    {
        $this->dump($title, $data);
        exit;
    }

// SSO consume: vérification, éventuel hop de domaine, login sur la boutique cible en réutilisant le cookie du contexte (même nom que whoami), puis redirection.
    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('ckc_debug') === 'sign') {
            $u = (int)Tools::getValue('u');
            $exp = (int)Tools::getValue('exp', time() + 1800);
            $jti = (string)Tools::getValue('jti', bin2hex(random_bytes(8)));
            $ts = (int)Tools::getValue('ts', 2);
            $back = Tools::getValue('back', '');
            if ($back) {
                // Si on a reçu 'index.php?controller=my-account', remplace par le friendly
                if (stripos($back, 'index.php?controller=my-account') === 0) {
                    // Génère l’URL canonique et garde seulement le PATH relatif
                    $url = $this->context->link->getPageLink('my-account', true); // ex: https://.../mon-compte
                    $back = ltrim(parse_url($url, PHP_URL_PATH), '/');            // -> 'mon-compte'
                }
            }
            $secret = (string)Configuration::get('CKC_EP_SSO_SECRET');
            $sig = hash_hmac('sha256', $u . '|' . $exp . '|' . $jti, $secret);
            $url = $this->context->link->getModuleLink('ckc_espacepro', 'consume', [
                'u' => $u, 'exp' => $exp, 'jti' => $jti, 'sig' => $sig, 'back' => $back, 'ts' => $ts
            ], true, null, null);
            $this->dd('SIGN', [
                'u' => $u, 'exp' => $exp, 'jti' => $jti, 'sig' => $sig, 'ts' => $ts, 'back' => $back,
                'secret_sha256' => hash('sha256', $secret),
                'url_debug' => $url . '&ckc_debug=2',
                'url' => $url
            ]);
        }

        $this->dump('consume (entry)', [
            'server' => [
                'http_host' => ($_SERVER['HTTP_HOST'] ?? ''), 'server_name' => ($_SERVER['SERVER_NAME'] ?? ''),
                'https' => isset($_SERVER['HTTPS']) ? 1 : 0, 'remote_addr' => ($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent' => ($_SERVER['HTTP_USER_AGENT'] ?? ''), 'request_uri' => ($_SERVER['REQUEST_URI'] ?? ''),
                'php_version' => PHP_VERSION, 'ps_version' => _PS_VERSION_,
                'cookie_key_sha256' => defined('_COOKIE_KEY_') ? hash('sha256', _COOKIE_KEY_) : 'n/a',
            ],
            'shop_ctx_before' => ['context' => Shop::getContext(), 'ctx_shop_id' => (int)$this->context->shop->id],
        ]);

        $u = (int)Tools::getValue('u');
        $exp = (int)Tools::getValue('exp');
        $jti = (string)Tools::getValue('jti');
        $sig = (string)Tools::getValue('sig');
        $back = (string)Tools::getValue('back', 'my-account');
        $targetShopId = (int)Tools::getValue('ts', 0);
        $hop = (int)Tools::getValue('hop', 0);

        if (!$u || !$exp || !$jti || !$sig || !$targetShopId) {
            $this->dd('consume (params manquants)', compact('u', 'exp', 'jti', 'sig', 'targetShopId', 'back'));
        }
        $this->dump('consume (params)', [
            'u' => $u, 'exp' => $exp, 'jti' => $jti, 'sig' => $sig, 'back' => $back, 'targetShopId' => $targetShopId, 'hop' => $hop
        ]);

        $secret = (string)Configuration::get('CKC_EP_SSO_SECRET');
        $dataToSign = $u . '|' . $exp . '|' . $jti;
        $expected = hash_hmac('sha256', $dataToSign, $secret);
        $this->dump('consume (signature check)', ['expected' => $expected, 'sig' => $sig, 'dataToSign' => $dataToSign, 'now' => time(), 'exp' => $exp]);
        if (!hash_equals($expected, $sig)) {
            $this->dd('consume (signature KO)', compact('expected', 'sig', 'dataToSign'));
        }
        if ($exp < time()) {
            $this->dd('consume (token expiré)', ['exp' => $exp, 'now' => time()]);
        }

        $customer = new Customer($u);
        if (!Validate::isLoadedObject($customer)) {
            $this->dd('consume (customer introuvable)', ['u' => $u]);
        }
        $this->dump('consume (customer ok)', [
            'id' => $customer->id, 'email' => $customer->email, 'is_guest' => (int)$customer->isGuest(),
            'gid_default' => (int)$customer->id_default_group
        ]);

        $home = $this->getShopBase($targetShopId);
        $targetHost = parse_url($home, PHP_URL_HOST) ?: '';
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        $this->dump('consume (host check)', ['currentHost' => $currentHost, 'targetHost' => $targetHost, 'home' => $home]);

        // Si on n'est pas encore sur le bon host, on "hop"
        if (!empty($targetHost) && strcasecmp($targetHost, $currentHost) !== 0) {
            $params = ['u' => $u, 'exp' => $exp, 'jti' => $jti, 'sig' => $sig, 'back' => $back, 'ts' => $targetShopId, 'hop' => 1];
            if ($dbg = Tools::getValue('ckc_debug')) {
                $params['ckc_debug'] = $dbg;
            }
            $hopUrl = $this->context->link->getModuleLink('ckc_espacepro', 'consume', $params, true, null, $targetShopId);
            $this->dump('consume (domain-hop → autre host)', ['from_host' => $currentHost, 'to_host' => $targetHost, 'hopUrl' => $hopUrl]);

            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            if (headers_sent($hsFile, $hsLine)) {
                $this->dump('consume (headers déjà envoyés, fallback JS hop)', ['file' => $hsFile, 'line' => $hsLine, 'to' => $hopUrl]);
                echo '<script>location.href=' . json_encode($hopUrl) . ';</script>';
                exit;
            }
            header('Location: ' . $hopUrl, true, 302);
            exit;
        }

        // ==== ICI : on est bien sur le host de la boutique cible ====
        $prevType = Shop::getContext();
        $prevShopId = (int)$this->context->shop->id;
        Shop::setContext(Shop::CONTEXT_SHOP, (int)$targetShopId);

        // ⚠️ Utiliser le cookie du CONTEXTE (nom + crypto exacts de shop2)
        $cookie = $this->context->cookie;

        // Remplissage standard
        $cookie->id_customer = (int)$customer->id;
        $cookie->customer_lastname = (string)$customer->lastname;
        $cookie->customer_firstname = (string)$customer->firstname;
        $cookie->passwd = (string)$customer->passwd;   // hash DB
        $cookie->email = (string)$customer->email;
        $cookie->secure_key = (string)$customer->secure_key;
        $cookie->is_guest = 0;
        $cookie->logged = 1;

        // Contexte boutique
        $cookie->id_shop = (int)$targetShopId;
        $cookie->id_shop_group = (int)Shop::getGroupFromShop($targetShopId);

        // Optionnel mais utile
        if (!empty($this->context->language->id)) {
            $cookie->id_lang = (int)$this->context->language->id;
        }
        if (!empty($this->context->currency->id)) {
            $cookie->id_currency = (int)$this->context->currency->id;
        }

        if (class_exists('\CustomerSession')) {
            try {
                $cookie->registerSession(new \CustomerSession());
            } catch (\Throwable $e) {
                $this->dump('consume (registerSession exception)', ['msg' => $e->getMessage()]);
            }
        }

        try {
            $cookie->write();
        } catch (\Throwable $e) {
            $this->dd('consume (cookie write exception)', ['msg' => $e->getMessage()]);
        }

        // Synchroniser le contexte (évite une invalidation immédiate)
        $this->context->customer = $customer;
        if (method_exists($this->context, 'updateCustomer')) {
            try {
                $this->context->updateCustomer($customer);
            } catch (\Throwable $e) {
            }
        }
        if (class_exists('Hook')) {
            try {
                Hook::exec('actionAuthentication', ['customer' => $customer]);
            } catch (\Throwable $e) {
            }
        }

        if (Tools::getValue('ckc_debug')) {
            $ref = new ReflectionClass($cookie);
            $meta = [];
            foreach (['name', '_name', 'domain', '_domain', 'path', '_path'] as $prop) {
                if ($ref->hasProperty($prop)) {
                    $rp = $ref->getProperty($prop);
                    $rp->setAccessible(true);
                    $meta[$prop] = $rp->getValue($cookie);
                }
            }
            $meta['cookie_logged'] = (int)$cookie->logged;
            $meta['cookie_id_customer'] = (int)$cookie->id_customer;
            $meta['cookie_email'] = (string)$cookie->email;
            $meta['cookie_passwd_len'] = strlen((string)$cookie->passwd);
            $meta['cookie_key_sha256'] = defined('_COOKIE_KEY_') ? hash('sha256', _COOKIE_KEY_) : 'n/a';

            $setCookies = [];
            foreach (headers_list() as $h) {
                if (stripos($h, 'Set-Cookie:') === 0) {
                    $setCookies[] = $h;
                }
            }
            $meta['set_cookie_headers'] = $setCookies;

            $this->dump('consume (cookie meta - shop2 après write)', $meta);
        }

        Shop::setContext($prevType, $prevShopId);

        $path = $this->normalizeBackPath($back);
        $to = rtrim($home, '/') . '/' . $path;

        $this->dump('consume (redirect final)', ['to' => $to]);

        if (Tools::getValue('ckc_debug') == '2') {
            $this->dd('consume (stop avant redirect)', ['to' => $to]);
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        if (headers_sent($hsFile, $hsLine)) {
            $this->dump('consume (headers déjà envoyés, fallback JS final)', ['file' => $hsFile, 'line' => $hsLine, 'to' => $to]);
            echo '<script>location.href=' . json_encode($to) . ';</script>';
            exit;
        }
        header('Location: ' . $to, true, 302);
        exit;
    }

    private function getShopBase(int $idShop): string
    {
        // Récupère les infos directes du shop cible
        $shop = new Shop($idShop);

        // Si pour une raison X l'objet n'est pas chargé, fallback propre
        if (!Validate::isLoadedObject($shop)) {
            return rtrim($this->context->link->getBaseLink($idShop, true), '/') . '/';
        }

        $domain = $shop->domain_ssl ?: $shop->domain;  // force https si dispo
        $phys = $shop->physical_uri ?: '/';
        $virt = $shop->virtual_uri ?: '';

        // Construit la base: https://domain/physical_uri/virtual_uri/
        $base = 'https://' . rtrim($domain, '/')
            . rtrim($phys, '/') . '/'
            . ltrim($virt, '/');

        if (Tools::getValue('ckc_debug') == '2') {
            header('Content-Type: text/plain; charset=utf-8');
            echo "CKC EP DEBUG · getShopBase\n";
            var_export(['idShop' => $idShop, 'domain' => $domain, 'phys' => $phys, 'virt' => $virt, 'base' => $base]);
        }

        return rtrim($base, '/') . '/';
    }


    private function normalizeBackPath(string $back): string
    {
        $b = trim($back);
        if ($b === '' || $b === 'my-account') {
            return 'index.php?controller=my-account';
        }
        if (preg_match('~^https?://~i', $b)) {
            // Sécurité: n’autorise que le même domaine, sinon renvoie vers my-account
            $host = parse_url($b, PHP_URL_HOST);
            if ($host && $host === ($_SERVER['HTTP_HOST'] ?? '')) {
                $rel = parse_url($b, PHP_URL_PATH);
                $qs = parse_url($b, PHP_URL_QUERY);
                return ltrim($rel, '/') . ($qs ? '?' . $qs : '');
            }
            return 'index.php?controller=my-account';
        }
        return ltrim($b, '/');
    }

}
