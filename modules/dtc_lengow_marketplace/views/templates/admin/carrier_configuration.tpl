{**
 * Configuration du transporteur des commandes marketplace.
 *
 * Rendu maison plutot que HelperForm : les listes de produits comptent plusieurs centaines
 * d'options et le form.tpl du core genere des megaoctets de blancs dans ce cas.
 *}
<div class="panel">
	<div class="panel-heading">
		<i class="icon-truck"></i> {$labels.legend|escape:'html':'UTF-8'}
	</div>

	<form method="post"{if $form_action} action="{$form_action|escape:'html':'UTF-8'}"{/if} class="form-horizontal">
		<div class="form-group">
			<label class="control-label col-lg-3">{$labels.enabled|escape:'html':'UTF-8'}</label>
			<div class="col-lg-9">
				<span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="{$keys.enabled|escape:'html':'UTF-8'}" id="dtc_lgwmp_enabled_on" value="1"{if $values.enabled} checked="checked"{/if} />
					<label for="dtc_lgwmp_enabled_on">{$labels.yes|escape:'html':'UTF-8'}</label>
					<input type="radio" name="{$keys.enabled|escape:'html':'UTF-8'}" id="dtc_lgwmp_enabled_off" value="0"{if !$values.enabled} checked="checked"{/if} />
					<label for="dtc_lgwmp_enabled_off">{$labels.no|escape:'html':'UTF-8'}</label>
					<a class="slide-button btn"></a>
				</span>
				<p class="help-block">{$labels.enabled_help|escape:'html':'UTF-8'}</p>
			</div>
		</div>

		{foreach $carrier_fields as $field}
			<div class="form-group">
				<label class="control-label col-lg-3" for="{$field.key|escape:'html':'UTF-8'}">{$field.label|escape:'html':'UTF-8'}</label>
				<div class="col-lg-9">
					<select name="{$field.key|escape:'html':'UTF-8'}" id="{$field.key|escape:'html':'UTF-8'}" class="chosen fixed-width-xxl">
						<option value="0">{$labels.none|escape:'html':'UTF-8'}</option>
						{foreach $carriers as $carrier}
							<option value="{$carrier.id|intval}"{if $carrier.id == $field.value} selected="selected"{/if}>{$carrier.name|escape:'html':'UTF-8'}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/foreach}

		<div class="form-group">
			<label class="control-label col-lg-3" for="{$keys.priority|escape:'html':'UTF-8'}">{$labels.priority|escape:'html':'UTF-8'}</label>
			<div class="col-lg-9">
				<select name="{$keys.priority|escape:'html':'UTF-8'}" id="{$keys.priority|escape:'html':'UTF-8'}" class="fixed-width-xxl">
					{foreach $priorities as $priority}
						<option value="{$priority.id|escape:'html':'UTF-8'}"{if $priority.id == $values.priority} selected="selected"{/if}>{$priority.name|escape:'html':'UTF-8'}</option>
					{/foreach}
				</select>
				<p class="help-block">{$labels.priority_help|escape:'html':'UTF-8'}</p>
			</div>
		</div>

		{foreach $product_fields as $field}
			<div class="form-group">
				<label class="control-label col-lg-3" for="{$field.key|escape:'html':'UTF-8'}">{$field.label|escape:'html':'UTF-8'}</label>
				<div class="col-lg-9">
					<select name="{$field.key|escape:'html':'UTF-8'}[]" id="{$field.key|escape:'html':'UTF-8'}" class="chosen fixed-width-xxl" multiple="multiple" data-placeholder="{$labels.product_placeholder|escape:'html':'UTF-8'}">
						{foreach $products as $product}
							<option value="{$product.id|intval}"{if isset($field.selected[$product.id])} selected="selected"{/if}>{$product.name|escape:'html':'UTF-8'}</option>
						{/foreach}
					</select>
					{if $field.help}<p class="help-block">{$field.help|escape:'html':'UTF-8'}</p>{/if}
				</div>
			</div>
		{/foreach}

		<div class="panel-footer">
			<button type="submit" name="submitDtcMarketplaceCarriers" class="btn btn-default pull-right">
				<i class="process-icon-save"></i> {$labels.save|escape:'html':'UTF-8'}
			</button>
		</div>
	</form>
</div>
