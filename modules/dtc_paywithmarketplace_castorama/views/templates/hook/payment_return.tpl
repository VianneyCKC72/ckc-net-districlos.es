{if isset($dtc_order_reference) && $dtc_order_reference}
  <p>{l s='Votre commande %s a été enregistrée.' sprintf=[$dtc_order_reference] mod='dtc_paywithmarketplace_castorama'}</p>
{else}
  <p>{l s='Votre commande a été enregistrée.' mod='dtc_paywithmarketplace_castorama'}</p>
{/if}

