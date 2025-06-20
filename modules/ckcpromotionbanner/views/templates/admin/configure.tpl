{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<div class="panel">
	<h2><i class="icon icon-asterisk"></i></i> {l s='Promotion banner' mod='ckcpromotionbanner'} </h2>
	<p>
		<strong>{l s='Closable promotion bar' mod='ckcpromotionbanner'}</strong><br />
		{l s='at the bottom / top of the page (with customizable message)' mod='ckcpromotionbanner'}<br />
		{l s='With or without timer' mod='ckcpromotionbanner'} <br />
	</p>
	<br />

</div>
<div class="panel panel-promobanner">
	<h3 style="margin-top: 1px !important; border: none; margin-bottom: 5px; margin-left: -7px; background: none;" class="h3-ckctitle ckch3"><i class="icon icon-link"></i> {l s='Add text(s) for promotion' mod='ckcpromotionbanner'}</h3>
	<p class="help-block">{l s='If you have several promotional texts to show, you can add as many as necessary here (if only one too)' mod='ckcpromotionbanner'}</p>
	<div class="mt-5 id-select-container">
		<form id="id_promotexts" class="context-form" action="{$moduleAdminLink}" method="post" enctype="multipart/form-data" novalidate>
			<h4>{l s='Put a promo text to add :' mod='ckcpromotionbanner'}</h4>
			<div class="input-group mb-3">
				<input type="text" id="idInput" class="form-control" placeholder="{l s='Put a promo text to add' mod='ckcpromotionbanner'}">
				<div class="btn btn-primary" id="addIdButton">+</div>
			</div>
			<div id="selectedIds"></div>
			<input type="hidden" id="idsField" name="id_promotexts" form="id_promotexts">
			<input class="btn btn-success mt-3 saveExceptions" type="submit" value="{l s='Save the list of promo texts' mod='ckcpromotionbanner'}" form="id_promotexts">
		</form>
	</div>
</div>

<div class="panel panel-promobanner">
	<h3 style="margin-top: 1px !important; border: none; margin-bottom: 5px; margin-left: -7px; background: none;" class="h3-ckctitle ckch3"><i class="icon icon-link"></i> {l s='Ajouter du/des texte(s) pour la promotion PRO' mod='ckcpromotionbanner'}</h3>
	<p class="help-block">{l s='If you have several promotional texts to show, you can add as many as necessary here (if only one too)' mod='ckcpromotionbanner'}</p>
	<div class="mt-5 id-select-container">
		<form id="id_promotextsPro" class="context-form" action="{$moduleAdminLink}" method="post" enctype="multipart/form-data" novalidate>
			<h4>{l s='Mettez un texte promotionnel PRO à ajouter :' mod='ckcpromotionbanner'}</h4>
			<div class="input-group mb-3">
				<input type="text" id="idInputPro" class="form-control" placeholder="{l s='Put a promo text to add' mod='ckcpromotionbanner'}">
				<div class="btn btn-primary" id="addIdButtonPro">+</div>
			</div>
			<div id="selectedIdsPro"></div>
			<input type="hidden" id="idsFieldPro" name="id_promotextsPro" form="id_promotextsPro">
			<input class="btn btn-success mt-3 saveExceptions" type="submit" value="{l s='Save the list of promo texts' mod='ckcpromotionbanner'}" form="id_promotextsPro">
		</form>
	</div>
</div>

<script type="text/javascript">
	var moduleAdminLink = "{$moduleAdminLink|escape:'htmlall':'UTF-8'}";
	var promotexts = `{$promotexts}`;
	var promotextsPro = `{$promotextsPro}`;

	let selectedPromotexts = promotexts.split(",,");
	selectedPromotexts.forEach(function callback (text, index) {
		if(text != ""){
			elementTag = '<span class="tag" data-id="'+index+'">'+text+' <span class="remove">✖</span></span>'
			$("#selectedIds").append(elementTag);
		}
	});

	$("#idsField").val(promotexts)


	$(document).ready(function() {

		$("#addIdButton").click(function(e) {
			e.preventDefault();
			let newId = $("#idInput").val().trim();
			if (newId && selectedPromotexts.indexOf(newId) < 0) {
				if(newId != ""){
					selectedPromotexts.push(newId);
					updateIdsDisplay();
					$("#idInput").val("");
				}
			}
		});

		$(document).on("click", ".remove", function() {
			let index = $(this).parent().attr("data-id");
			selectedPromotexts = selectedPromotexts.filter(text => text !== selectedPromotexts[index]);
			updateIdsDisplay();
		});

		function updateIdsDisplay() {
			$("#selectedIds").empty();
			selectedPromotexts.forEach(function callback (text, index) {
				if(text != ""){
					elementTag = '<span class="tag" data-id="'+index+'">'+text+' <span class="remove">✖</span></span>'
					$("#selectedIds").append(elementTag);
				}
			});
			$("#idsField").val(selectedPromotexts.join(",,"));
		}

		// Enregistrement textes promo Pro

		let selectedPromotextsPro = promotextsPro.split(",,");
		selectedPromotextsPro.forEach(function callback (text, index) {
			if(text != ""){
				elementTagPro = '<span class="tag" data-id="'+index+'">'+text+' <span class="removePro">✖</span></span>'
				$("#selectedIdsPro").append(elementTagPro);
			}
		});

		$("#idsFieldPro").val(promotextsPro)

		$("#addIdButtonPro").click(function(e) {
			e.preventDefault();
			let newIdPro = $("#idInputPro").val().trim();
			if (newIdPro && selectedPromotextsPro.indexOf(newIdPro) < 0) {
				if(newIdPro != ""){
					selectedPromotextsPro.push(newIdPro);
					updateIdsDisplayPro();
					$("#idInputPro").val("");
				}
			}
		});

		$(document).on("click", ".removePro", function() {
			let indexPro = $(this).parent().attr("data-id");
			selectedPromotextsPro = selectedPromotextsPro.filter(text => text !== selectedPromotextsPro[indexPro]);
			updateIdsDisplayPro();
		});

		function updateIdsDisplayPro() {
			$("#selectedIdsPro").empty();
			selectedPromotextsPro.forEach(function callback (text, index) {
				if(text != ""){
					elementTagPro = '<span class="tag" data-id="'+index+'">'+text+' <span class="removePro">✖</span></span>'
					$("#selectedIdsPro").append(elementTagPro);
				}
			});
			$("#idsFieldPro").val(selectedPromotextsPro.join(",,"));
		}
	});

</script>