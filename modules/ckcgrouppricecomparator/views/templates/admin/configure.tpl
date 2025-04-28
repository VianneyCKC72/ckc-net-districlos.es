{*
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='Comparateur des prix selon des groupes des clients' mod='ckcgrouppricecomparator'}</h3>
	<p> Lien vers la tâche cron à configurer des alertes mail :
		<strong>{$cron_url_track}</strong>
	</p>
</div>

<div class="panel">
	<h3><i class="icon icon-tags"></i> {l s='Tarifs pro des produits' mod='ckcgrouppricecomparator'}</h3>
	<table id="table_products_pro" class="table table-striped table-bordered" style="width:100%; margin-top: 15px;">
		<thead>
		<tr>
			<th >{l s='ID'}</th>
			<th>{l s='Nom' mod='ckcgrouppricecomparator'}</th>
			<th >{l s='Prix Pro HT' mod='ckcgrouppricecomparator'}</th>
            <th >{l s='Prix Standard HT' mod='ckcgrouppricecomparator'}</th>
            <th >{l s='Différence en HT' mod='ckcgrouppricecomparator'}</th>
		</tr>
		</thead>
		<tbody>
		{if $productsResult}
        {foreach $productsResult as $product}
			<tr>
				<td>{$product.id_product}</td>
				<td>{$product.name}</td>
				<td>{$product.pricePro} {$currency}</td>
                <td>{$product.priceStandard} {$currency}</td>
                <td>{$product.reduction} {$currency}</td>
			</tr>
        {/foreach}
		{else}
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
		{/if}
		</tbody>
	</table>
</div>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script>
$('#table_products_pro').DataTable({
		"bLengthChange": false, // Cache le menu de changement de nombre d'éléments affichés
		// language: {
		//     url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json',
		// },
		language: {
			paginate: {
				first: '&#8606;',
				last: '&#8608;',
				previous: '&#8592;',
				next: '&#8594;'
			}
		},
		"searching": true,
		responsive: true,
		pageLength: 50,
		"order": [[0, "desc"]], // Trie la première colonne (ID) en ordre décroissant
	});
</script>