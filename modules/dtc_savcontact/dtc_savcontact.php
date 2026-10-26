<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_savcontact extends Module
{
    /** Contact SAV used for order-related customer threads */
    const ORDER_CONTACT_ID = 2;

    public function __construct()
    {
        $this->name = 'dtc_savcontact';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SAV Contact commande');
        $this->description = $this->l('Rattache les threads commande au contact SAV et masque les messages automatiques (HiPay, rupture) dans la liste.');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionObjectCustomerThreadAddBefore')
            && $this->registerHook('actionObjectCustomerMessageAddBefore')
            && $this->registerHook('actionAdminCustomerThreadsListingFieldsModifier')
            && $this->migrateAutoMessagesToPrivate();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Threads créés depuis une commande ont id_contact = 0 par défaut :
     * on les rattache au contact SAV.
     *
     * @param array $params
     */
    public function hookActionObjectCustomerThreadAddBefore($params)
    {
        if (empty($params['object']) || !($params['object'] instanceof CustomerThread)) {
            return;
        }

        /** @var CustomerThread $thread */
        $thread = $params['object'];

        if ((int) $thread->id_order > 0 && (int) $thread->id_contact === 0) {
            $thread->id_contact = (int) self::ORDER_CONTACT_ID;
        }
    }

    /**
     * Marque les messages système comme privés (non affichés dans la liste SAV).
     * L'e-mail client reste géré par le handler (isPrivate du command), pas par cet objet.
     *
     * @param array $params
     */
    public function hookActionObjectCustomerMessageAddBefore($params)
    {
        if (empty($params['object']) || !($params['object'] instanceof CustomerMessage)) {
            return;
        }

        /** @var CustomerMessage $message */
        $message = $params['object'];

        if (self::isAutomaticMessage((string) $message->message)) {
            $message->private = 1;
        }
    }

    /**
     * Liste SAV : n'utiliser que les messages non privés pour l'aperçu,
     * et masquer les threads qui n'ont que des messages automatiques.
     *
     * @param array $params
     */
    public function hookActionAdminCustomerThreadsListingFieldsModifier($params)
    {
        if (!empty($params['join'])) {
            $params['join'] = str_replace(
                'ON cm.`id_customer_thread` = a.`id_customer_thread`',
                'ON cm.`id_customer_thread` = a.`id_customer_thread` AND cm.`private` = 0',
                $params['join']
            );
        }

        $params['where'] .= ' AND EXISTS (
            SELECT 1
            FROM `' . _DB_PREFIX_ . 'customer_message` cm_human
            WHERE cm_human.`id_customer_thread` = a.`id_customer_thread`
              AND cm_human.`private` = 0
        )';
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public static function isAutomaticMessage($message)
    {
        $message = trim(html_entity_decode(strip_tags($message), ENT_QUOTES, 'UTF-8'));

        if ($message === '') {
            return false;
        }

        if (stripos($message, 'HiPay Notification') !== false) {
            return true;
        }

        // Template order_message "Rupture" (ps_order_message id 6)
        if (stripos($message, 'Nous sommes actuellement en rupture de stock du / des produit(s)') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Passe en privé les messages auto déjà présents en base.
     *
     * @return bool
     */
    public function migrateAutoMessagesToPrivate()
    {
        $ok = Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'customer_message`
             SET `private` = 1
             WHERE `private` = 0
               AND (
                    `message` LIKE "HiPay Notification%"
                 OR `message` LIKE "%Nous sommes actuellement en rupture de stock du / des produit(s)%"
               )'
        );

        return (bool) $ok;
    }
}
