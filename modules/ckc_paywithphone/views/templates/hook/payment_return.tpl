{if $status == 'ok'}
	<p>
		{l s='Your order on %s is complete.' sprintf=[$shop_name] mod='ckc_paywithphone'}
	</p>
	{include file='module:ckc_paywithphone/views/templates/hook/_partials/payment_infos.tpl'}

	<p>
		{l s='Please specify us your order reference %s.' sprintf=[$reference] mod='ckc_paywithphone'}<br/>
		{l s='We\'ve also sent you this information by e-mail.' mod='ckc_paywithphone'}
	</p>
	<strong>{l s='Your order will be sent as soon as we receive payment.' mod='ckc_paywithphone'}</strong>
	<p>
		{l s='If you have questions, comments or concerns, please contact our expert customer support team.' mod='ckc_paywithphone'}
	</p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our expert customer support team.' mod='ckc_paywithphone'}
	</p>
{/if}
