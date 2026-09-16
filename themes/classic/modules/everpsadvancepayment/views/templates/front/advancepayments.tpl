{*
* Project : everpsadvancepayment
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}

{extends file='page.tpl'}

{block name="page_content"}
{if $advancepaymentsList}
<div class="content">
	<div class="row">
		<p>{l s='Here is a list of all your advance payments invoices.' mod='everpsadvancepayment'}</p>
		<p>{l s='You can order whenever on our website.' mod='everpsadvancepayment'}</p>
        <p>{l s='Feel free to contact us by phone at' mod='everpsadvancepayment'} <a href="+tel{$shop_phone|escape:'htmlall':'UTF-8'}">{$shop_phone|escape:'htmlall':'UTF-8'}</a> {l s='or by email at' mod='everpsadvancepayment'} <a href="{$shop_email|escape:'htmlall':'UTF-8'}">{$shop_email|escape:'htmlall':'UTF-8'}</a></p>
	</div>
</div>
<div class="table-responsive" id="everquotations">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>{l s='Download link' mod='everpsadvancepayment'}</th>
                <th>{l s='Creation date' mod='everpsadvancepayment'}</th>
                <th style="text-align: right;">{l s='Total Tax incl.' mod='everpsadvancepayment'}</th>
                <th style="text-align: right;">{l s='Advance payment amount' mod='everpsadvancepayment'}</th>
                <th style="text-align: right;">{l s='Add to cart' mod='everpsadvancepayment'}</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$advancepaymentsList item=value}
        <tr>
            <td align="left" class="details-{$value.id_order|escape:'htmlall':'UTF-8'}" id="id_order">
                {$value.reference|escape:'htmlall':'UTF-8'}{$value.id_order|escape:'htmlall':'UTF-8'}
            </td>
            <td align="left" class="details-{$value.id_order|escape:'htmlall':'UTF-8'}" id="download_link">
                <a href="{$link->getModuleLink('everpsadvancepayment', 'advancepayments',['id_order'=>$value.id_order,'action'=>pdf])|escape:'htmlall':'UTF-8'}" class="btn btn-info renderPdf" role="button">
                    {$value.reference|escape:'htmlall':'UTF-8'}{$value.id_order|escape:'htmlall':'UTF-8'}.pdf
                    <i class="material-icons">picture_as_pdf</i>
                </a>
            </td>
            <td align="left" class="details-{$value.id_order|escape:'htmlall':'UTF-8'}" id="date_add">
                {$value.date_add|date_format:"%A %e %B %Y"|escape:'htmlall':'UTF-8'}

            </td>
            <td align="right" class="details-{$value.id_order|escape:'htmlall':'UTF-8'}" id="total_paid_tax_incl">
                {Tools::displayPrice($value.total_paid_tax_incl)|escape:'htmlall':'UTF-8'}
            </td>
            {if isset($advance_use_taxes) && $advance_use_taxes}
            <td align="right" class="details-{$value.id_order|escape:'htmlall':'UTF-8'}" id="advanced_tax_incl">
                {Tools::displayPrice($value.advanced_tax_incl)|escape:'htmlall':'UTF-8'}
            </td>
            {else}
            <td align="right" class="details-{$value.id_order|escape:'htmlall':'UTF-8'}" id="advanced_tax_excl">
                {Tools::displayPrice($value.advanced_tax_excl)|escape:'htmlall':'UTF-8'}
            </td>
            {/if}
            <td align="right" class="details-{$value.id_order|escape:'htmlall':'UTF-8'}" id="add_quote_to_cart">
                <a href="{$link->getModuleLink('everpsadvancepayment', 'advancepayments',['id_order'=>$value.id_order,'action'=>addtocart])|escape:'htmlall':'UTF-8'}" class="btn btn-success" role="button" data-idquote="{$value.id_order}">
                    {l s='Add to cart' mod='everpsadvancepayment'}
                    <i class="material-icons">shopping_basket</i>
                </a>
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
</div>
{else}
<div class="content">
    <div class="row">
        <p>{l s='There\'s no advance payments on your account. Feel free to ask some support !' mod='everpsadvancepayment'}</p>
    </div>
</div>
{/if}
<a href="{$link->getPageLink('my-account', true)|escape:'html'}" title="{l s='Back to my account' mod='everpsadvancepayment'}" class="account" rel="nofollow"><span>{l s='Back to my account' mod='everpsadvancepayment'}</span></a>
{/block}
