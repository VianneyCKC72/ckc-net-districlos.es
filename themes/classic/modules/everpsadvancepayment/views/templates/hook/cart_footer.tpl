{*
* Project : everpsadvancepayment
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}
{if Context::getContext()->shop->id != 2}
<div class="col-12" style="margin-top: 20px;">
    {l s='Vous pouvez payer en utilisant le paiement anticipé. Total dû est :' mod='everpsadvancepayment'} {if isset($advance_use_taxes) && $advance_use_taxes}{$advanced_amount_wt|escape:'htmlall':'UTF-8'}{else}{$advanced_amount|escape:'htmlall':'UTF-8'}{/if}
</div>
{/if}