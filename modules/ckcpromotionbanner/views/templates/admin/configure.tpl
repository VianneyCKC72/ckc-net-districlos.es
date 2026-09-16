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
		<small>{l s='Current shop' mod='ckcpromotionbanner'}: #{$current_shop_id|intval}</small>
	</p>
</div>

{function name="ckcpromobarre_lang_widget" group="" texts_by_lang=[] languages=[] default_lang_id=0 employee_lang_id=0 input_id_prefix="" container_id_prefix=""}
	<ul class="nav nav-tabs ckcpromobarre-lang-tabs" role="tablist" data-group="{$group}">
		{foreach from=$languages item=lang}
			<li class="nav-item" style="display:inline-block;">
				<a class="nav-link ckcpromobarre-lang-tab{if $lang.id_lang == $employee_lang_id} active{/if}"
				   data-group="{$group}"
				   data-lang="{$lang.id_lang}"
				   href="#"
				   role="tab">
					{$lang.name|escape:'htmlall':'UTF-8'} ({$lang.iso_code|escape:'htmlall':'UTF-8'})
				</a>
			</li>
		{/foreach}
	</ul>
	<div class="tab-content ckcpromobarre-tab-content mt-3" data-group="{$group}">
		{foreach from=$languages item=lang}
			<div class="ckcpromobarre-lang-pane{if $lang.id_lang == $employee_lang_id} active{/if}"
				 data-group="{$group}"
				 data-lang="{$lang.id_lang}"
				 {if $lang.id_lang != $employee_lang_id}style="display:none;"{/if}>
				<h4>
					{l s='Put a promo text to add (language: %s)' sprintf=[$lang.name] mod='ckcpromotionbanner'}
					{if $lang.id_lang != $default_lang_id}
						<button type="button"
								class="btn btn-default btn-xs ckcpromobarre-copy-default ml-2"
								data-group="{$group}"
								data-lang="{$lang.id_lang}"
								data-default-lang="{$default_lang_id}">
							{l s='Copy from default language' mod='ckcpromotionbanner'}
						</button>
					{/if}
				</h4>
				<div class="input-group mb-3">
					<input type="text"
						   id="{$input_id_prefix}-{$lang.id_lang}"
						   class="form-control ckcpromobarre-input"
						   data-group="{$group}"
						   data-lang="{$lang.id_lang}"
						   placeholder="{l s='Put a promo text to add' mod='ckcpromotionbanner'}">
					<div class="btn btn-primary ckcpromobarre-add"
						 data-group="{$group}"
						 data-lang="{$lang.id_lang}">+</div>
				</div>
				<div id="{$container_id_prefix}-{$lang.id_lang}"
					 class="ckcpromobarre-selected"
					 data-group="{$group}"
					 data-lang="{$lang.id_lang}"></div>
			</div>
		{/foreach}
	</div>
{/function}

<div class="panel panel-promobanner">
	<h3 style="margin-top: 1px !important; border: none; margin-bottom: 5px; margin-left: -7px; background: none;" class="h3-ckctitle ckch3"><i class="icon icon-link"></i> {l s='Add text(s) for promotion' mod='ckcpromotionbanner'}</h3>
	<p class="help-block">
		{l s='Configure each language separately. Type a text then click + (or Save — pending text is kept).' mod='ckcpromotionbanner'}
		{if $ckctranslate_ready}
			{l s='Or use “Translate with ckctranslate” to fill the target language from the source language automatically.' mod='ckcpromotionbanner'}
		{/if}
	</p>
	<div class="mt-5 id-select-container">
		<form id="id_promotexts" class="context-form" action="{$moduleAdminLink}" method="post" enctype="multipart/form-data" novalidate>
			{call name="ckcpromobarre_lang_widget"
				group="b2c"
				texts_by_lang=$promotextsByLang
				languages=$languages
				default_lang_id=$default_lang_id
				employee_lang_id=$employee_lang_id
				input_id_prefix="idInput"
				container_id_prefix="selectedIds"}
			<input type="hidden" name="id_promotexts_json" id="id_promotexts_json" value="">
			<div class="ckcpromobarre-actions mt-3">
				<input class="btn btn-success saveExceptions" type="submit" name="submitPromoTextsB2c" value="{l s='Save the list of promo texts' mod='ckcpromotionbanner'}">
				{if $ckctranslate_ready}
					<button class="btn btn-info"
							type="submit"
							name="submitTranslatePromoTextsB2c"
							value="1"
							onclick="return confirm('{l s='Translate source texts with ckctranslate and overwrite the target language?' mod='ckcpromotionbanner'|escape:'javascript':'UTF-8'}');">
						{l s='Translate with ckctranslate' mod='ckcpromotionbanner'}
					</button>
				{else}
					<span class="help-block" style="display:inline-block;margin-left:10px;">
						{l s='Enable ckctranslate + API key to translate in one click.' mod='ckcpromotionbanner'}
					</span>
				{/if}
			</div>
		</form>
	</div>
