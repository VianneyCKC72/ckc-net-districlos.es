{*
* 2007-2025 PrestaShop
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
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link href="https://fonts.cdnfonts.com/css/segoe-pro" rel="stylesheet">

{* <div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='CKC Stock Multiboutique' mod='ckcstockmultistore'}</h3>
	<p>
		<strong>{l s='Here is my new generic module!' mod='ckcstockmultistore'}</strong><br />
		{l s='Thanks to PrestaShop, now I have a great module.' mod='ckcstockmultistore'}<br />
		{l s='I can configure it using the following configuration form.' mod='ckcstockmultistore'}
	</p>
	<br />
	<p>
		{l s='This module will boost your sales!' mod='ckcstockmultistore'}
	</p>
</div> *}

<div class="panel">
	<h3 class="h3-ckctitle ckch3"><i class="icon icon-tags"></i> {l s='Toutes les boutiques' mod='ckcstockmultistore'}</h3>
    <table id="stores" class="table table-striped table-bordered" style="width:100%; font-weight:300 !important;">
        <thead>
        <tr>
            <th>ID</th>
            <th>{l s='Nom boutique' mod='ckcstockmultistore'}</th>
            <th>{l s='Actions' mod='ckcstockmultistore'}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $boutiques as $boutique}
            <tr>
                <th>{$boutique.id_store}</th>
				<th>{$boutique.name}</th>
                <th>
                    <a href="{$moduleAdminLink}&id_store={$boutique.id_store}" class="btn btn-primary">
                        <i class="icon-edit"></i> {l s='Modifier' mod='ckcstockmultistore'}
                    </a>
                </th>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>

<div class="panel">
    <h3 style="margin-top: 1px !important; border: none; margin-bottom: 5px; margin-left: -7px;" class="h3-ckctitle ckch3"><i class="icon icon-link"></i> {l s='Stock Produits Par Boutique' mod='ckcstockmultistore'}</h3>
    <div id="modulecontent" class="clearfix">
        <div id="ckcobfuscation-menu">
            <div class="col-lg-12">
                <div class="list-group" v-on:click.prevent>
				{foreach $boutiques as $boutique}
					<a href="#" class="list-group-item col-lg-2" v-bind:class="{ 'active': isActive({$boutique.id_store}) }" v-on:click="makeActive({$boutique.id_store})"><i class="material-icons">collections_bookmark</i> {l s={$boutique.name} mod='ckcstockmultistore'}</a>
				{/foreach}
                </div>
            </div>
        </div>

        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>


        {* list your admin tpl *}
		{foreach $boutiques as $boutique}
			<div id="{$boutique.id_store}" class="ckcobfuscation_menu addons-hide">
				{include file="./tabs/productTable.tpl"}
			</div>
		{/foreach}

    </div>
</div>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>

<script type="text/javascript">

	var currentPage = "{$currentPage|escape:'htmlall':'UTF-8'}";
	var moduleName = "{$module_name|escape:'htmlall':'UTF-8'}";
	var moduleAdminLink = "{$moduleAdminLink|escape:'htmlall':'UTF-8'}";
	var ps_version = "{$isPs17|escape:'htmlall':'UTF-8'}";

    $('#stores').DataTable({
        "bLengthChange": false, // Cache le menu de changement de nombre d'éléments affichés
        "language": {
            "url": "{$module_dir}views/js/datatables/lang/fr.json"
        },
		"oLanguage": {
			"sSearch": "Rechercher :"
		},
        "searching": true,
        responsive: true,
        pageLength: 10,
        "order": [[0, "desc"]], // Trie la première colonne (ID) en ordre décroissant
    });

</script>

