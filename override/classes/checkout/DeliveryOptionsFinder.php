<?php
/**
 * Override DeliveryOptionsFinder : badge checkout + placeholders délai + prix barré id_reference 1096
 *
 * @author    Districlos
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/DeliveryDelayHelper.php';

class DeliveryOptionsFinder extends DeliveryOptionsFinderCore
{
    /** Transporteur dont le prix barré = max des autres options (sinon ×2 core) */
    private const REDUC_COMPARE_ID_REFERENCE = 1096;

    /**
     * {@inheritdoc}
     */
    public function getDeliveryOptions()
    {
        $carriers_available = parent::getDeliveryOptions();
        if (!$carriers_available) {
            return $carriers_available;
        }

        $context = $this->getParentContext();
        $idLang = (int) $context->language->id;
        $idReferences = [];
        $key1096 = null;

        foreach ($carriers_available as $id_carriers_list => $carrier) {
            if (!empty($carrier['id_reference'])) {
                $idRef = (int) $carrier['id_reference'];
                $idReferences[] = $idRef;
                if ($idRef === self::REDUC_COMPARE_ID_REFERENCE) {
                    $key1096 = $id_carriers_list;
                }
            }

            if (!empty($carrier['delay'])) {
                $carriers_available[$id_carriers_list]['delay'] = DeliveryDelayHelper::replace($carrier['delay']);
            }
        }

        // Badges : 1 requête pour tous les transporteurs
        $module = Module::getInstanceByName('dtc_carrier_badge');
        if ($module && $module->active && $idReferences) {
            $badges = $module->getBadges($idReferences, $idLang);
            foreach ($carriers_available as $id_carriers_list => $carrier) {
                $idRef = !empty($carrier['id_reference']) ? (int) $carrier['id_reference'] : 0;
                if ($idRef && !empty($badges[$idRef])) {
                    $carriers_available[$id_carriers_list]['badge'] = $badges[$idRef];
                }
            }
        }

        // Prix barré 1096 — cache Cart déjà rempli par parent (pas de recalcul)
        // Même en gratuit : barré = max des autres options si > 0
        if ($key1096 === null) {
            return $carriers_available;
        }

        $id_address = (int) $context->cart->id_address_delivery;
        $delivery_option_list = $context->cart->getDeliveryOptionList();
        $include_taxes = !Product::getTaxCalculationMethod((int) $context->cart->id_customer)
            && (int) Configuration::get('PS_TAX');

        $maxPrice = 0.0;
        $maxOther = 0.0;
        $price1096 = null;

        foreach ($carriers_available as $id_carriers_list => $carrier) {
            if (!isset($delivery_option_list[$id_address][$id_carriers_list])) {
                continue;
            }
            $list = $delivery_option_list[$id_address][$id_carriers_list];
            $price = $include_taxes
                ? (float) $list['total_price_with_tax']
                : (float) $list['total_price_without_tax'];

            if ($price > $maxPrice) {
                $maxPrice = $price;
            }
            if ($id_carriers_list === $key1096) {
                $price1096 = $price;
            } elseif ($price > $maxOther) {
                $maxOther = $price;
            }
        }

        $displayPrice = isset($carriers_available[$key1096]['price'])
            ? trim((string) $carriers_available[$key1096]['price'])
            : '';
        $isDisplayedFree = (bool) preg_match('/^(free|gratuit)$/iu', $displayPrice);

        $reducAmount = null;
        if ($isDisplayedFree && $maxOther > 0) {
            // Gratuit : toujours le max des autres transporteurs
            $reducAmount = $maxOther;
        } elseif ($price1096 !== null && $maxPrice > $price1096) {
            // Payant : max de la liste s'il est plus cher que 1096
            $reducAmount = $maxPrice;
        }

        if ($reducAmount !== null) {
            $carriers_available[$key1096]['reduc'] = $this->getParentPriceFormatter()->format(ceil($reducAmount));
        }

        return $carriers_available;
    }

    /**
     * @return Context
     */
    private function getParentContext()
    {
        $r = new ReflectionClass(DeliveryOptionsFinderCore::class);
        $p = $r->getProperty('context');
        $p->setAccessible(true);

        return $p->getValue($this);
    }

    /**
     * @return \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter
     */
    private function getParentPriceFormatter()
    {
        $r = new ReflectionClass(DeliveryOptionsFinderCore::class);
        $p = $r->getProperty('priceFormatter');
        $p->setAccessible(true);

        return $p->getValue($this);
    }
}
