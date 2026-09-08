{*
* Project : everpsadvancepayment
* @author Team EVER
* @copyright Team EVER
* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
* @link https://www.team-ever.com
*}
<div class="row">
	<div class="col-xs-12 col-md-12">
		<p class="payment_module" id="everpsadvancepayment_payment_button">
			{if $cart->getOrderTotal() < 2}
				<a href="">
					<img src="{$domain|cat:$payment_button|escape:'html':'UTF-8'}" alt="{l s='Pay with my payment module' mod='everpsadvancepayment'}" />
					{l s='Minimum amount required in order to pay with my payment module:' mod='everpsadvancepayment'} {convertPrice price=2}
				</a>
			{else}
				<a href="{$link->getModuleLink('everpsadvancepayment', 'validation', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with advanced payment' mod='everpsadvancepayment'}">
					<img src="{$advanced_logo|escape:'htmlall':'UTF-8'}" alt="{l s='Pay with my payment module' mod='everpsadvancepayment'}" width="32" height="32" />
					{l s='Pay with advanced payment' mod='everpsadvancepayment'}
				</a>
			{/if}
		</p>
	</div>
</div>
