<?php

class Dtc_ordertrackingTrackModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $auth = false;

    /** @var bool */
    public $guestAllowed = true;

    public function init()
    {
        // Page publique, pas d’auth obligatoire.
        parent::init();
    }

    public function setMedia()
    {
        parent::setMedia();

        $this->registerStylesheet(
            'dtc-ordertracking',
            'modules/' . $this->module->name . '/views/css/tracking.css',
            ['media' => 'all', 'priority' => 150]
        );

        if (file_exists(_PS_MODULE_DIR_ . $this->module->name . '/views/js/tracking.js')) {
            $this->registerJavascript(
                'dtc-ordertracking',
                'modules/' . $this->module->name . '/views/js/tracking.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }
    }

    public function initContent()
    {
        parent::initContent();

        // Paramètre canonique : ?code=XXXX. On garde un repli sur ?ref=XXXX
        // pour ne pas casser les anciens liens (e-mails déjà envoyés).
        $ref = trim((string) Tools::getValue('code', Tools::getValue('ref', '')));
        $id = (int) Tools::getValue('id', 0);
        $token = trim((string) Tools::getValue('token', ''));
        $email = trim((string) Tools::getValue('email', ''));

        $order = null;
        $canView = false;

        if ($ref !== '' && $token !== '' && Dtc_ordertracking::verifyToken($ref, $token)) {
            $order = Dtc_ordertracking::getOrderByReference($ref);
            $canView = (bool) $order;
        } elseif ($this->context->customer && $this->context->customer->isLogged() && $ref !== '') {
            $order = Dtc_ordertracking::getOrderByReference($ref);
            if ($order && (int) $order->id_customer === (int) $this->context->customer->id) {
                $canView = true;
            } elseif ($order) {
                // Option 1 : si connecté, on ne doit jamais accéder à une commande d'un autre compte.
                $this->errors[] = $this->module->l('La commande n’est pas liée à votre compte.');
            }
        } elseif ((!$this->context->customer || !$this->context->customer->isLogged()) && $ref !== '' && Dtc_ordertracking::allowReferenceOnly() && $this->checkRateLimit()) {
            // Mode public "référence seule" (sans token) : à activer explicitement en BO.
            // On l’accepte aussi en GET pour permettre le partage d’un lien ?ref=XXX (avec ou sans id).
            $order = Dtc_ordertracking::getOrderByReference($ref);
            // Si un id est fourni, on le confronte à la référence pour un contrôle minimal.
            if ($order && $id > 0 && (int) $order->id !== $id) {
                $order = null;
            }
            $canView = (bool) $order;
            if (!$canView) {
                $this->errors[] = $this->module->l('Référence invalide.');
            }
        } elseif ($this->isPostRequest() && $this->checkRateLimit() && $ref !== '' && $email !== '') {
            $order = Dtc_ordertracking::getOrderByReference($ref);
            if ($order) {
                $customer = new Customer((int) $order->id_customer);
                $canView = Validate::isLoadedObject($customer)
                    && Tools::strtolower((string) $customer->email) === Tools::strtolower($email);
            }

            if (!$canView) {
                $this->errors[] = $this->module->l('Référence ou email invalide.');
            }
        } elseif ($this->isPostRequest() && $ref !== '' && $email === '' && !Dtc_ordertracking::allowReferenceOnly()) {
            // Soumission du formulaire sans email alors que l’option "référence seule" n’est pas activée.
            $this->errors[] = $this->module->l('Merci de renseigner votre email, ou activez la recherche par référence seule dans la configuration du module.');
        }

        if (!$canView || !$order) {
            $this->context->smarty->assign([
                'form_action' => $this->context->link->getModuleLink($this->module->name, 'track', [], true),
                'code_value' => Tools::safeOutput($ref),
                'email_value' => Tools::safeOutput($email),
                'allow_ref_only' => (int) Dtc_ordertracking::allowReferenceOnly(),
                'errors' => $this->errors,
                'login_url' => $this->context->link->getPageLink('authentication', true),
            ]);

            $this->setTemplate('module:dtc_ordertracking/views/templates/front/track_form.tpl');
            return;
        }

        // Canonicalisation : si l'accès public par référence est activé et qu'on vient d'un POST,
        // on redirige vers une URL partageable ?ref=XXX (PRG pattern).
        if ($this->isPostRequest() && Dtc_ordertracking::allowReferenceOnly()) {
            Tools::redirect($this->context->link->getModuleLink($this->module->name, 'track', [
                'code' => (string) $order->reference,
            ], true));
        }

        $data = $this->buildTrackingData($order);

        $this->context->smarty->assign($data);
        $this->setTemplate('module:dtc_ordertracking/views/templates/front/track.tpl');
    }

    protected function isPostRequest(): bool
    {
        return Tools::strtolower($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'post';
    }

    /**
     * Rate limit simple : 3 tentatives / minute / IP.
     */
    protected function checkRateLimit(): bool
    {
        $ip = (string) Tools::getRemoteAddr();
        $baseKey = 'dtc_ot_rl_' . sha1($ip);
        $tsKey = $baseKey . '_ts';
        $now = time();

        $lastTs = (int) Cache::retrieve($tsKey);
        $count = (int) Cache::retrieve($baseKey);

        // Remise à zéro si la fenêtre d’1 minute est dépassée.
        if ($lastTs <= 0 || ($now - $lastTs) > 60) {
            $lastTs = $now;
            $count = 0;
        }

        if ($count >= 3) {
            $this->errors[] = $this->module->l('Trop de tentatives. Merci de réessayer dans une minute.');
            return false;
        }

        Cache::store($baseKey, $count + 1);
        Cache::store($tsKey, $lastTs);

        return true;
    }

    protected function buildTrackingData(Order $order): array
    {
        $idLang = (int) $this->context->language->id;

        $labels = Dtc_ordertracking::getStepLabels();
        $mapping = Dtc_ordertracking::getMapping(); // id_order_state => step
        // PrestaShop 8.x : l’historique se récupère via Order::getHistory().
        $history = $order->getHistory($idLang, false, true);

        $stepDates = [];
        $maxStepReached = 0;
        if (is_array($history)) {
            foreach ($history as $line) {
                $idState = (int) ($line['id_order_state'] ?? 0);
                $step = (int) ($mapping[$idState] ?? 0);
                if ($step >= 1 && $step <= 5) {
                    $maxStepReached = max($maxStepReached, $step);
                    if (!isset($stepDates[$step])) {
                        $stepDates[$step] = (string) ($line['date_add'] ?? '');
                    }
                }
            }
        }

        $timeline = [];
        for ($i = 1; $i <= 5; $i++) {
            $timeline[] = [
                'step' => $i,
                'label' => (string) ($labels[$i] ?? ('Étape ' . $i)),
                'done' => $maxStepReached >= $i && $maxStepReached !== 0,
                'current' => $maxStepReached === $i && $maxStepReached !== 0,
                'date' => $stepDates[$i] ?? null,
            ];
        }

        $currency = new Currency((int) $order->id_currency);
        $totalIncl = (float) $order->total_paid_tax_incl;
        $totalExcl = (float) $order->total_paid_tax_excl;

        $pro = $this->context->smarty->getTemplateVars('pro');
        $isPro = false;
        if (is_array($pro)) {
            $isPro = !empty($pro['env']) || !empty($pro['user']['is_pro']);
        }

        $trackingUrl = Dtc_ordertracking::getTrackingUrl($order);
        $currentLabel = $maxStepReached >= 1 && $maxStepReached <= 5
            ? (string) ($labels[$maxStepReached] ?? '')
            : '';

        return [
            'order_reference' => Tools::safeOutput((string) $order->reference),
            'order_date' => Tools::displayDate($order->date_add, $idLang, true),
            'order_total_incl' => Tools::displayPrice($totalIncl, $currency),
            'order_total_excl' => Tools::displayPrice($totalExcl, $currency),
            'tracking_url' => $trackingUrl,
            // Laisser Smarty échapper : éviter le double-encodage (&ccedil; → &amp;ccedil;)
            'current_step_label' => $currentLabel,
            'timeline' => $timeline,
            // Lien contact (plus fiable que $urls.pages.contact selon le contexte/template).
            'contact_url' => $this->context->link->getPageLink('contact', true),
            'is_pro' => (int) $isPro,
        ];
    }
}

