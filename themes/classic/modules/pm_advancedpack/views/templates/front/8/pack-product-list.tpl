<div id="ap5-product-list">
    {foreach from=$productsPack item=productPack}
        {if !empty($productPack.attributes.groups)}
            <span class="font-weight-bold">{$productPack.presentation.name}{if $productPack.quantity} <span style="padding: 1px 15px;background-color: #79C144;color: #fff;font-weight: 600;font-size: 16px;margin-left: 10px;">x{$productPack.quantity}</span>{/if}</span>
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
                        {/foreach}

                        {if !empty($group.attributes) && in_array($group.group_type, ['select', 'color', 'radio'])}
                            <div class="clearfix product-variants-item ap5-attribute-fieldset">
                                <span class="control-label">{$group.name}</span>
                                <div class="attribute_list ap5-attribute-list">
                                    {if ($group.group_type == 'select') || ($group.group_type == 'color')}
                                        <select name="{$groupName}" id="group_{$id_attribute_group|intval}" class="ap5-attribute-select no-print form-control form-control-select"{if in_array($productPack.id_product_pack, $packExcludeList)} disabled="disabled"{/if}>
                                            {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                                {assign var=ap5_isCurrentSelectedIdAttribute value=((isset($productsPackErrors[$productPack.id_product_pack]) && isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])) || $group.default == $id_attribute)}
                                                <option value="{$id_attribute|intval}" {if $ap5_isCurrentSelectedIdAttribute} selected="selected"{/if} title="{$group_attribute.name}">{$group_attribute.name}</option>
                                            {/foreach}
                                        </select>
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
