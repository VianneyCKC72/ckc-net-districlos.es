<?php
/**
 * Override DeliveryOptionsFinder : multiplicateur "reduc" x4 pour transporteurs 1772 et 1776
 *
 * @author    CKC-Net / custom
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DeliveryOptionsFinder extends DeliveryOptionsFinderCore
{
    /** IDs transporteurs avec reduc x4 au lieu de x2 */
    private const REDUC_MULTIPLIER_4_CARRIERS = [1772, 1776];

    /**
     * {@inheritdoc}
     */
    public function getDeliveryOptions()
    {
        $carriers_available = parent::getDeliveryOptions();

        $id_address = $this->getParentContext()->cart->id_address_delivery;
        $delivery_option_list = $this->getParentContext()->cart->getDeliveryOptionList();
        $include_taxes = !Product::getTaxCalculationMethod((int) $this->getParentContext()->cart->id_customer)
            && (int) Configuration::get('PS_TAX');

        foreach ($carriers_available as $id_carriers_list => $carrier) {
            $multiplier = $this->getReducMultiplierForKey($id_carriers_list);
            if ($multiplier === 4 && isset($delivery_option_list[$id_address][$id_carriers_list])) {
                $carriers_list = $delivery_option_list[$id_address][$id_carriers_list];
                $price = $include_taxes
                    ? $carriers_list['total_price_with_tax']
                    : $carriers_list['total_price_without_tax'];
                $carriers_available[$id_carriers_list]['reduc'] = $this->getParentPriceFormatter()->format(ceil($price * 4));
            }
        }

        return $carriers_available;
    }

    /**
     * Retourne 4 si la clé correspond à un transporteur 1772 ou 1776, sinon 2.
     *
     * @param string $id_carriers_list Clé type "1772" ou "12,13"
     * @return int
     */
    private function getReducMultiplierForKey($id_carriers_list)
    {
        $ids = array_map('intval', explode(',', $id_carriers_list));

        foreach ($ids as $id) {
            if (in_array($id, self::REDUC_MULTIPLIER_4_CARRIERS, true)) {
                return 4;
            }
        }

        return 2;
    }

    /**
     * Accès au context du parent (propriété private).
     *
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
     * Accès au priceFormatter du parent (propriété private).
     *
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
