<div id="ap5-product-list">
    {foreach from=$productsPack item=productPack}
        {if !empty($productPack.attributes.groups)}
            <span class="font-weight-bold">{$productPack.presentation.name}</span>
            <!-- attributes -->
            <div class="product-variants ap5-attributes" data-id-product-pack="{$productPack.id_product_pack|intval}">
                {foreach from=$productPack.attributes.groups key=id_attribute_group item=group}
                    {if $group.attributes|@count}
                        {assign var=indexCounter value=0}
                        {foreach from=$group.attributes key=id_attribute item=group_attribute}
                            {* Force the user-selected attribute to be the default one *}
                            {assign var=indexCounter value=$indexCounter+1}
                            {if isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])}
                                {$group['default'] = $id_attribute}
                            {/if}
                            {assign var="groupName" value="group_`$productPack.id_product`_$id_attribute_group"}
                            {assign var=ap5_isCurrentSelectedIdAttribute value=((isset($productsPackErrors[$productPack.id_product_pack]) && isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])) || $group.default == $id_attribute)}
                            {if ($group.group_type == 'color')}
                                <input class="product-pack-variant-radio input-color ap5-attribute-radio" type="radio" data-product="{$productPack.id_product}" data-product-attribute="{$id_attribute_group}" name="{$groupName}" value="{$id_attribute|intval}" {if $ap5_isCurrentSelectedIdAttribute} checked="checked"{/if} data-position="{$indexCounter}">
                            {/if}
                        {/foreach}

                        {if !empty($group.attributes) && in_array($group.group_type, ['select', 'color', 'radio'])}
                            <div class="clearfix product-variants-item ap5-attribute-fieldset">
                                <span class="control-label">{$group.name}</span>
                                <div class="attribute_list ap5-attribute-list">
                                    {if ($group.group_type == 'select')}
                                        <select name="{$groupName}" id="group_{$id_attribute_group|intval}" class="ap5-attribute-select no-print form-control form-control-select"{if in_array($productPack.id_product_pack, $packExcludeList)} disabled="disabled"{/if}>
                                            {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                                {assign var=ap5_isCurrentSelectedIdAttribute value=((isset($productsPackErrors[$productPack.id_product_pack]) && isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])) || $group.default == $id_attribute)}
                                                <option value="{$id_attribute|intval}" {if $ap5_isCurrentSelectedIdAttribute} selected="selected"{/if} title="{$group_attribute.name}">{$group_attribute.name}</option>
                                            {/foreach}
                                        </select>
                                    {elseif ($group.group_type == 'color')}
                                        <div class="custom-color-select">
                                            <div class="selected-option">
                                                {assign var="default_colorpicker" value=""}
                                                {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                                    {assign var=ap5_isCurrentSelectedIdAttribute value=((isset($productsPackErrors[$productPack.id_product_pack]) && isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])) || $group.default == $id_attribute)}
                                                    {if $default_colorpicker = $id_attribute}
                                                        <img src="{$urls.img_col_url}{$id_attribute|intval}.jpg" alt="{$productPack.attributes.colors.$id_attribute.name}" /> {$group_attribute.name}
                                                    {/if}
                                                {/foreach}
                                            </div>
                                            <div class="options-list">
                                                {assign var="default_colorpicker" value=""}
                                                {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                                    {assign var=ap5_isCurrentSelectedIdAttribute value=((isset($productsPackErrors[$productPack.id_product_pack]) && isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])) || $group.default == $id_attribute)}
                                                    <div class="option" data-value="{$id_attribute}" data-name="{$groupName}" style="display: flex; align-items: center;">
                                                        {if $productPack.attributes.colors.$id_attribute.image_exists}
                                                            <img src="{$urls.img_col_url}{$id_attribute|intval}.jpg" alt="{$productPack.attributes.colors.$id_attribute.name}" />
                                                        {/if}
                                                        {$group_attribute.name}
                                                    </div>
                                                    {if $ap5_isCurrentSelectedIdAttribute}
                                                        {$default_colorpicker = $id_attribute}
                                                    {/if}
                                                {/foreach}
                                            </div>
                                        </div>
{*                                        <ul class="ap5-color-to-pick-list ap5-color-to-pick-list-{$productPack.id_product_pack|intval}-{$id_attribute_group|intval}">*}
{*                                            {assign var="default_colorpicker" value=""}*}
{*                                            {foreach from=$group.attributes key=id_attribute item=group_attribute}*}
{*                                                {assign var=ap5_isCurrentSelectedIdAttribute value=((isset($productsPackErrors[$productPack.id_product_pack]) && isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])) || $group.default == $id_attribute)}*}
{*                                                <li class="float-left float-xs-left pull-xs-left input-container{if $ap5_isCurrentSelectedIdAttribute} selected{/if}">*}
{*                                                    <label aria-label="{$group_attribute.name|escape:'html':'UTF-8'}">*}
{*                                                        <a href="{$productPack.presentation.url}" data-id-product-pack="{$productPack.id_product_pack|intval}" data-id-attribute-group="{$id_attribute_group|intval}" data-id-attribute="{$id_attribute|intval}" id="color_{$id_attribute|intval}" name="{$productPack.attributes.colors.$id_attribute.name}" class="ap5-color color color_pick{if $ap5_isCurrentSelectedIdAttribute} selected{/if}{if in_array($productPack.id_product_pack, $packExcludeList)} disabled{/if}" style="background: {$productPack.attributes.colors.$id_attribute.value};" title="{$productPack.attributes.colors.$id_attribute.name}">*}
{*                                                            {if $productPack.attributes.colors.$id_attribute.image_exists}*}
{*                                                                <img src="{$urls.img_col_url}{$id_attribute|intval}.jpg" alt="{$productPack.attributes.colors.$id_attribute.name}" />*}
{*                                                            {/if}*}
{*                                                            {$group_attribute.name}*}
{*                                                        </a>*}
{*                                                    </label>*}
{*                                                </li>*}
{*                                                {if $ap5_isCurrentSelectedIdAttribute}*}
{*                                                    {$default_colorpicker = $id_attribute}*}
{*                                                {/if}*}
{*                                            {/foreach}*}
{*                                        </ul>*}
                                    {elseif ($group.group_type == 'radio')}
                                        <ul>
                                            {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                                {assign var=ap5_isCurrentSelectedIdAttribute value=((isset($productsPackErrors[$productPack.id_product_pack]) && isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])) || $group.default == $id_attribute)}
                                                <li class="input-container float-left float-xs-left pull-xs-left">
                                                    <input type="radio" class="input-radio ap5-attribute-radio" name="{$groupName}" value="{$id_attribute}" {if $ap5_isCurrentSelectedIdAttribute} checked="checked"{/if}{if in_array($productPack.id_product_pack, $packExcludeList)} disabled="disabled"{/if} />
                                                    <span class="radio-label">{$group_attribute.name}</span>
                                                </li>
                                            {/foreach}
                                        </ul>
                                    {/if}
                                </div>
                            </div>
                        {/if}
                    {/if}
                {/foreach}
            </div>
        {/if}
    {/foreach}
</div>
