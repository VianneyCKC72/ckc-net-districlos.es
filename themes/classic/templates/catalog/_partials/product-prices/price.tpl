{assign var="has_campaign_discount" value=false}
{if isset($campaignProductDiscount) && isset($campaignProductDiscount.active) && $campaignProductDiscount.active && !$product.has_discount}
    {assign var="has_campaign_discount" value=true}
{/if}

{if isset($showPackMlDualPrice) && $showPackMlDualPrice && !isset($initial_pack)}
    {include file='catalog/_partials/product-prices/price-dual-ml.tpl'}
{else}
<div class="d-flex align-items-center product-prices">
    {if $has_campaign_discount}
        {assign var="price_parts" value=","|explode:$campaignProductDiscount.price}
    {else}
        {assign var="price_parts" value=","|explode:$product.price}
    {/if}
    {assign var="price_integer" value=$price_parts[0]}
    {assign var="price_decimal" value=$price_parts[1]}
    <div class="product-price price {if $product.has_discount || $has_campaign_discount}has-discount{/if} mr-2" itemprop="price">
        {if isset($initial_pack)}
            <span class="price-loader" style="display: inline-block;"></span>
        {else}
            <span class="price-integer">{$price_integer}</span><span class="price-decimal">,{$price_decimal}</span>
        {/if}
    </div>
    {hook h='displayProductPriceBlock' product=$product type="weight" hook_origin='product_sheet'}

    {if !isset($initial_pack)}
        <div class="tax-shipping-delivery-label">
            {if !$configuration.taxes_enabled}
                {l s='No tax' d='Shop.Theme.Catalog'}
            {elseif $configuration.display_taxes_label}
                {$product.labels.tax_long}
            {/if}
            {hook h='displayProductPriceBlock' product=$product type="price"}
            {hook h='displayProductPriceBlock' product=$product type="after_price"}
            {if $product.is_virtual	== 0}
                {if $product.additional_delivery_times == 1}
                    {if $product.delivery_information}
                        <span class="delivery-information">{$product.delivery_information}</span>
                    {/if}
                {elseif $product.additional_delivery_times == 2}
                    {if $product.quantity >= $product.quantity_wanted}
                        <span class="delivery-information">{$product.delivery_in_stock}</span>
                        {* Out of stock message should not be displayed if customer can't order the product. *}
                    {elseif $product.add_to_cart_url}
                        <span class="delivery-information">{$product.delivery_out_stock}</span>
                    {/if}
                {/if}
            {/if}
        </div>
    {/if}
    {block name='product_discount'}
        {if $product.has_discount || $has_campaign_discount}
            <div class="product-discount">
                {hook h='displayProductPriceBlock' product=$product type="old_price"}
                {if $has_campaign_discount}
                    {if !$pro.env}
                        <div class="product-discount tag-discount">
                            <div class="font-weight-bold">
                                <span class="discount-value">- {$campaignProductDiscount.percentage}%</span>
                            </div>
                        </div>
                    {/if}
                    <span class="regular-price">{$campaignProductDiscount.regular_price}</span>
                {else}
                    {if !$pro.env}
                        {include file='catalog/_partials/product-prices/has-discount.tpl'}
                    {/if}
                    <span class="regular-price">{$product.regular_price}</span>
                {/if}
            </div>
        {/if}
    {/block}
</div>
{/if}
{if $product.quantity_wanted > 1 && !$product.is_configurable && !$product.name|strstr:"sur mesure"}
    <p class="product-unit-price sub">
        {$product.unit_price_full}
        {if $env_pro}
            HT
        {/if} à l'unité</p>
{/if}
