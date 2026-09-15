<?php

if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', dirname(__FILE__, 3));
}

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

if (
    isset($_POST['ajax']) && $_POST['ajax'] == '1' &&
    isset($_POST['action']) && $_POST['action'] == 'getCartId'
) {
    $cart_id = (int)Context::getContext()->cart->id;
    die(json_encode(['cart_id' => $cart_id]));
}

if (
    isset($_POST['ajax']) && $_POST['ajax'] == '1' &&
    isset($_POST['action']) && $_POST['action'] == 'setCustomPickupCarrier'
) {
    $id_cart = isset($_POST['id_cart']) ? (int)$_POST['id_cart'] : (int)Context::getContext()->cart->id;
    $id_carrier = (int) $_POST['id_carrier'];
    $id_store = isset($_POST['id_store']) ? (int)$_POST['id_store'] : 0;

    // Log pour debug - DÉSACTIVÉ
    // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
    //     date('Y-m-d H:i:s').' [AJAX setCustomPickupCarrier] id_cart='.$id_cart.' id_carrier='.$id_carrier.' id_store='.$id_store.' POST='.json_encode($_POST).' REQUEST_URI='.($_SERVER['REQUEST_URI'] ?? 'N/A').PHP_EOL,
    //     FILE_APPEND
    // );

    if ($id_cart && $id_carrier) {
        // Vérifie si le transporteur envoyé est un transporteur de retrait
        $carrier = new Carrier($id_carrier);
        $isPickup = false;
        if (Validate::isLoadedObject($carrier)) {
            $isPickup = stripos(trim($carrier->name), 'Retrait') !== false;
        }

        // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
        //     date('Y-m-d H:i:s').' [AJAX] carrier_name="'.trim($carrier->name).'" isPickup='.($isPickup ? 'true' : 'false').PHP_EOL,
        //     FILE_APPEND
        // );

        // Vérifie le transporteur actuel du panier
        $cart = new Cart($id_cart);
        $currentCartCarrier = (int) $cart->id_carrier;
        // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
        //     date('Y-m-d H:i:s').' [AJAX] currentCartCarrier='.$currentCartCarrier.' id_carrier='.$id_carrier.PHP_EOL,
        //     FILE_APPEND
        // );

        // Ne traite que si c'est un transporteur de retrait ET que le transporteur envoyé correspond au transporteur actuel
        if ($isPickup && ($currentCartCarrier == $id_carrier || $currentCartCarrier == 0 || $currentCartCarrier == 1682)) {
            // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
            //     date('Y-m-d H:i:s').' [AJAX] ✅ Traitement du retrait '.$id_carrier.PHP_EOL,
            //     FILE_APPEND
            // );

            try {
                // Nettoie d'abord la configuration existante
                Configuration::deleteByName('CUSTOMPICKUP_CARRIER_' . $id_cart);
                $sql = "DELETE FROM "._DB_PREFIX_."configuration WHERE name = 'CUSTOMPICKUP_STORE_" . $id_cart . "'";
                Db::getInstance()->execute($sql);
                if (isset(Context::getContext()->cookie->{'custompickup_id_store_'.$id_cart})) {
                    unset(Context::getContext()->cookie->{'custompickup_id_store_'.$id_cart});
                    Context::getContext()->cookie->write();
                }

                Configuration::updateValue('CUSTOMPICKUP_CARRIER_' . $id_cart, $id_carrier);
                // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
                //     date('Y-m-d H:i:s').' [AJAX] ✅ Configuration mise à jour CUSTOMPICKUP_CARRIER_'.$id_cart.'='.$id_carrier.PHP_EOL,
                //     FILE_APPEND
                // );

                // Stocke l'id_store dans le cookie ET la base de données
                if ($id_store) {
                    // Nettoyer les anciennes configurations pour ce cart
                    $sql = "DELETE FROM "._DB_PREFIX_."configuration WHERE name = 'CUSTOMPICKUP_STORE_" . $id_cart . "'";
                    Db::getInstance()->execute($sql);

                    // Stocke dans la base de données et le cookie
                    Configuration::updateValue('CUSTOMPICKUP_STORE_' . $id_cart, $id_store);
                    Context::getContext()->cookie->{'custompickup_id_store_'.$id_cart} = $id_store;
                    Context::getContext()->cookie->write();
                    // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
                    //     date('Y-m-d H:i:s').' [AJAX] ✅ Store config mise à jour CUSTOMPICKUP_STORE_'.$id_cart.'='.$id_store.PHP_EOL,
                    //     FILE_APPEND
                    // );
                }

                // Si c'est un retrait, on force le transporteur
                $cart->id_carrier = $id_carrier;
                $cart->update();
                // Forcer la mise à jour du delivery_option (clé de déclenchement du JS PrestaShop)
                $deliveryOption = [$cart->id_address_delivery => $id_carrier . ','];
                $cart->setDeliveryOption($deliveryOption);
                $cart->save();
                // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
                //     date('Y-m-d H:i:s').' [AJAX] ✅ Cart mis à jour avec carrier='.$id_carrier.' (retrait forcé)'.PHP_EOL,
                //     FILE_APPEND
                // );

                die(json_encode(['success' => true]));
            } catch (Exception $e) {
                // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
                //     date('Y-m-d H:i:s').' [AJAX] ❌ Erreur - '.$e->getMessage().PHP_EOL,
                //     FILE_APPEND
                // );
                die(json_encode(['success' => false, 'message' => 'Erreur: '.$e->getMessage()]));
            }
        } else {
            // Si ce n'est pas un retrait, on nettoie la configuration existante et on ignore l'appel
            // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
            //     date('Y-m-d H:i:s').' [AJAX] ⚠️ Transporteur '.$id_carrier.' ignoré - isPickup='.($isPickup ? 'true' : 'false').' currentCartCarrier='.$currentCartCarrier.' condition='.($currentCartCarrier == $id_carrier || $currentCartCarrier == 0 || $currentCartCarrier == 1682 ? 'true' : 'false').PHP_EOL,
            //     FILE_APPEND
            // );
            Configuration::deleteByName('CUSTOMPICKUP_CARRIER_' . $id_cart);
            $sql = "DELETE FROM "._DB_PREFIX_."configuration WHERE name = 'CUSTOMPICKUP_STORE_" . $id_cart . "'";
            Db::getInstance()->execute($sql);
            if (isset(Context::getContext()->cookie->{'custompickup_id_store_'.$id_cart})) {
                unset(Context::getContext()->cookie->{'custompickup_id_store_'.$id_cart});
                Context::getContext()->cookie->write();
            }
            die(json_encode(['success' => false, 'message' => 'Transporteur non retrait ignoré']));
        }
    } else {
        // file_put_contents(_PS_ROOT_DIR_.'/log_custompickup_ajax.txt',
        //     date('Y-m-d H:i:s').' [AJAX] ❌ Erreur - id_cart ou id_carrier manquant - id_cart='.$id_cart.' id_carrier='.$id_carrier.PHP_EOL,
        //     FILE_APPEND
        // );
        die(json_encode(['success' => false]));
    }
}

