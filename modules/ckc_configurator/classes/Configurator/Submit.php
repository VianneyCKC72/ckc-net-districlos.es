<?php
namespace CkcConfigurator\Configurator;

use CkcConfigurator\Configurator\Session;

class Submit
{
    private $context;
    private $session;

    public function __construct($user_key)
    {
        $this->session = Session::find($user_key);
    }

    public function submit()
    {
        $this->context = \Context::getContext();
        $products = $this->getProducts();

        if(count($products)) {
            $this->createCart();
            if($this->addProducts($products)) {
                return true;
            }
        }

		return false;
    }


    /**
     * Création et gestion du panier
     */
    private function createCart()
    {
        // On vide le panier à l'avance
        if($this->context->cart->getOrderTotal() > 0) {
            foreach($this->context->cart->getProducts() as $product) {
                $this->context->cart->deleteProduct($product["id_product"]);
            }
            $this->context->cart->update();
        }

        if(!$this->context->cart->id) {
            if($this->context->cookie->id_guest) {
                $guest = new \Guest($this->context->cookie->id_guest);
                $this->context->cart->mobile_theme = $guest->mobile_theme;
            }
            $this->context->cart->add();
            if($this->context->cart->id) {
                $this->context->cookie->id_cart = (int)$this->context->cart->id;
            }
        }
    }

    /**
     * Ajout de produits au panier
     */
    private function addProducts($products)
    {
        foreach($products as $product) {
	        $this->context->cart->updateQty($product['quantity'], $product['id_product'], $product['id_product_attribute']);
            $this->context->cart->update();
        }

        if($this->context->cart->getOrdertotal() > 0) {
            return true;
        }

        return false;
    }

	/**
	 * Formattage des produits de la session
	 */
    public function getProducts()
    {
	    $products = [];

	    foreach($this->session->entries() as $entry) {
		    
		    if($this->session->installation == 'scellement-sur-platine' && $entry['id_product'] == 230) {
			    continue;
		    }

            // todo : possible de le dynamiser plus tard avec une recherche si existe..)
            if($entry['id_product'] == '1140') {
                $entry['id_product_attribute'] = 11768858;
            }

		    $products[] = [
			    "id_product" => $entry['id_product'],
			    "id_product_attribute" => $entry['id_product_attribute'],
			    "quantity" => $entry['quantity']
		    ];
	    }


        return $products;
    }
}
