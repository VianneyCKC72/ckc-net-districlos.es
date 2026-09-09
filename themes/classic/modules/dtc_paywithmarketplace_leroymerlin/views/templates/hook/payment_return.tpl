{if isset($dtc_order_reference) && $dtc_order_reference}
  <p>{l s='Votre commande %s a été enregistrée.' sprintf=[$dtc_order_reference] d='Shop.Theme.Customdtc'}</p>
{else}
  <p>{l s='Votre commande a été enregistrée.' d='Shop.Theme.Customdtc'}</p>
{/if}
