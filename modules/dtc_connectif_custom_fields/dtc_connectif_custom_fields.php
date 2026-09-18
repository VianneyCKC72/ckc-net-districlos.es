<?php
/**
 * Module Connectif - Champs personnalisés DISTRICLOS
 *
 * Envoie des champs personnalisés (magasin, flag_get, etc.) à Connectif via l'API.
 * Compatible avec le module Connectif existant - aucun conflit.
 * Le flag_get est géré par le module Netreviews/Skeepers.
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!defined('CN_ERROR_CODE')) {
    define('CN_ERROR_CODE', '10185');
}

class Dtc_Connectif_Custom_Fields extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_connectif_custom_fields';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.1';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '8.99.99'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Connectif - Champs personnalisés DTC');
        $this->description = $this->l('Remonte le magasin et autres champs personnalisés vers Connectif via l\'API.');

        $this->installWebhookInfrastructure();
    }

    /**
     * Crée la table de suivi SMS devis et génère le token webhook si nécessaire.
     */
    protected function installWebhookInfrastructure(): bool
    {
        try {
            $dbOk = Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_connectif_devis_sms` (
                    `id_customer` INT UNSIGNED NOT NULL,
                    `date_dernier_sms` DATETIME NOT NULL,
                    PRIMARY KEY (`id_customer`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4'
            );

            if (!$dbOk) {
                return false;
            }

            if (!Configuration::get('DTC_CONNECTIF_WEBHOOK_TOKEN')) {
                return (bool) Configuration::updateValue('DTC_CONNECTIF_WEBHOOK_TOKEN', Tools::passwdGen(32));
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function install(): bool
    {
        if (!Module::isEnabled('connectif')) {
            $this->_errors[] = $this->l('Le module Connectif doit être installé et activé pour utiliser ce module.');

            return false;
        }

        return $this->installWebhookInfrastructure()
            && parent::install()
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('actionOpartQuotationCreate')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('actionCustomerAccountUpdate')
            && $this->registerHook('actionObjectCustomerAddAfter')
            && $this->registerHook('actionObjectCustomerUpdateBefore');
    }

    public function uninstall(): bool
    {
        return parent::uninstall();
    }

    /**
     * Récupère le magasin du client.
     * Si idOrder est fourni, priorise le magasin de cette commande (attribué par ckcstockmultistore).
     * Sinon, utilise la dernière commande du client avec id_store > 0.
     * Si aucune commande avec id_store : "France".
     */
    public function getMagasinForCustomer(int $idCustomer, ?int $idOrder = null): string
    {
        if ($idCustomer <= 0) {
            return 'France';
        }

        try {
            // Priorité : magasin de la commande spécifiée (attribué lors du changement de statut)
            if ($idOrder !== null && $idOrder > 0) {
                $magasin = Db::getInstance()->getValue(
                    'SELECT s.name FROM `' . _DB_PREFIX_ . 'orders` o
                    INNER JOIN `' . _DB_PREFIX_ . 'ckcstockstores_stores` s ON s.id_store = o.id_store
                    WHERE o.id_order = ' . (int) $idOrder . ' AND o.id_store > 0'
                );
                if ($magasin !== false && $magasin !== null && $magasin !== '') {
                    return (string) $magasin;
                }
            }

            // Fallback : dernière commande du client avec id_store
            $query = new DbQuery();
            $query->select('s.name');
            $query->from('orders', 'o');
            $query->innerJoin('ckcstockstores_stores', 's', 's.id_store = o.id_store');
            $query->where('o.id_customer = ' . (int) $idCustomer);
            $query->where('o.id_store > 0');
            $query->orderBy('o.date_add DESC');

            $magasin = Db::getInstance()->getValue($query);

            if ($magasin === false) {
                $dbError = Db::getInstance()->getMsgError();
                if (empty($dbError)) {
                    return 'France';
                }
                return 'France';
            }

            return $magasin ?: 'France';
        } catch (Throwable $e) {
            return 'France';
        }
    }

    /**
     * Récupère la valeur de flag_get depuis ps_av_orders pour une commande donnée.
     * Le flag_get est géré par le module Netreviews/Skeepers.
     * flag_get = 1 → la commande est envoyée à Skeepers
     * flag_get = 2 → la commande n'est pas envoyée à Skeepers
     *
     * @param int $idOrder ID de la commande
     * @return int|null Valeur de flag_get (1 ou 2) ou null si non trouvé
     */
    /**
     * Vérifie si le statut de commande est dans la liste AV_ORDERSTATESCHOOSEN de Netreviews.
     * Interroge toutes les clés AV_ORDERSTATESCHOOSEN% en base pour couvrir tous les groupName possibles.
     */
    protected function isOrderStatusInNetreviewsList(int $idOrderState, int $idShop, int $idLang): bool
    {
        try {
            $rows = Db::getInstance()->executeS(
                'SELECT name, value FROM `' . _DB_PREFIX_ . 'configuration`
                WHERE name LIKE "AV_ORDERSTATESCHOOSEN%"
                AND value != ""
                AND (id_shop = ' . (int) $idShop . ' OR id_shop = 0 OR id_shop IS NULL)
                AND (id_shop_group = 0 OR id_shop_group IS NULL)'
            );

            if (!is_array($rows)) {
                return false;
            }

            $idOrderStateStr = (string) $idOrderState;

            foreach ($rows as $row) {
                $statusList = trim($row['value'] ?? '');
                if ($statusList === '') {
                    continue;
                }
                $statusListArray = array_map('trim', explode(';', $statusList));
                if (in_array($idOrderStateStr, $statusListArray, true)) {
                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function getFlagGetForOrder(int $idOrder): ?int
    {
        if ($idOrder <= 0) {
            return null;
        }

        try {
            $flagGet = Db::getInstance()->getValue(
                'SELECT flag_get FROM `' . _DB_PREFIX_ . 'av_orders` WHERE id_order = ' . (int) $idOrder
            );

            if ($flagGet === false || $flagGet === null) {
                return null;
            }

            return (int) $flagGet;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Construit les champs personnalisés à envoyer à Connectif.
     * Extensible : ajouter d'autres champs ici.
     */
    public function getCustomFieldsForCustomer(int $idCustomer, ?int $idOrder = null): array
    {
        $fields = [];

        $magasin = $this->getMagasinForCustomer($idCustomer, $idOrder);
        $fields['magasin'] = $magasin;
        $fields['groupe_client'] = $this->getCustomerGroupForCustomer($idCustomer);

        // Ajout de flag_get si une commande est fournie
        // Le flag_get est géré par le module Netreviews/Skeepers
        // Connectif attend flag_get en string, donc on convertit
        if ($idOrder !== null && $idOrder > 0) {
            $flagGet = $this->getFlagGetForOrder($idOrder);
            if ($flagGet !== null) {
                $fields['flag_get'] = (string) $flagGet;
            }
        }

        $fields = array_merge($fields, $this->getQuotationFieldsForCustomer($idCustomer));

        return $fields;
    }

    /**
     * Champs devis OpartDevis pour Connectif (a_cree_devis, date_dernier_devis, nb_devis).
     * a_cree_devis est obligatoire côté Connectif : toujours envoyé.
     * a_cree_devis repasse à false après réception du webhook SMS Connectif,
     * jusqu'à la création d'un nouveau devis postérieur au dernier SMS.
     */
    protected function getQuotationFieldsForCustomer(int $idCustomer): array
    {
        if ($idCustomer <= 0) {
            return [
                'a_cree_devis' => false,
                'nb_devis' => 0,
            ];
        }

        try {
            $nbDevis = (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'opartdevis` WHERE id_customer = ' . (int) $idCustomer
            );

            $aCreeDevis = false;
            $fields = [
                'a_cree_devis' => false,
                'nb_devis' => $nbDevis,
            ];

            if ($nbDevis > 0) {
                $lastDate = Db::getInstance()->getValue(
                    'SELECT date_add FROM `' . _DB_PREFIX_ . 'opartdevis`
                    WHERE id_customer = ' . (int) $idCustomer . '
                    ORDER BY date_add DESC'
                );

                $dateDernierSms = Db::getInstance()->getValue(
                    'SELECT date_dernier_sms FROM `' . _DB_PREFIX_ . 'dtc_connectif_devis_sms`
                    WHERE id_customer = ' . (int) $idCustomer
                );

                if (!$dateDernierSms) {
                    $aCreeDevis = true;
                } elseif ($lastDate && strtotime((string) $lastDate) > strtotime((string) $dateDernierSms)) {
                    $aCreeDevis = true;
                }

                $fields['a_cree_devis'] = $aCreeDevis;

                if ($lastDate) {
                    $fields['date_dernier_devis'] = date('Y-m-d', strtotime((string) $lastDate));
                }
            }

            return $fields;
        } catch (Throwable $e) {
            return [
                'a_cree_devis' => false,
                'nb_devis' => 0,
            ];
        }
    }

    /**
     * Repousse les champs devis vers Connectif (utilisé après réception du webhook SMS).
     */
    public function syncQuotationFieldsToConnectif(string $email, int $idCustomer): bool
    {
        if ($idCustomer <= 0 || empty($email) || !Validate::isEmail($email)) {
            return false;
        }

        $fields = $this->getQuotationFieldsForCustomer($idCustomer);

        PrestaShopLogger::addLog(
            'Dtc_Connectif_Custom_Fields: re-sync devis après SMS pour client ' . $idCustomer
            . ' - Champs: ' . json_encode($fields),
            1,
            null,
            'Dtc_Connectif_Custom_Fields',
            $idCustomer,
            true
        );

        return $this->syncCustomFieldsToConnectif($email, $fields);
    }

    /**
     * Récupère le nom du groupe client par défaut.
     */
    public function getCustomerGroupForCustomer(int $idCustomer): string
    {
        if ($idCustomer <= 0) {
            return '';
        }

        try {
            $groupId = (int) Db::getInstance()->getValue(
                'SELECT id_default_group FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer = ' . (int) $idCustomer
            );

            if ($groupId <= 0) {
                return '';
            }

            $idLang = 0;
            if (Context::getContext() && Context::getContext()->language) {
                $idLang = (int) Context::getContext()->language->id;
            }
            if ($idLang <= 0) {
                $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
            }

            $groupName = Db::getInstance()->getValue(
                'SELECT name
                FROM `' . _DB_PREFIX_ . 'group_lang`
                WHERE id_group = ' . (int) $groupId . '
                AND id_lang = ' . (int) $idLang
            );

            if (!$groupName) {
                $groupName = Db::getInstance()->getValue(
                    'SELECT name
                    FROM `' . _DB_PREFIX_ . 'group_lang`
                    WHERE id_group = ' . (int) $groupId . '
                    ORDER BY id_lang ASC'
                );
            }

            return $groupName ? (string) $groupName : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Envoie les champs personnalisés à Connectif via l'API.
     */
    protected function syncCustomFieldsToConnectif(string $email, array $customFields): bool
    {
        if (empty($email) || empty($customFields)) {
            return false;
        }

        if (!file_exists(_PS_MODULE_DIR_ . 'connectif/classes/cn-config-utils.php')) {
            return false;
        }

        require_once _PS_MODULE_DIR_ . 'connectif/classes/cn-account-utils.php';
        require_once _PS_MODULE_DIR_ . 'connectif/classes/cn-config-utils.php';
        require_once _PS_MODULE_DIR_ . 'connectif/helpers/connectif-api-helper.php';

        $cnConfigUtils = new CnConfigUtils();
        $connectifPublicApi = $cnConfigUtils->getConnectifPublicApiUrl();

        if (!$connectifPublicApi) {
            return false;
        }

        $patched = false;

        if (Configuration::get('CN_MULTILANG_STATUS') === '1') {
            $accounts = CnAccountUtils::getAccounts();
            if (is_array($accounts)) {
                foreach ($accounts as $account) {
                    if (empty($account->is_active) || $account->is_active !== '1') {
                        continue;
                    }
                    $storeApiKey = CnConfigUtils::getAuthApiKey($account->apiKeyId ?? '', $account->apiKeySecret ?? '');
                    if ($storeApiKey && $this->patchContact($email, $connectifPublicApi, $customFields, $storeApiKey)) {
                        $patched = true;
                    }
                }
            }
        } else {
            $storeApiKey = $cnConfigUtils->getApiKey();
            if ($storeApiKey && $this->patchContact($email, $connectifPublicApi, $customFields, $storeApiKey)) {
                $patched = true;
            }
        }

        return $patched;
    }

    /**
     * Appelle l'API PATCH de Connectif.
     */
    protected function patchContact(string $email, string $apiUrl, array $contact, string $storeApiKey): bool
    {
        try {
            $result = ConnectifApiHelper::patchContact($email, $apiUrl, $contact, $storeApiKey);

            return $result === true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function hookActionCustomerAccountUpdate(array $params): void
    {
        $customer = $params['customer'] ?? null;

        if (!$customer instanceof Customer) {
            return;
        }

        $this->syncForCustomer($customer);
    }

    /**
     * Commande validée : mettre à jour le magasin du client.
     */
    public function hookActionValidateOrder(array $params): void
    {
        $order = $params['order'] ?? null;
        $customer = $params['customer'] ?? null;

        if (!$order instanceof Order || !$customer instanceof Customer) {
            return;
        }

        $this->syncForCustomer($customer, (int) $order->id);
    }

    /**
     * Statut de commande mis à jour : synchroniser flag_get vers Connectif.
     * 
     * Ce hook attend que Netreviews/Skeepers ait défini flag_get avant de l'envoyer à Connectif.
     * L'ordre d'exécution des hooks peut varier selon l'ordre d'installation des modules.
     */
    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        // PrestaShopLogger::addLog(
        //     'Dtc_Connectif_Custom_Fields: hookActionOrderStatusPostUpdate appelé - id_order=' . (isset($params['id_order']) ? (int) $params['id_order'] : 'N/A'),
        //     1,
        //     null,
        //     'Dtc_Connectif_Custom_Fields',
        //     isset($params['id_order']) ? (int) $params['id_order'] : 0,
        //     true
        // );

        $newOrderStatus = $params['newOrderStatus'] ?? null;
        $idOrder = isset($params['id_order']) ? (int) $params['id_order'] : 0;

        if (!$newOrderStatus instanceof OrderState || $idOrder <= 0) {
            return;
        }

        // Récupérer la commande et le client
        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        // Ne traiter que si le statut est dans la liste AV_ORDERSTATESCHOOSEN de Netreviews
        if (!$this->isOrderStatusInNetreviewsList((int) $newOrderStatus->id, (int) $order->id_shop, (int) $order->id_lang)) {
            return;
        }

        $customer = new Customer((int) $order->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return;
        }

        // Attendre que Netreviews ait défini flag_get (max 5 tentatives avec 0.5 seconde entre chaque)
        // L'ordre d'exécution des hooks peut varier selon l'ordre d'installation des modules
        $maxAttempts = 5;
        $flagGet = null;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $flagGet = $this->getFlagGetForOrder($idOrder);
            
            if ($flagGet !== null) {
                // PrestaShopLogger::addLog(
                //     'Dtc_Connectif_Custom_Fields: flag_get trouvé pour commande ' . $idOrder . ' (tentative ' . $attempt . '): ' . $flagGet,
                //     1,
                //     null,
                //     'Dtc_Connectif_Custom_Fields',
                //     $idOrder,
                //     true
                // );
                break;
            }
            
            // Attendre 0.5 seconde avant la prochaine tentative
            if ($attempt < $maxAttempts) {
                usleep(500000); // 0.5 seconde en microsecondes
            }
        }

        // if ($flagGet === null) {
        //     PrestaShopLogger::addLog(
        //         'Dtc_Connectif_Custom_Fields: flag_get non trouvé pour commande ' . $idOrder . ' après ' . $maxAttempts . ' tentatives',
        //         3,
        //         null,
        //         'Dtc_Connectif_Custom_Fields',
        //         $idOrder,
        //         true
        //     );
        // }

        $this->syncForCustomer($customer, $idOrder);
    }

    /**
     * Devis OpartDevis créé : synchroniser a_cree_devis, date_dernier_devis, nb_devis vers Connectif.
     */
    public function hookActionOpartQuotationCreate(array $params): void
    {
        $idOpartDevis = isset($params['id_opart_devis']) ? (int) $params['id_opart_devis'] : 0;

        PrestaShopLogger::addLog(
            'Dtc_Connectif_Custom_Fields: hookActionOpartQuotationCreate appelé - id_opartdevis=' . ($idOpartDevis > 0 ? $idOpartDevis : 'N/A'),
            1,
            null,
            'Dtc_Connectif_Custom_Fields',
            $idOpartDevis,
            true
        );

        if ($idOpartDevis <= 0) {
            return;
        }

        $idCustomer = (int) Db::getInstance()->getValue(
            'SELECT id_customer FROM `' . _DB_PREFIX_ . 'opartdevis` WHERE id_opartdevis = ' . (int) $idOpartDevis
        );

        if ($idCustomer <= 0) {
            PrestaShopLogger::addLog(
                'Dtc_Connectif_Custom_Fields: devis ' . $idOpartDevis . ' - client introuvable en base',
                3,
                null,
                'Dtc_Connectif_Custom_Fields',
                $idOpartDevis,
                true
            );

            return;
        }

        $customer = new Customer($idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            PrestaShopLogger::addLog(
                'Dtc_Connectif_Custom_Fields: devis ' . $idOpartDevis . ' - objet Customer invalide (id_customer=' . $idCustomer . ')',
                3,
                null,
                'Dtc_Connectif_Custom_Fields',
                $idOpartDevis,
                true
            );

            return;
        }

        $this->syncForCustomer($customer, null, $idOpartDevis);
    }

    /**
     * Inscription client : magasin par défaut "France".
     */
    public function hookActionCustomerAccountAdd(array $params): void
    {
        $customer = $params['newCustomer'] ?? null;

        if (!$customer instanceof Customer) {
            return;
        }

        $this->syncForCustomer($customer);
    }

    /**
     * Client créé depuis le back-office.
     */
    public function hookActionObjectCustomerAddAfter(array $params): void
    {
        $customer = $params['object'] ?? null;

        if (!$customer instanceof Customer) {
            return;
        }

        $this->syncForCustomer($customer);
    }

    /**
     * Client mis à jour depuis le back-office.
     */
    public function hookActionObjectCustomerUpdateBefore(array $params): void
    {
        $customer = $params['object'] ?? null;

        if (!$customer instanceof Customer) {
            return;
        }

        $this->syncForCustomer($customer);
    }

    private function syncForCustomer(Customer $customer, ?int $idOrder = null, ?int $idOpartDevis = null): void
    {
        $email = $customer->email;
        $idCustomer = $this->resolveCustomerId($customer, $email);

        if (empty($email) || !Validate::isEmail($email)) {
            if ($idOpartDevis !== null && $idOpartDevis > 0) {
                PrestaShopLogger::addLog(
                    'Dtc_Connectif_Custom_Fields: devis ' . $idOpartDevis . ' - email client invalide, sync Connectif annulée',
                    3,
                    null,
                    'Dtc_Connectif_Custom_Fields',
                    $idOpartDevis,
                    true
                );
            }

            return;
        }

        $customFields = $this->getCustomFieldsForCustomer($idCustomer, $idOrder);

        if ($idOpartDevis !== null && $idOpartDevis > 0) {
            $devisFields = array_intersect_key($customFields, array_flip([
                'a_cree_devis',
                'date_dernier_devis',
                'nb_devis',
            ]));

            PrestaShopLogger::addLog(
                'Dtc_Connectif_Custom_Fields: Envoi à Connectif pour devis ' . $idOpartDevis
                . ' (client ' . $idCustomer . ') - Champs devis: ' . json_encode($devisFields),
                1,
                null,
                'Dtc_Connectif_Custom_Fields',
                $idOpartDevis,
                true
            );
        }

        $result = $this->syncCustomFieldsToConnectif($email, $customFields);

        if ($idOpartDevis !== null && $idOpartDevis > 0) {
            PrestaShopLogger::addLog(
                'Dtc_Connectif_Custom_Fields: Résultat envoi Connectif pour devis ' . $idOpartDevis . ': ' . ($result ? 'SUCCÈS' : 'ÉCHEC'),
                $result ? 1 : 3,
                null,
                'Dtc_Connectif_Custom_Fields',
                $idOpartDevis,
                true
            );
        }
    }

    private function resolveCustomerId(Customer $customer, string $email): int
    {
        $idCustomer = (int) $customer->id;

        if ($idCustomer > 0) {
            return $idCustomer;
        }

        if (!Validate::isEmail($email)) {
            return 0;
        }

        $idByEmail = (int) Db::getInstance()->getValue(
            'SELECT id_customer FROM `' . _DB_PREFIX_ . 'customer` WHERE email = "' . pSQL($email) . '"'
        );

        if ($idByEmail > 0) {
            return $idByEmail;
        }

        return 0;
    }

}
