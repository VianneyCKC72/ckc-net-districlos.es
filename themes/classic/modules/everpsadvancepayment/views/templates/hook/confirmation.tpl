{*
* Project : everpsadvancepayment
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}

<section>
  <p>{l s='Your order has been saved, thanks for your confidence !' mod='everpsadvancepayment'}</p>
  <p>{$content nofilter}</p>
  {if !$advance_use_taxes}
  <p>{l s='Amount due is ' mod='everpsadvancepayment'} {$advanced_tax_excl|escape:'htmlall':'UTF-8'}</p>
  {else}
  <p>{l s='Amount due is ' mod='everpsadvancepayment'} {$advanced_tax_incl|escape:'htmlall':'UTF-8'}</p>
  {/if}
  <p>{l s='An email has been sent with this information.' mod='everpsadvancepayment'}</p>
  <p>{l s='You can contact us at ' mod='everpsadvancepayment'} <a href="mailto:{$shop_email|escape:'htmlall':'UTF-8'}"> {$shop_email|escape:'htmlall':'UTF-8'}</a></p>
  <p><a href="{$link->getModuleLink('everpsadvancepayment', 'advancepayments',['id_order'=>$id_order,'action'=>pdf])|escape:'htmlall':'UTF-8'}" class="btn btn-info" target="_blank">{l s='You can download your PDF file by clicking on this link' mod='everpsadvancepayment'}</a></p>
</section>
