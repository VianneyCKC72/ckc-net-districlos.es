<p>
  {l s='Votre commande sur %s est bien enregistrée.' sprintf=[$shop_name] d='Modules.Dtcwirepayment.Shop'}<br/>
  {l s='Merci d\'effectuer un virement avec les informations suivantes :' d='Modules.Dtcwirepayment.Shop'}
</p>
{include file='module:dtc_wirepayment/views/templates/hook/_partials/payment_infos.tpl'}

<p>
  {l s='Indiquez la référence de commande %s dans le libellé du virement.' sprintf=[$reference] d='Modules.Dtcwirepayment.Shop'}<br/>
  {l s='Ces informations vous ont également été envoyées par e-mail.' d='Modules.Dtcwirepayment.Shop'}
</p>
<strong>{l s='Votre commande sera traitée dès réception du paiement.' d='Modules.Dtcwirepayment.Shop'}</strong>
<p>
  {l s='Pour toute question, contactez notre [1]service client[/1].' d='Modules.Dtcwirepayment.Shop' sprintf=['[1]' => "<a href='{$contact_url}'>", '[/1]' => '</a>']}
</p>