</div>

<div class="panel panel-promobanner">
	<h3 style="margin-top: 1px !important; border: none; margin-bottom: 5px; margin-left: -7px; background: none;" class="h3-ckctitle ckch3"><i class="icon icon-link"></i> {l s='Ajouter du/des texte(s) pour la promotion PRO' mod='ckcpromotionbanner'}</h3>
	<p class="help-block">{l s='Configure each language separately. Type a text then click + (or Save — pending text is kept).' mod='ckcpromotionbanner'}</p>
	<div class="mt-5 id-select-container">
		<form id="id_promotextsPro" class="context-form" action="{$moduleAdminLink}" method="post" enctype="multipart/form-data" novalidate>
			{call name="ckcpromobarre_lang_widget"
				group="pro"
				texts_by_lang=$promotextsProByLang
				languages=$languages
				default_lang_id=$default_lang_id
				employee_lang_id=$employee_lang_id
				input_id_prefix="idInputPro"
				container_id_prefix="selectedIdsPro"}
			<input type="hidden" name="id_promotextsPro_json" id="id_promotextsPro_json" value="">
			<div class="ckcpromobarre-actions mt-3">
				<input class="btn btn-success saveExceptions" type="submit" name="submitPromoTextsPro" value="{l s='Save the list of promo texts' mod='ckcpromotionbanner'}">
				{if $ckctranslate_ready}
					<button class="btn btn-info"
							type="submit"
							name="submitTranslatePromoTextsPro"
							value="1"
							onclick="return confirm('{l s='Translate source texts with ckctranslate and overwrite the target language?' mod='ckcpromotionbanner'|escape:'javascript':'UTF-8'}');">
						{l s='Translate with ckctranslate' mod='ckcpromotionbanner'}
					</button>
				{/if}
			</div>
		</form>
	</div>
</div>

