<div class="d-flex align-items-center">
    {assign var="price_parts" value=","|explode:$product.price}
    {assign var="price_integer" value=$price_parts[0]}
    {assign var="price_decimal" value=$price_parts[1]}
    <div class="product-price {if $product.has_discount}has-discount{/if} mr-2">
        <span class="price-integer">{$price_integer}</span><span class="price-decimal">,{$price_decimal}</span></span>
    </div>
    {hook h='displayProductPriceBlock' product=$product type="weight" hook_origin='product_sheet'}

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
    {block name='product_discount'}
        {if $product.has_discount}
            <div class="product-discount">
                {hook h='displayProductPriceBlock' product=$product type="old_price"}
                <span class="regular-price">{$product.regular_price}</span>
            </div>
        {/if}
    {/block}
</div>
{if $product.quantity_wanted > 1 && !$product.is_configurable && !$product.name|strstr:"sur mesure"}
    <p class="product-unit-price sub">
        {$product.unit_price_full}
        {if $env_pro}
            HT
        {/if} à l'unité</p>
{/if}
