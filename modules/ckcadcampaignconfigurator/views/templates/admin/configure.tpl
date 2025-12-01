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

{* <div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='Configurateur des campagnes publicitaires' mod='ckcadcampaignconfigurator'}</h3>
	<p>
		<strong>{l s='Here is my new generic module!' mod='ckcadcampaignconfigurator'}</strong><br />
		{l s='Thanks to PrestaShop, now I have a great module.' mod='ckcadcampaignconfigurator'}<br />
		{l s='I can configure it using the following configuration form.' mod='ckcadcampaignconfigurator'}
	</p>
	<br />
	<p>
		{l s='This module will boost your sales!' mod='ckcadcampaignconfigurator'}
	</p>
</div> *}

<div class="panel">
	<h3><i class="icon icon-tags"></i> {l s='List of campaigns' mod='ckcadcampaignconfigurator'}</h3>
	<div>
	<table class="table">
	<thead>
	  <tr>
		<th class="fixed-width-xs text-left">ID</th>
		<th class="fixed-width-lg title_box text-left">Nom Campagne</th>
		<th class="fixed-width-md title_box text-left">Date Début</th>
		<th class="fixed-width-md text-left">Date Fin</th>
		<th class="fixed-width-md">Group Clients</th>
		<th class="fixed-width-xs center">Actions</th>
	  </tr>
	</thead>
	<tbody>
		{if $campaigns}
			{foreach $campaigns as $campaign }
				<tr>
				<td class="center">{$campaign.id}</td>
				<td class="text-left">{$campaign.title}</td>
				<td>{$campaign.date_start}</td>
				<td>{$campaign.date_end}</td>
				<td>{$campaign.id_client_group}</td>
				<td>
					<div class="btn-group-action">
						<div class="btn-group pull-right">
						<a href="{$current_url}&amp;edit_campaign&ID={$campaign.id}" class="btn btn-default">
							<i class="icon-pencil"></i> {l s='Edit' d='Admin.Actions'}
						</a>
						<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
							<span class="caret"></span>&nbsp;
						</button>
						<ul class="dropdown-menu">
						<li>
							<a href="{$current_url}&amp;edit_campaign&ID={$campaign.id}">
								<i class="icon-pencil"></i> {l s='Edit' d='Admin.Actions'}
							</a>
						</li>
						<li>
							<a href="{$current_url}&amp;copy_campaign&ID={$campaign.id}">
								<i class="material-icons">content_copy</i> {l s='Duplicate' d='Admin.Actions'}
							</a>
						</li>
						<li>
							<a href="{$current_url}&amp;delete_campaign&ID={$campaign.id}"
							   onclick="return confirm('{l s='Voulez-vous vraiment supprimer cette campagne ?' mod='ckcadcampaignconfigurator'}');">
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
				<td>No campaigns</td>
			</tr>
		{/if}
	</tbody>
  </table>
	</div>
	<div class="panel-footer">
	<a class="btn btn-default pull-right" href="{$current_url}&amp;create_new_campaign"><i class="process-icon-plus"></i> {l s='Add new campaign' mod='ckcadcampaignconfigurator'}</a>
  </div>
</div>
<script type="text/javascript">
    var moduleAdminLink = "{$current_url|escape:'htmlall':'UTF-8'}";
	var currentPage = "";

</script>