<script type="text/javascript">
(function ($) {
	var promotextsByLang = {$promotextsByLangJson nofilter};
	var promotextsProByLang = {$promotextsProByLangJson nofilter};
	var knownLangIds = [
		{foreach from=$languages item=lang name=cklang}{$lang.id_lang|intval}{if !$smarty.foreach.cklang.last},{/if}{/foreach}
	];

	var state = {
		b2c: {},
		pro: {}
	};

	function csvToArray(csv) {
		if (csv === null || csv === undefined || csv === '') {
			return [];
		}
		return String(csv).split(',,').map(function (t) { return t.trim(); }).filter(function (t) { return t !== ''; });
	}

	function init(group, source) {
		source = source || {};
		knownLangIds.forEach(function (langId) {
			var key = String(langId);
			var fromJson = source[key] !== undefined ? source[key] : source[langId];
			state[group][key] = csvToArray(fromJson);
		});
	}

	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function render(group, langId) {
		langId = String(langId);
		var $container = $('.ckcpromobarre-selected[data-group="' + group + '"][data-lang="' + langId + '"]');
		$container.empty();
		var items = state[group][langId] || [];
		items.forEach(function (text, index) {
			$container.append(
				'<span class="tag" data-id="' + index + '">' +
				escapeHtml(text) +
				' <span class="remove" data-group="' + group + '" data-lang="' + langId + '" data-id="' + index + '">✖</span>' +
				'</span>'
			);
		});
	}

	function renderGroup(group) {
		Object.keys(state[group]).forEach(function (langId) {
			render(group, langId);
		});
	}

	function buildPayload(group) {
		var payload = {};
		knownLangIds.forEach(function (langId) {
			var key = String(langId);
			payload[key] = state[group][key] || [];
		});
		return JSON.stringify(payload);
	}

	function commitPendingInputs(group) {
		$('.ckcpromobarre-input[data-group="' + group + '"]').each(function () {
			var $input = $(this);
			var langId = String($input.data('lang'));
			var value = ($input.val() || '').trim();
			if (value === '') {
				return;
			}
			state[group][langId] = state[group][langId] || [];
			if (state[group][langId].indexOf(value) < 0) {
				state[group][langId].push(value);
			}
			$input.val('');
		});
	}

	function syncHidden(group, hiddenId) {
		commitPendingInputs(group);
		renderGroup(group);
		$('#' + hiddenId).val(buildPayload(group));
	}

	$(document).ready(function () {
		init('b2c', promotextsByLang);
		init('pro', promotextsProByLang);
		renderGroup('b2c');
		renderGroup('pro');
		$('#id_promotexts_json').val(buildPayload('b2c'));
		$('#id_promotextsPro_json').val(buildPayload('pro'));

		$('#id_promotexts').on('submit', function () {
			syncHidden('b2c', 'id_promotexts_json');
		});
		$('#id_promotextsPro').on('submit', function () {
			syncHidden('pro', 'id_promotextsPro_json');
		});

		$(document).on('click', '.ckcpromobarre-lang-tab', function (e) {
			e.preventDefault();
			var $tab = $(this);
			var group = $tab.data('group');
			var langId = String($tab.data('lang'));

			$('.ckcpromobarre-lang-tab[data-group="' + group + '"]').removeClass('active');
			$tab.addClass('active');

			$('.ckcpromobarre-lang-pane[data-group="' + group + '"]').hide().removeClass('active');
			$('.ckcpromobarre-lang-pane[data-group="' + group + '"][data-lang="' + langId + '"]').show().addClass('active');
		});

		$(document).on('click', '.ckcpromobarre-add', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var group = $btn.data('group');
			var langId = String($btn.data('lang'));
			var $input = $('.ckcpromobarre-input[data-group="' + group + '"][data-lang="' + langId + '"]');
			var value = ($input.val() || '').trim();

			if (value === '') {
				return;
			}

			state[group][langId] = state[group][langId] || [];
			if (state[group][langId].indexOf(value) >= 0) {
				$input.val('');
				return;
			}
			state[group][langId].push(value);
			$input.val('');
			render(group, langId);
		});

		$(document).on('keydown', '.ckcpromobarre-input', function (e) {
			if (e.which === 13) {
				e.preventDefault();
				$(this).closest('.input-group').find('.ckcpromobarre-add').first().trigger('click');
			}
		});

		$(document).on('click', '.ckcpromobarre-selected .remove', function (e) {
			e.preventDefault();
			var $rm = $(this);
			var group = $rm.data('group');
			var langId = String($rm.data('lang'));
			var index = parseInt($rm.data('id'), 10);

			if (!state[group][langId] || isNaN(index)) {
				return;
			}
			state[group][langId].splice(index, 1);
			render(group, langId);
		});

		$(document).on('click', '.ckcpromobarre-copy-default', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var group = $btn.data('group');
			var langId = String($btn.data('lang'));
			var defaultLangId = String($btn.data('default-lang'));
			var source = (state[group][defaultLangId] || []).slice();

			if (source.length === 0) {
				return;
			}

			state[group][langId] = source;
			render(group, langId);
		});
	});
})(jQuery);
</script>
