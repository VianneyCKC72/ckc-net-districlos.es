<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Magasin de référence lié à une commande (colonne orders.id_store)
 * + résolution des coordonnées de virement bancaire par magasin.
 */
class OrderStoreHelper
{
    /**
     * Magasin + téléphone présentation en une seule requête.
     *
     * @return array{id_store?: int, name?: string, iban?: string, bic?: string, presentation_phone?: string}|null
     */
    public static function getStoreForOrder(int $idOrder): ?array
    {
        if ($idOrder <= 0) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT o.`id_store`, s.*, p.`telephone` AS `presentation_phone`
            FROM `' . _DB_PREFIX_ . 'orders` o
            LEFT JOIN `' . _DB_PREFIX_ . 'ckcstockstores_stores` s ON s.`id_store` = o.`id_store`
            LEFT JOIN `' . _DB_PREFIX_ . 'ckcstorespresentation` p ON p.`id_multiboutique` = o.`id_store`
            WHERE o.`id_order` = ' . $idOrder
        );

        return $row ?: null;
    }

    /**
     * Résout le magasin à partir du panier (transporteur puis code postal).
     */
    public static function resolveStoreFromCart(Cart $cart): ?array
    {
        $address = new Address((int) $cart->id_address_delivery);
        if (!Validate::isLoadedObject($address)) {
            return null;
        }

        $stores = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ckcstockstores_stores`');
        if (!$stores) {
            return null;
        }

        $idStore = null;
        $idCarrier = (int) $cart->id_carrier;
        if ($idCarrier > 0) {
            $carrier = new Carrier($idCarrier);
            $carrierRef = (int) $carrier->id_reference;
            foreach ($stores as $store) {
                $carrierIds = array_filter(array_map('intval', array_map('trim', explode(',', (string) ($store['carrier'] ?? '')))));
                if (in_array($carrierRef, $carrierIds, true)) {
                    $idStore = (int) $store['id_store'];
                    break;
                }
            }
        }

        if (!$idStore) {
            $zip = (string) $address->postcode;
            $countryIso = (string) (Country::getIsoById((int) $address->id_country) ?: 'FR');
            if (class_exists('Ckcstockmultistore') && method_exists('Ckcstockmultistore', 'matchStoreByZip')) {
                $country = method_exists('Ckcstockmultistore', 'countryFromZip')
                    ? Ckcstockmultistore::countryFromZip($zip, $countryIso)
                    : $countryIso;
                $match = Ckcstockmultistore::matchStoreByZip($stores, $zip, $country);
                if ($match) {
                    $idStore = (int) $match['id_store'];
                }
            } else {
                foreach ($stores as $store) {
                    foreach (explode(',', (string) ($store['zip_codes'] ?? '')) as $pattern) {
                        $pattern = trim($pattern);
                        if ($pattern === '') {
                            continue;
                        }
                        if ($pattern === $zip
                            || (strpos($pattern, '*') !== false && preg_match('/^' . str_replace('*', '.*', $pattern) . '$/', $zip))
                        ) {
                            $idStore = (int) $store['id_store'];
                            break 2;
                        }
                    }
                }
            }
        }

        if (!$idStore) {
            return null;
        }

        foreach ($stores as $store) {
            if ((int) $store['id_store'] === $idStore) {
                return $store;
            }
        }

        return null;
    }

    /**
     * Coordonnées de virement pour une commande (magasin lié ou fallback config).
     *
     * @param array{owner?: string, details?: string, address?: string} $fallback
     *
     * @return array{owner: string, details: string, address: string, iban: string, bic: string}
     */
    public static function getWireBankDetailsForOrder(int $idOrder, array $fallback = []): array
    {
        $store = self::getStoreForOrder($idOrder);
        if ($store && empty($store['iban'])) {
            $store = null;
        }

        return self::formatWireBankDetails($store, $fallback);
    }

    /**
     * Coordonnées de virement pour un panier (avant création commande).
     *
     * @param array{owner?: string, details?: string, address?: string} $fallback
     *
     * @return array{owner: string, details: string, address: string, iban: string, bic: string}
     */
    public static function getWireBankDetailsForCart(Cart $cart, array $fallback = []): array
    {
        $store = self::resolveStoreFromCart($cart);
        if ($store && empty($store['iban'])) {
            $store = null;
        }

        return self::formatWireBankDetails($store, $fallback);
    }

    /**
     * Applique le RIB magasin aux variables d'e-mail bankwire_*.
     *
     * @param array<string, mixed> $mailVars
     *
     * @return array<string, mixed>
     */
    public static function applyWireDetailsToMailVars(array $mailVars, int $idOrder, array $fallback = []): array
    {
        if ($idOrder <= 0 || !isset($mailVars['{bankwire_details}'])) {
            return $mailVars;
        }

        $details = self::getWireBankDetailsForOrder($idOrder, $fallback);
        if ($details['iban'] === '') {
            // Magasin sans IBAN : tenter le remplacement de placeholders sur le texte existant
            if (strpos((string) $mailVars['{bankwire_details}'], '{iban}') !== false
                || strpos((string) $mailVars['{bankwire_details}'], '{bic}') !== false
            ) {
                return $mailVars;
            }

            return $mailVars;
        }

        $mailVars['{bankwire_owner}'] = $details['owner'];
        $mailVars['{bankwire_details}'] = Tools::nl2br($details['details']);
        if (isset($mailVars['{bankwire_address}']) && $details['address'] !== '') {
            $mailVars['{bankwire_address}'] = Tools::nl2br($details['address']);
        }

        return $mailVars;
    }

    /**
     * @param array<string, mixed>|null $store
     * @param array{owner?: string, details?: string, address?: string} $fallback
     *
     * @return array{owner: string, details: string, address: string, iban: string, bic: string}
     */
    public static function formatWireBankDetails(?array $store, array $fallback = []): array
    {
        $owner = trim((string) ($fallback['owner'] ?? ''));
        $detailsTemplate = (string) ($fallback['details'] ?? '');
        $address = (string) ($fallback['address'] ?? '');

        if ($store && !empty($store['iban'])) {
            $iban = (string) $store['iban'];
            $bic = (string) ($store['bic'] ?? '');
            if ($detailsTemplate !== ''
                && (strpos($detailsTemplate, '{iban}') !== false || strpos($detailsTemplate, '{bic}') !== false)
            ) {
                $details = str_replace(['{iban}', '{bic}'], [$iban, $bic], $detailsTemplate);
            } else {
                $details = trim($iban . "\n" . $bic);
            }
            if (!empty($store['name'])) {
                $owner = trim((string) $store['name']);
            }

            return [
                'owner' => $owner !== '' ? $owner : '___________',
                'details' => $details !== '' ? $details : '___________',
                'address' => $address !== '' ? $address : '___________',
                'iban' => $iban,
                'bic' => $bic,
            ];
        }

        return [
            'owner' => $owner !== '' ? $owner : '___________',
            'details' => $detailsTemplate !== '' ? $detailsTemplate : '___________',
            'address' => $address !== '' ? $address : '___________',
            'iban' => '',
            'bic' => '',
        ];
    }
}