class CustomPickup extends Module
{
    public function __construct()
    {
        $this->name = 'custompickup';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'DISTRICLOS';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Custom Pickup');
        $this->description = $this->l('Ajoute une info personnalisée sur les modes de livraison.');
    }

    public function install()
    {
        if (
            !parent::install() ||
            !$this->registerHook('displayCarrierExtraContent') ||
            !$this->registerHook('displayHeader') ||
            !$this->registerHook('actionCarrierProcess') ||
            !$this->registerHook('actionObjectOrderAddBefore') ||
            !$this->registerHook('actionCarrierUpdate')
        ) {
            return false;
        }

        // Ajoute l'onglet "Retrait magasin" sous Livraison
        $idParent = (int)Db::getInstance()->getValue(
            "SELECT id_tab FROM "._DB_PREFIX_."tab WHERE class_name = 'AdminParentShipping'"
        );

        if (!$idParent) {
            $idParent = 0;
        }

        $tab = new Tab();
        $tab->class_name = 'AdminCustomPickup';
        $tab->module = $this->name;
        $tab->id_parent = $idParent;

        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Retrait magasin';
        }

        if (!$tab->add()) {
            return false;
        }

        Configuration::updateValue('CUSTOMPICKUP_MAIN_CARRIER_NAME', 'Retrait chez DISTRICLOS');
        return true;
    }

    public function uninstall()
    {
        $idTab = (int)Db::getInstance()->getValue(
            "SELECT id_tab FROM "._DB_PREFIX_."tab WHERE class_name = 'AdminCustomPickup'"
        );

        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        return parent::uninstall();
    }

    /**
     * Récupère l'ID du transporteur principal
     */
    private function getMainCarrierId()
    {
        $mainCarrierName = Configuration::get('CUSTOMPICKUP_MAIN_CARRIER_NAME');
        $id_lang = (int)Context::getContext()->language->id;
        $carriers = Carrier::getCarriers($id_lang, true, false, false);

        foreach ($carriers as $carrier) {
            if (trim($carrier['name']) === trim($mainCarrierName)) {
                return $carrier['id_carrier'];
            }
        }
        return null;
    }

    private function getAllStores()
    {
        $id_lang = (int)$this->context->language->id;

        $sql = 'SELECT s.id_store, sl.name, s.city
            FROM '._DB_PREFIX_.'store s
            LEFT JOIN '._DB_PREFIX_.'store_lang sl ON s.id_store = sl.id_store AND sl.id_lang = '.$id_lang.'
            WHERE s.active = 1';

        return Db::getInstance()->executeS($sql);
    }

    private function logDebug($message) {
        file_put_contents(_PS_ROOT_DIR_.'/log_custompickup.txt', date('Y-m-d H:i:s').' '.$message.PHP_EOL, FILE_APPEND);
    }

    /**
     * Vérifie si un transporteur est un transporteur de retrait
     */
    private function isPickupCarrier($carrierId) {
        $carrier = new Carrier($carrierId);
        if (!Validate::isLoadedObject($carrier)) {
            return false;
        }

        $carrierName = trim($carrier->name);
        // $this->logDebug('isPickupCarrier: carrier_id='.$carrierId.' name="'.$carrierName.'"');

        // Vérifie si le nom contient "Retrait"
        return stripos($carrierName, 'Retrait') !== false;
    }

    /**
     * Nettoie la configuration custompickup pour un panier donné
     */
    private function clearCustomPickupConfig($cartId) {
        // $this->logDebug('clearCustomPickupConfig: cart_id='.$cartId);

        // Supprime la configuration du transporteur
        Configuration::deleteByName('CUSTOMPICKUP_CARRIER_' . $cartId);
        // $this->logDebug('clearCustomPickupConfig: configuration transporteur supprimée');

        // Supprime la configuration du magasin
        $sql = "DELETE FROM "._DB_PREFIX_."configuration WHERE name = 'CUSTOMPICKUP_STORE_" . $cartId . "'";
        Db::getInstance()->execute($sql);
        // $this->logDebug('clearCustomPickupConfig: configuration magasin supprimée');

        // Supprime le cookie
        if (isset(Context::getContext()->cookie->{'custompickup_id_store_'.$cartId})) {
            unset(Context::getContext()->cookie->{'custompickup_id_store_'.$cartId});
            Context::getContext()->cookie->write();
            // $this->logDebug('clearCustomPickupConfig: cookie supprimé');
        }

        // $this->logDebug('clearCustomPickupConfig: configuration nettoyée pour cart_id='.$cartId);
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit_custompickup_config')) {
            $selectedStores = Tools::getValue('CUSTOMPICKUP_SELECTED_STORES', []);
            Configuration::updateValue('CUSTOMPICKUP_SELECTED_STORES', implode(',', $selectedStores));
            $output .= $this->displayConfirmation($this->l('Paramètres enregistrés.'));
        }

        if (Tools::isSubmit('custompickup_reindex_products')) {
            $mainCarrierName = Configuration::get('CUSTOMPICKUP_MAIN_CARRIER_NAME');
            $id_lang = (int)$this->context->language->id;
            $carriers = Carrier::getCarriers($id_lang, true, false, false);
            $mainCarrier = null;
            $retraitCarrierIds = [];
            foreach ($carriers as $carrier) {
                if (stripos($carrier['name'], 'Retrait') === 0) {
                    $retraitCarrierIds[] = $carrier['id_reference'];
                }
                if (trim($carrier['name']) === trim($mainCarrierName)) {
                    $mainCarrier = $carrier;
                }
            }
            if ($mainCarrier) {
                $mainCarrierIdRef = $mainCarrier['id_reference'];
                $products = Db::getInstance()->executeS('SELECT id_product FROM '._DB_PREFIX_.'product');
                foreach ($products as $product) {
                    // Supprime toutes les associations "Retrait"
                    if (!empty($retraitCarrierIds)) {
                        Db::getInstance()->delete('product_carrier', 'id_product='.(int)$product['id_product'].' AND id_carrier_reference IN ('.implode(',', $retraitCarrierIds).')');
                    }
                    // Ajoute l'association avec le transporteur principal si pas déjà présent
                    $exists = Db::getInstance()->getValue('SELECT 1 FROM '._DB_PREFIX_.'product_carrier WHERE id_product='.(int)$product['id_product'].' AND id_carrier_reference='.(int)$mainCarrierIdRef);
                    if (!$exists) {
                        Db::getInstance()->insert('product_carrier', [
                            'id_product' => (int)$product['id_product'],
                            'id_carrier_reference' => (int)$mainCarrierIdRef,
                            'id_shop' => (int)$this->context->shop->id,
                        ]);
                    }
                }
                $output .= $this->displayConfirmation('Réindexation terminée : tous les produits sont associés uniquement à "'.$mainCarrierName.'".');
            } else {
                $output .= $this->displayError('Transporteur principal "'.$mainCarrierName.'" introuvable.');
            }
        }

        $stores = $this->getAllStores();
        $selectedStores = explode(',', Configuration::get('CUSTOMPICKUP_SELECTED_STORES'));

        // Récupération des produits sans retrait
        $id_lang = (int)$this->context->language->id;
        $sql = "
            SELECT p.id_product, pl.name AS product_name
            FROM "._DB_PREFIX_."product p
            LEFT JOIN "._DB_PREFIX_."product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = $id_lang
            WHERE p.active = 1
            AND NOT EXISTS (
                SELECT 1
                FROM "._DB_PREFIX_."product_carrier ppc
                INNER JOIN "._DB_PREFIX_."carrier c ON c.id_reference = ppc.id_carrier_reference AND c.deleted = 0
                WHERE ppc.id_product = p.id_product
                AND (
                    c.name LIKE '%Retrait%'
                    OR c.name LIKE '%Economique%'
                    OR c.name LIKE '%Économique%'
                    OR c.name LIKE '%Standard%'
                )
            )
            GROUP BY p.id_product
        ";
        $products_no_pickup = Db::getInstance()->executeS($sql);

        // Correspondance magasins / transporteurs
        $selectedStoresInfo = [];
        if (!empty($selectedStores)) {
            $ids = implode(',', array_map('intval', $selectedStores));
            $sql = "SELECT s.id_store, sl.name AS store_name, s.city
                    FROM "._DB_PREFIX_."store s
                    LEFT JOIN "._DB_PREFIX_."store_lang sl ON s.id_store = sl.id_store AND sl.id_lang = $id_lang
                    WHERE s.id_store IN ($ids)";
            $storesInfo = Db::getInstance()->executeS($sql);

            // Récupère tous les transporteurs actifs
            $carriers = Carrier::getCarriers($id_lang, true, false, false);

            foreach ($storesInfo as $store) {
                $foundCarrier = null;
                foreach ($carriers as $carrier) {
                    if (
                        (stripos($carrier['name'], 'Retrait') !== false
                            || stripos($carrier['name'], 'Economique') !== false
                            || stripos($carrier['name'], 'Économique') !== false
                            || stripos($carrier['name'], 'Standard') !== false)
                        && stripos($carrier['name'], $store['city']) !== false
                    ) {
                        $foundCarrier = $carrier;
                        break;
                    }
                }
                $selectedStoresInfo[] = [
                    'id_store' => $store['id_store'],
                    'store_name' => $store['store_name'],
                    'city' => $store['city'],
                    'carrier' => $foundCarrier ? [
                        'id_carrier' => $foundCarrier['id_carrier'],
                        'name' => $foundCarrier['name']
                    ] : null
                ];
            }
        }
        $this->context->smarty->assign('selectedStoresInfo', $selectedStoresInfo);

        $this->context->smarty->assign([
            'stores' => $stores,
            'selectedStores' => $selectedStores,
            'products_no_pickup' => $products_no_pickup,
        ]);

        return $output . $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }

    private function getStoresWithCarriers()
    {
        $id_lang = (int)$this->context->language->id;
        $selectedIds = explode(',', Configuration::get('CUSTOMPICKUP_SELECTED_STORES'));

        if (empty($selectedIds)) {
            return [];
        }

        $ids = implode(',', array_map('intval', $selectedIds));

        $sql = "SELECT s.id_store, sl.name AS store_name, sl.address1, s.city, s.latitude, s.longitude, s.phone, sl.hours
            FROM "._DB_PREFIX_."store s
            LEFT JOIN "._DB_PREFIX_."store_lang sl ON s.id_store = sl.id_store AND sl.id_lang = $id_lang
            WHERE s.active = 1 AND s.id_store IN ($ids)";

        $stores = Db::getInstance()->executeS($sql);

        $carriers = Carrier::getCarriers($id_lang, true, false, false);

        $results = [];
        foreach ($stores as $store) {
            foreach ($carriers as $carrier) {
                if (
                    stripos($carrier['name'], 'Retrait') !== false &&
                    stripos($carrier['name'], $store['city']) !== false
                ) {
                    $results[] = [
                        'store' => $store,
                        'carrier' => $carrier
                    ];
                    break;
                }
            }
        }

        return $results;
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . '/views/css/pickup-list.css');
        if ($this->context->controller instanceof OrderController) {
            $this->context->controller->registerJavascript(
                'module-'.$this->name,
                'modules/'.$this->name.'/views/js/pickup.js',
                [
                    'position' => 'bottom',
                    'priority' => 300,
                ]
            );
        }

        //$this->context->controller->addJS($this->_path . '/views/js/pickup.js?v=' . time());

        // Ajout dynamique de l'ID du transporteur principal pour tous les templates
        $custompickup_main_carrier_id = $this->getMainCarrierId();

        $this->context->smarty->assign('custompickup_main_carrier_id', $custompickup_main_carrier_id);
    }

    public function hookDisplayCarrierExtraContent($params)
    {
        $custompickup_main_carrier_id = $this->getMainCarrierId();

        $this->context->smarty->assign('custompickup_main_carrier_id', $custompickup_main_carrier_id);

        if (!isset($params['carrier']) || !$custompickup_main_carrier_id) {
            return 'Paramètre carrier manquant';
        }

        $carrierId = (int)$params['carrier'];
        $carrier = new Carrier($carrierId);

        if (!Validate::isLoadedObject($carrier)) {
            return 'Transporteur invalide';
        }

        if ($carrier->id != $custompickup_main_carrier_id) {
            return '';
        }

        $cart = $this->context->cart;
        $deliveryAddress = new Address($cart->id_address_delivery);

        if (!Validate::isLoadedObject($deliveryAddress)) {
            return '';
        }

        $deliveryLatLng = $this->getCoordinatesFromAddress($deliveryAddress);

        $storesWithCarriers = $this->getStoresWithCarriers();

        if (empty($storesWithCarriers)) {
            return '';
        }

        $closestStore = null;
        $otherStores = [];

        if ($deliveryLatLng) {
            foreach ($storesWithCarriers as &$item) {
                if (isset($item['store']['latitude'], $item['store']['longitude'])) {
                    $distance = $this->getDistance(
                        $deliveryLatLng['lat'],
                        $deliveryLatLng['lon'],
                        $item['store']['latitude'],
                        $item['store']['longitude']
                    );
                    $item['distance_km'] = round($distance, 1);
                } else {
                    $item['distance_km'] = null;
                }
                $store_ckc = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ckcstockstores_stores` WHERE `name` LIKE "%' . strtoupper($item['store']['store_name']).'%"');

                if ($store_ckc) {

                    $hours = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'ckcstorespresentation WHERE id_multiboutique = '.(int)$store_ckc['id_store']);

                    if ($hours) {
                        $item['store']['hours'] = [];
                        for ($i = 0; $i <= 4; $i++) {
                            $dayKey = $i;
                            $item['store']['hours'][$dayKey] = str_replace('et', ' ', $hours['horaires_heures']);
                        }
                        $item['store']['hours'][5] = $hours['horaires_samedi'] != '' ? $hours['horaires_samedi'] : 'Fermé';
                        $item['store']['hours'] = json_encode($item['store']['hours']);
                    }

                    $item['store']['id_ckc_store'] = $store_ckc['id_store'];
                }
            }

            unset($item);

            usort($storesWithCarriers, function ($a, $b) {
                return ($a['distance_km'] ?? PHP_INT_MAX) <=> ($b['distance_km'] ?? PHP_INT_MAX);
            });

            $closestStore = array_shift($storesWithCarriers);
            $otherStores = $storesWithCarriers;
        }

        $allStores = array_merge([$closestStore], $otherStores);
        $safeStoresJson = json_encode(array_map(function ($item) {
            return [
                'store' => [
                    'id_store' => $item['store']['id_store'],
                    'store_name' => $item['store']['store_name'],
                    'address1' => $item['store']['address1'],
                    'city' => $item['store']['city'],
                    'latitude' => $item['store']['latitude'],
                    'longitude' => $item['store']['longitude'],
                    'phone' => $item['store']['phone'],
                    'hours' => $item['store']['hours'],
                ],
                'carrier' => [
                    'id' => $item['carrier']['id_carrier'],
                    'name' => $item['carrier']['name'],
                ],
            ];
        }, $allStores));

        $this->context->smarty->assign([
            'closest_store' => $closestStore,
            'other_stores' => $otherStores,
            'stores_json' => $safeStoresJson,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/pickup-point.tpl');
    }

    // Hook : force l'id_carrier du panier si un point de retrait a été sélectionné
    public function hookActionCarrierProcess($params)
    {
        $id_cart = (int) $params['cart']->id;
        $currentCarrier = (int) $params['cart']->id_carrier;
        $customCarrier = (int) Configuration::get('CUSTOMPICKUP_CARRIER_' . $id_cart);

        // $this->logDebug('hookActionCarrierProcess: id_cart='.$id_cart.' currentCarrier='.$currentCarrier.' customCarrier='.$customCarrier);

        // Si on a un transporteur custompickup configuré
        if ($customCarrier) {
            // Vérifie si le transporteur actuel est un retrait
            $isCurrentPickup = $this->isPickupCarrier($currentCarrier);
            // $this->logDebug('hookActionCarrierProcess: isCurrentPickup='.($isCurrentPickup ? 'true' : 'false'));

            // Si le transporteur actuel n'est pas un retrait ET qu'il n'est pas 0 ET qu'il n'est pas le transporteur de groupe (1638), on nettoie la configuration
            if (!$isCurrentPickup && $currentCarrier > 0 && $currentCarrier != 1682) {
                $this->clearCustomPickupConfig($id_cart);
                // $this->logDebug('hookActionCarrierProcess: configuration nettoyée car transporteur classique sélectionné');
                return; // On ne force plus le transporteur custompickup
            }

            // Force le transporteur custompickup spécifique
            $params['cart']->id_carrier = $customCarrier;
            $params['cart']->update();
            // $this->logDebug('hookActionCarrierProcess: transporteur forcé à '.$customCarrier.' (transporteur spécifique)');
        }
    }

    // Hook critique : force l'id_carrier de la commande juste avant insertion
    public function hookActionObjectOrderAddBefore($params)
    {
        if (isset($params['object']) && $params['object'] instanceof Order) {
            $id_cart = (int) $params['object']->id_cart;

            // Lire depuis Configuration ET directement depuis la BDD pour comparer
            $customCarrierConfig = (int) Configuration::get('CUSTOMPICKUP_CARRIER_' . $id_cart);
            $customCarrierDb = (int) Db::getInstance()->getValue(
                'SELECT value FROM '._DB_PREFIX_.'configuration 
                WHERE name = "CUSTOMPICKUP_CARRIER_' . (int)$id_cart . '"'
            );

            $orderCarrier = (int) $params['object']->id_carrier;

            // Récupérer aussi le transporteur du panier
            $cart = new Cart($id_cart);
            $cartCarrier = Validate::isLoadedObject($cart) ? (int) $cart->id_carrier : 0;

            // Récupérer l'id_store sauvegardé
            $savedStoreId = (int) Configuration::get('CUSTOMPICKUP_STORE_' . $id_cart);

            // Logs détaillés - DÉSACTIVÉ
            // $logMessage = sprintf(
            //     "[ORDER CREATE] id_cart=%d | order_id_carrier=%d | cart_id_carrier=%d | customCarrier_config=%d | customCarrier_db=%d | saved_store_id=%d",
            //     $id_cart,
            //     $orderCarrier,
            //     $cartCarrier,
            //     $customCarrierConfig,
            //     $customCarrierDb,
            //     $savedStoreId
            // );

            // Si les valeurs diffèrent, c'est suspect
            // if ($customCarrierConfig != $customCarrierDb) {
            //     $logMessage .= " | ⚠️ DIFFÉRENCE CONFIG/DBD DÉTECTÉE!";
            // }

            // Récupérer les noms des transporteurs pour le log
            // if ($customCarrierConfig > 0) {
            //     $carrierObj = new Carrier($customCarrierConfig);
            //     if (Validate::isLoadedObject($carrierObj)) {
            //         $logMessage .= " | customCarrier_name=\"" . trim($carrierObj->name) . "\"";
            //     }
            // }

            // if ($cartCarrier > 0) {
            //     $cartCarrierObj = new Carrier($cartCarrier);
            //     if (Validate::isLoadedObject($cartCarrierObj)) {
            //         $logMessage .= " | cartCarrier_name=\"" . trim($cartCarrierObj->name) . "\"";
            //     }
            // }

            // if ($orderCarrier > 0) {
            //     $orderCarrierObj = new Carrier($orderCarrier);
            //     if (Validate::isLoadedObject($orderCarrierObj)) {
            //         $logMessage .= " | orderCarrier_name=\"" . trim($orderCarrierObj->name) . "\"";
            //     }
            // }

            // file_put_contents(
            //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
            //     date('Y-m-d H:i:s') . ' ' . $logMessage . PHP_EOL,
            //     FILE_APPEND
            // );

            // Utiliser la valeur de la BDD (plus fiable que le cache)
            $customCarrier = $customCarrierDb > 0 ? $customCarrierDb : $customCarrierConfig;

            if ($customCarrier && $customCarrier > 0) {
                // Vérifier que ce n'est PAS le transporteur principal
                // if ($customCarrier == 1682) {
                //     file_put_contents(
                //         _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                //         date('Y-m-d H:i:s') . ' [ORDER CREATE] ⚠️ PROBLÈME: customCarrier est le transporteur principal (1682) au lieu d\'un transporteur spécifique!' . PHP_EOL,
                //         FILE_APPEND
                //     );
                // }

                // Si on a un transporteur custompickup configuré, on l'utilise
                $params['object']->id_carrier = $customCarrier;

                // file_put_contents(
                //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                //     date('Y-m-d H:i:s') . ' [ORDER CREATE] ✅ Transporteur FORCÉ à ' . $customCarrier . ' (depuis custompickup config)' . PHP_EOL,
                //     FILE_APPEND
                // );
            } else {
                // Sinon, on utilise le transporteur actuel du panier
                if (Validate::isLoadedObject($cart) && $cartCarrier > 0) {
                    // Vérifie si le transporteur du panier est le transporteur principal
                    if ($cartCarrier == 1682 || $orderCarrier == 1682) {
                        // file_put_contents(
                        //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                        //     date('Y-m-d H:i:s') . ' [ORDER CREATE] 🔍 FALLBACK: Pas de customCarrier configuré ET transporteur principal (1682) détecté. Recherche du magasin le plus proche...' . PHP_EOL,
                        //     FILE_APPEND
                        // );

                        // FALLBACK : Essayer de trouver le magasin le plus proche et utiliser son transporteur spécifique
                        // Entourer dans un try/catch pour éviter de bloquer la commande en cas d'erreur
                        try {
                            $deliveryAddress = new Address($cart->id_address_delivery);
                            if (Validate::isLoadedObject($deliveryAddress)) {
                                // Récupérer les magasins avec leurs transporteurs
                                $storesWithCarriers = $this->getStoresWithCarriers();

                                if (!empty($storesWithCarriers)) {
                                    // Calculer les distances
                                    $deliveryLatLng = $this->getCoordinatesFromAddress($deliveryAddress);

                                    if ($deliveryLatLng) {
                                        foreach ($storesWithCarriers as &$item) {
                                            if (isset($item['store']['latitude'], $item['store']['longitude'])) {
                                                $distance = $this->getDistance(
                                                    $deliveryLatLng['lat'],
                                                    $deliveryLatLng['lon'],
                                                    $item['store']['latitude'],
                                                    $item['store']['longitude']
                                                );
                                                $item['distance_km'] = round($distance, 1);
                                            } else {
                                                $item['distance_km'] = null;
                                            }
                                        }
                                        unset($item);

                                        // Trier par distance
                                        usort($storesWithCarriers, function ($a, $b) {
                                            return ($a['distance_km'] ?? PHP_INT_MAX) <=> ($b['distance_km'] ?? PHP_INT_MAX);
                                        });

                                        // Prendre le magasin le plus proche
                                        $closestStore = $storesWithCarriers[0];

                                        if (isset($closestStore['carrier']['id_carrier'])) {
                                            $closestCarrierId = (int) $closestStore['carrier']['id_carrier'];

                                            // Vérifier que ce n'est pas le transporteur principal
                                            if ($closestCarrierId != 1682) {
                                                // Utiliser le transporteur spécifique du magasin le plus proche
                                                $params['object']->id_carrier = $closestCarrierId;

                                                // Sauvegarder aussi pour référence future
                                                Configuration::updateValue('CUSTOMPICKUP_CARRIER_' . $id_cart, $closestCarrierId);
                                                if (isset($closestStore['store']['id_store'])) {
                                                    Configuration::updateValue('CUSTOMPICKUP_STORE_' . $id_cart, $closestStore['store']['id_store']);
                                                }

                                                // file_put_contents(
                                                //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                                                //     date('Y-m-d H:i:s') . ' [ORDER CREATE] ✅ FALLBACK: Transporteur automatique assigné: ' . $closestCarrierId . ' (magasin le plus proche: ' . $closestStore['store']['store_name'] . ' - ' . ($closestStore['distance_km'] ?? 'N/A') . ' km)' . PHP_EOL,
                                                //     FILE_APPEND
                                                // );

                                                // Sortir après avoir assigné
                                                // $finalCarrier = (int) $params['object']->id_carrier;
                                                // $finalCarrierObj = new Carrier($finalCarrier);
                                                // if (Validate::isLoadedObject($finalCarrierObj)) {
                                                //     file_put_contents(
                                                //         _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                                                //         date('Y-m-d H:i:s') . ' [ORDER CREATE] 📦 TRANSPORTEUR FINAL: ' . $finalCarrier . ' ("' . trim($finalCarrierObj->name) . '")' . PHP_EOL,
                                                //         FILE_APPEND
                                                //     );
                                                // }
                                                return;
                                            }
                                        }
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            // En cas d'erreur, on log l'erreur mais on ne bloque pas la commande
                            // file_put_contents(
                            //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                            //     date('Y-m-d H:i:s') . ' [ORDER CREATE] ❌ ERREUR FALLBACK: ' . $e->getMessage() . ' (Ligne: ' . $e->getLine() . ') - Utilisation du transporteur principal (1682) pour éviter de bloquer la commande' . PHP_EOL,
                            //     FILE_APPEND
                            // );
                        } catch (Throwable $e) {
                            // Capturer aussi les erreurs fatales (PHP 7+)
                            // file_put_contents(
                            //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                            //     date('Y-m-d H:i:s') . ' [ORDER CREATE] ❌ ERREUR FATALE FALLBACK: ' . $e->getMessage() . ' (Ligne: ' . $e->getLine() . ') - Utilisation du transporteur principal (1682) pour éviter de bloquer la commande' . PHP_EOL,
                            //     FILE_APPEND
                            // );
                        }

                        // Si on arrive ici, on n'a pas pu trouver de magasin proche ou une erreur s'est produite
                        // file_put_contents(
                        //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                        //     date('Y-m-d H:i:s') . ' [ORDER CREATE] ⚠️ FALLBACK: Impossible de trouver un magasin proche, utilisation du transporteur principal (1682)' . PHP_EOL,
                        //     FILE_APPEND
                        // );
                        // On laisse le transporteur principal (1682) être utilisé - la commande peut continuer normalement
                        return;
                    }

                    // Force le transporteur du panier seulement si ce n'est pas le transporteur principal
                    $params['object']->id_carrier = $cartCarrier;

                    // file_put_contents(
                    //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                    //     date('Y-m-d H:i:s') . ' [ORDER CREATE] ✅ Transporteur FORCÉ à ' . $cartCarrier . ' (depuis panier)' . PHP_EOL,
                    //     FILE_APPEND
                    // );
                } else {
                    // file_put_contents(
                    //     _PS_ROOT_DIR_.'/log_custompickup_order.txt',
                    //     date('Y-m-d H:i:s') . ' [ORDER CREATE] ⚠️ Aucun transporteur custompickup ET aucun transporteur dans le panier!' . PHP_EOL,
                    //     FILE_APPEND
                    // );
                }
            }

            // Log final du transporteur qui sera utilisé - DÉSACTIVÉ
            // $finalCarrier = (int) $params['object']->id_carrier;
            // $finalCarrierObj = new Carrier($finalCarrier);
            // if (Validate::isLoadedObject($finalCarrierObj)) {
            //     file_put_contents(
            //         _PS_ROOT_DIR_.'/log_custompickup_order.txt',
            //         date('Y-m-d H:i:s') . ' [ORDER CREATE] 📦 TRANSPORTEUR FINAL: ' . $finalCarrier . ' ("' . trim($finalCarrierObj->name) . '")' . PHP_EOL,
            //         FILE_APPEND
            //     );
            // }
        }
    }

    // Hook : gère le changement de transporteur
    public function hookActionCarrierUpdate($params)
    {
        if (!isset($params['cart']) || !isset($params['carrier'])) {
            return;
        }

        $id_cart = (int) $params['cart']->id;
        $id_carrier = (int) $params['carrier']->id;

        // $this->logDebug('hookActionCarrierUpdate: id_cart='.$id_cart.' id_carrier='.$id_carrier);

        // Vérifie si le nouveau transporteur est un transporteur de retrait
        $isPickup = $this->isPickupCarrier($id_carrier);
        // $this->logDebug('hookActionCarrierUpdate: isPickup='.($isPickup ? 'true' : 'false'));

        if (!$isPickup) {
            // Si ce n'est pas un transporteur de retrait, on nettoie la configuration
            $this->clearCustomPickupConfig($id_cart);
            // $this->logDebug('hookActionCarrierUpdate: configuration nettoyée car transporteur classique sélectionné');
        }
    }

    private function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Rayon Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    private function getCoordinatesFromAddress($address)
    {
        if (!Validate::isLoadedObject($address)) {
            return null;
        }

        // Utilisation uniquement du code postal et du pays pour la géolocalisation
        $fullAddress = trim($address->postcode . ' ' . Country::getNameById($this->context->language->id, $address->id_country));

        $fullAddressEncoded = urlencode($fullAddress);
        $url = "https://nominatim.openstreetmap.org/search?q=$fullAddressEncoded&format=json&limit=1";

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: CustomPickupModule/1.0\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
            return [
                'lat' => (float) $data[0]['lat'],
                'lon' => (float) $data[0]['lon'],
            ];
        }
        return null;
    }
}


