{*

* Project : everpsadvancepayment

* @author Team EVER

* @copyright Team EVER

* @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)

* @link https://www.team-ever.com

*}

{if isset($everap_content) && $everap_content}
	<div class="dis_flex main_flex_dir reassuranceEver">
		{*	{$everap_content nofilter}*}
		<p class="no_marg">
			<img src="/themes/theme1486/assets/img/acompteEver.png" alt="{l s='acompte' d='Shop.Theme.Custommodules'}">
			<span>{l s='Possibilité de payer un acompte de' d='Shop.Theme.Custommodules'}</span>
			<span class="priceAdd"></span>
			<span>{l s='et le reste avant la livraison' d='Shop.Theme.Custommodules'}</span>
		</p>
	</div>
{/if}
