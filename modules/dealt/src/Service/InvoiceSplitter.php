<?php

namespace Dealt\Service;

use Order;

class InvoiceSplitter
{
    protected $order;
    protected $products10 = [];
    protected $products20 = [];
    protected $deliveryIncludedIn10 = false;

    protected $totals = [
        '10' => ['ht' => 0, 'tva' => 0, 'ttc' => 0],
        '20' => ['ht' => 0, 'tva' => 0, 'ttc' => 0],
    ];

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->split();
    }

    protected function split()
    {
        $products = $this->order->getProducts();

        foreach ($products as $product) {
            $rate = (int)$product['rate'];
            $line = [
                'name'       => $product['product_name'],
                'quantity'   => $product['product_quantity'],
                'unit_price' => (float)$product['unit_price_tax_excl'],
                'total_ht'   => (float)$product['total_price_tax_excl'],
                'total_ttc'  => (float)$product['total_price'],
            ];

            if ($rate === 10) {
                $this->products10[] = $line;
                $this->totals['10']['ht'] += $line['total_ht'];
                $this->totals['10']['ttc'] += $line['total_ttc'];
            } elseif ($rate === 20) {
                $this->products20[] = $line;
                $this->totals['20']['ht'] += $line['total_ht'];
                $this->totals['20']['ttc'] += $line['total_ttc'];
            }
        }

        if (!empty($this->products10)) {
            $this->deliveryIncludedIn10 = true;
            $this->addShippingTo10();
        }
    }

    protected function addShippingTo10()
    {
        $shippingExcl = (float)$this->order->total_shipping_tax_excl;
        $shippingIncl = (float)$this->order->total_shipping;

        $this->totals['10']['ht'] += $shippingExcl;
        $this->totals['10']['ttc'] += $shippingIncl;
    }

    public function getLines10()
    {
        return $this->products10;
    }

    public function getLines20()
    {
        return $this->products20;
    }

    public function getTotals()
    {
        // Calcule la TVA manquante
        $this->totals['10']['tva'] = $this->totals['10']['ttc'] - $this->totals['10']['ht'];
        $this->totals['20']['tva'] = $this->totals['20']['ttc'] - $this->totals['20']['ht'];

        return $this->totals;
    }

    public function hasMixedTva()
    {
        return !empty($this->products10) && !empty($this->products20);
    }

    public function isDeliveryIncludedIn10()
    {
        return $this->deliveryIncludedIn10;
    }
}
