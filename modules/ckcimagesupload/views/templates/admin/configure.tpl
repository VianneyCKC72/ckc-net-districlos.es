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
	<form method="post" enctype="multipart/form-data">
		<h3 class="p-10">Ajouter des images</h3>
		<div class="form-group">
			<label for="module_images">Choisir des images :</label><br>
			<input type="file" name="module_images[]" id="module_images" multiple accept="image/jpeg,image/png,image/webp" />
			<p class="help-block">Max 500 Ko par image, max 8 images à la fois, formats autorisés : jpeg, jpg, png, webp</p>
		</div>
		<br />
		<button type="submit" name="submit_{$module_name}" class="btn btn-primary">importer</button>
	</form>
	{* {l s='This module will boost your sales!' mod='ckcimagesupload'} *}
</div>

<div class="panel">
	<form id="upload" method="post">
		<h3 class="p-10">Images actuelles</h3>

		{if $uploaded_images|count > 0}
 
			<table id="listTable"  class="table">
				<thead>
					<tr>
					<th scope="col">#</th>
					<th scope="col">miniature</th>
					<th scope="col">url</th>
					<th scope="col" class="text-right">actions</th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$uploaded_images item=image name=imgloop}
					<tr>
						<th scope="row">{$smarty.foreach.imgloop.iteration}</th>
						<td><img src="{$module_dir}uploads/{$image}" class="thumbnail_img" alt="{$image}"></td>
						<td>{$domain}{$module_dir}uploads/{$image}</td>
					
						<td class="">
							<div class="d-flex align-items-center justify-end gap-10">
								<span onclick="copyLink('{$domain}{$module_dir}uploads/{$image}')" class="copyLink material-icons">
									content_copy
								</span>

								<button type="submit" name="delete_one" value="{$image}" class="btn btn-sm btn-danger" style="margin-left:5px;">
									Supprimer
								</button>

								<input type="checkbox" name="images_to_delete[]" value="{$image}" id="img_{$image}">
							</div>
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
			<button type="submit" name="delete_selected" class="btn btn-danger" style="mt-10">
				Supprimer les images sélectionnées
			</button>
			<button type="submit" name="delete_all" class="btn btn-warning" style="margin-left: 10px;">
				Tout supprimer
			</button>
		{else}
			<p>Aucune image pour le moment.</p>
		{/if}




	
	</form>
</div>



<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
	$(document).ready(function () {
		$('#listTable').DataTable({
			searching: false,
			pageLength: 25,
			"bLengthChange": false, 
			language: {
				url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json',
			},
		});
	});
</script>
