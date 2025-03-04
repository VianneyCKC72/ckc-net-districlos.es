<div id="ap5-product-list">
    {foreach from=$productsPack item=productPack}
        {if !empty($productPack.attributes.groups)}
            <span class="bold">{$productPack.presentation.name}</span>
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
                            <input class="product-pack-variant-radio input-color ap5-attribute-radio" type="radio" data-product="{$productPack.id_product}" data-product-attribute="{$id_attribute_group}" name="{$groupName}" value="{$id_attribute|intval}" {if $ap5_isCurrentSelectedIdAttribute} checked="checked"{/if} data-position="{$indexCounter}">
                        {/foreach}

                        {if !empty($group.attributes) && in_array($group.group_type, ['select', 'color', 'radio'])}
                            <div class="clearfix hiddenlabel">
                                <p class=group-name>{$group.name}</p>
                                <div class="select_wrap" data-id="{$productPack.id_product}_{$id_attribute_group}" id="wrap{$productPack.id_product}_{$id_attribute_group}">

                                    <ul class="default_option" data-id="{$productPack.id_product}_{$id_attribute_group}" id="option{$productPack.id_product}_{$id_attribute_group}">
                                        <li style="list-style: none">
                                            <div class="option">
                                                <p>{$group.name}</p>
                                            </div>
                                        </li>
                                    </ul>
                                    <ul class="select_ul" data-id="{$productPack.id_product}_{$id_attribute_group}" id="ul{$productPack.id_product}_{$id_attribute_group}">
                                        {assign var=indexCounter value=0}
                                        {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                            {assign var=indexCounter value=$indexCounter+1}
                                            {assign var=ap5_isCurrentSelectedIdAttribute value=((isset($productsPackErrors[$productPack.id_product_pack]) && isset($packCompleteAttributesList[$productPack.id_product_pack]) && in_array($id_attribute, $packCompleteAttributesList[$productPack.id_product_pack])) || $group.default == $id_attribute)}

                                            <li class="liOption" style="list-style: none" data-active="{if $ap5_isCurrentSelectedIdAttribute}1{else}0{/if}" data-value="{$id_attribute}" data-position="{$indexCounter}">
                                                <div class="option">
                                                    {if $group.group_type == 'color' && $group_attribute.html_color_code && !$group_attribute.texture}
                                                        <div style="background:{$group_attribute.html_color_code}; border-radius: 100%; width: 20px; height: 20px"></div>
                                                    {/if}
                                                    <p><span></span>{$group_attribute.name}</p>
                                                </div>
                                            </li>
                                        {/foreach}
                                    </ul>
                                </div>
                            </div>
                        {/if}
                    {/if}
                {/foreach}
            </div>
        {/if}
    {/foreach}
</div>
