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

<div class="panel">
	<h3><i class="icon icon-tags"></i> {l s='Magasins' mod='ckcstorespresentation'}</h3>
	<div>
	<table class="table">
	<thead>
	  <tr>
		<th class="fixed-width-xs text-left">ID</th>
		<th class="fixed-width-md title_box text-left">Departement</th>
		<th class="fixed-width-lg title_box text-left">Adresse</th>
		<th class="fixed-width-md text-left">Email</th>
		<th class="fixed-width-md text-left">Téléphone</th>
		<th class="fixed-width-md">Id magasin</th>
		<th class="fixed-width-xs text-right" style="padding-right:90px;">Actions</th>
	  </tr>
	</thead>
	<tbody>
		{if $stores}
			{foreach $stores as $store }
				<tr>
				<td class="text-left">{$store.id_ckcstorespresentation}</td>
				<td class="text-left">{$store.departement}</td>
				<td>{$store.adresse} {$store.code_postal} {$store.ville}</td>
				<td>{$store.email}</td>
				<td>{$store.telephone}</td>
				<td>{$store.id_multiboutique}</td>
				<td>
					<div class="btn-group-action">
						<div class="btn-group pull-right">
						<a href="{$current_url}&amp;edit_store&ID={$store.id_ckcstorespresentation}" class="btn btn-default">
							<i class="icon-pencil"></i> {l s='Edit' d='Admin.Actions'}
						</a>
						<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
							<span class="caret"></span>&nbsp;
						</button>
						<ul class="dropdown-menu">
						<li>
							<a href="{$current_url}&amp;edit_store&ID={$store.id_ckcstorespresentation}">
								<i class="icon-pencil"></i> {l s='Edit' d='Admin.Actions'}
							</a>
						</li>
						<li>
							<a href="{$current_url}&amp;delete_store&ID={$store.id_ckcstorespresentation}"
								onclick="return confirm('{l s='Do you really want to delete this information?' d='Modules.Facetedsearch.Admin'}');">
								<i class="icon-trash"></i> {l s='Delete' d='Admin.Actions'}
							</a>
						</li>
						</ul>
						</div>
					</div>
				</td>
				</tr>
			{/foreach}
		{else}
			<tr>
				<td>
					Aucune présentation des magasins est créée.
				</td>
			</tr>
		{/if}
	</tbody>
  </table>
	</div>
	<div class="panel-footer">
	<a class="btn btn-default pull-right" href="{$current_url}&amp;create_store"><i class="process-icon-plus"></i> {l s='Ajouter une présentation' mod='ckcstorespresentation'}</a>
  </div>
</div>
