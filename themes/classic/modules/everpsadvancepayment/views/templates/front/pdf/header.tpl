{**
 * Prestashop module : Everpsproductstoreinvoice
 *
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link https://www.team-ever.com
 *}

<table style="width: 100%">
<tr>
	<td style="width: 50%">
		{if $logo_path}
			<img src="{$logo_path|escape:'htmlall':'UTF-8'}" style="width:{$width_logo|escape:'htmlall':'UTF-8'}px; height:{$height_logo|escape:'htmlall':'UTF-8'}px;" />
		{/if}
	</td>
	<td style="width: 50%; text-align: right;">
		<table style="width: 100%">
			<tr>
				<td style="font-weight: bold; font-size: 14pt; color: #444; width: 100%;">{if isset($header)}{$header|escape:'html':'UTF-8'|upper}{/if}</td>
			</tr>
			<tr>
				<td style="font-size: 14pt; color: #9E9F9E">{$date|escape:'html':'UTF-8'}</td>
			</tr>
			<tr>
				<td style="font-size: 14pt; color: #9E9F9E">{$title|escape:'html':'UTF-8'} {$store_name|escape:'html':'UTF-8'}</td>
				<td style="font-size: 14pt; color: #9E9F9E">{$title|escape:'html':'UTF-8'} {$store_addr1|escape:'html':'UTF-8'}</td>
				<td style="font-size: 14pt; color: #9E9F9E">{$title|escape:'html':'UTF-8'} {$store_addr2|escape:'html':'UTF-8'}</td>
				<td style="font-size: 14pt; color: #9E9F9E">{$title|escape:'html':'UTF-8'} {$store_city|escape:'html':'UTF-8'}</td>
				<td style="font-size: 14pt; color: #9E9F9E">{$title|escape:'html':'UTF-8'} {$store_code|escape:'html':'UTF-8'}</td>
				<td style="font-size: 14pt; color: #9E9F9E">{$title|escape:'html':'UTF-8'} {$store_state|escape:'html':'UTF-8'}</td>
				<td style="font-size: 14pt; color: #9E9F9E">{$title|escape:'html':'UTF-8'} {$store_country|escape:'html':'UTF-8'}</td>
			</tr>
		</table>
	</td>
</tr>
</table>
