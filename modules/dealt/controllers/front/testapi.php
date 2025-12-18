<?php

use Dealt\Service\ApiService;

class DealtTestapiModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        require_once _PS_MODULE_DIR_ . 'dealt/src/Service/ApiService.php';
        $api = new \Dealt\Service\ApiService();

        $action = Tools::getValue('action');

        header('Content-Type: application/json');

        switch ($action) {
            case 'availability':
                $zipcode = Tools::getValue('zipcode');
                $offerId = '6846df4980a710f6d4b755c7';

                $offerIdReel = Tools::getValue('offer_id');

                if(!empty($offerIdReel)) {
                    $dealt_expert_requirements_description = Db::getInstance()->getValue('SELECT ds.expert_requirements_description FROM ' . _DB_PREFIX_ . 'dealt_service ds 
                    WHERE ds.reference LIKE "'.$offerIdReel.'"');

                    $storeId = '6846de0f80a710f6d4b75296';
                    $country = 'FR';
                    $storeIdReel = $this->getStoreId($zipcode);
                    if(!empty($storeIdReel)){
                        $result = $api->checkAvailability($zipcode, $offerIdReel, $country, $storeIdReel);
                        if ($result['available']) {
                            $result['dealt_expert_requirements_description'] = $dealt_expert_requirements_description;
                        }

                    } else {
                        $result = ['error' => 'Store ID pour le code postal n\'est pas disponible.'];
                    }

                } else {
                    $result = ['error' => 'Id de l\'offre est invalide.'];
                }
                break;

            case 'submit':
                $data = [
                    'address' => [
                        'country' => 'FR',
                        'zipCode' => '75001',
                        'city' => 'Paris',
                        'street1' => '10 rue de Rivoli',
                    ],
                    'customer' => [
                        'firstName' => 'Jean',
                        'lastName' => 'Dupont',
                        'emailAddress' => 'jean.dupont@example.com',
                        'phoneNumber' => '0611223344',
                        'customerProductPrice' => 249.90,
                        'customerServicePrice' => 59.90,
                        'purchaseOrder' => 'CMD123456',
                        'civility' => 'M',
                    ],
                    'webHookUrl' => 'https://vianney.cloture-discount.fr/module/dealt/webhook',
                    'extraDetails' => 'Portail lourd, accès jardin étroit',
                    'wishedDates' => [
                        [
                            'expectStart' => date(DATE_ATOM),
                            'expectEnd' => date(DATE_ATOM, strtotime('+7 days')),
                        ]
                    ],
                    'mediaFiles' => [],
                    'offerId' => '6846df4980a710f6d4b755c7',
                    'storeId' => '6846de0f80a710f6d4b75296'
                ];
                $result = $api->submitMission($data);
                break;

            default:
                $result = ['error' => 'Action non reconnue. Utilise ?action=availability ou ?action=submit'];
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }

    public function getStoreId($zipcode){
        if (!empty($zipcode)){
            $storeId = '';
            $stores = Db::getInstance()->executeS('SELECT zip_codes, name FROM ' . _DB_PREFIX_ . 'ckcstockstores_stores');

            $store_name = null;

            foreach ($stores as $store) {
                $zip_codes = explode(',', $store['zip_codes']);

                foreach ($zip_codes as $zip_code) {
                    $zip_code = trim($zip_code);

                    if ($zip_code === $zipcode || (strpos($zip_code, '*') !== false && preg_match('/^' . str_replace('*', '.*', $zip_code) . '$/', $zipcode))) {
                        $store_name = $store['name'];
                        break 2;
                    }
                }
            }

            if(!empty($store_name)){
                $stores2 = Db::getInstance()->executeS('SELECT offerId, departement FROM ' . _DB_PREFIX_ . 'ckcstorespresentation');
                foreach ($stores2 as $store) {
                    $storeName = strtolower(str_replace('ô', 'o', $store['departement']));
                    $store_name = strtolower($store_name);
                    if(strpos($store_name, $storeName) !== false){
                        $storeId = $store['offerId'];
                        break;
                    }
                }

                return $storeId;
            } else {
                return false;
            }
        }
        return false;
    }

}
