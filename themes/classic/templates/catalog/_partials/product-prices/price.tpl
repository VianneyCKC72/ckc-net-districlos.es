{* Calcul du prix final temporaire avec réduction *}
{if !isset($initial_pack) && isset($product.price_amount) && $product.price_amount >= 500}
    {assign var="p" value=$product.price_amount|floatval}
    {assign var="final_price" value=($p >= 1000) ? ($p * 0.90) : (($p >= 500) ? ($p * 0.95) : $p)}
    {if $final_price < $p}
        {assign var="final_price_formatted" value=$final_price|string_format:"%.2f"|replace:'.':','}
        {assign var="final_price_parts" value=","|explode:$final_price_formatted}
        {assign var="show_final_price" value=true}
        {* Calcul du pourcentage de réduction en dur *}
        {assign var="hard_discount_percent" value=($p >= 1000) ? 10 : 5}
    {/if}
{/if}

{* Récupération de la réduction dynamique (PrestaShop) *}
{if $product.has_discount && isset($product.discount_percentage_absolute) && $product.discount_type === 'percentage'}
    {assign var="dynamic_discount_percent" value=$product.discount_percentage_absolute}
{/if}

<div class="d-flex align-items-center product-prices">
    {if isset($show_final_price) && $show_final_price}
        {assign var="price_parts" value=$final_price_parts}
        {assign var="price_integer" value=$final_price_parts[0]}
        {assign var="price_decimal" value=$final_price_parts[1]}
    {else}
        {assign var="price_parts" value=","|explode:$product.price}
        {assign var="price_integer" value=$price_parts[0]}
        {assign var="price_decimal" value=$price_parts[1]|replace:' €':''|replace:'&nbsp;€':''|replace:'€':''|trim}
    {/if}
    <div class="product-price price {if $product.has_discount}has-discount{/if} mr-2" itemprop="price">
        {if isset($initial_pack)}
            <span class="price-loader" style="display: inline-block;"></span>
        {else}
            <span class="price-integer">{$price_integer}</span><span class="price-decimal">,{$price_decimal} €</span>
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
        {if isset($show_final_price) && $show_final_price}
            <div class="product-discount" style="margin-left: 15px;">
                {if isset($dynamic_discount_percent) && $dynamic_discount_percent > 0}
                    <div class="price-remise-label">Prix remisé : <span class="regular-price">{$product.regular_price}</span>
                        <span style="color: #ff144e; font-weight: bold;">-{$dynamic_discount_percent}</span>
                    </div>
                {/if}
                <div class="price-remise-label">SOLDES : <span class="regular-price">{$product.price}</span>
                    {if isset($hard_discount_percent)}
                        <span style="color: #ff144e; font-weight: bold;">-{$hard_discount_percent}%</span>
                    {/if}
                </div>
            </div>
        {elseif $product.has_discount}
            <div class="product-discount" style="margin-left: 15px;">
                <div class="price-remise-label">Prix remisé : <span class="regular-price">{$product.regular_price|replace:' €':''}</span>
                    {if isset($dynamic_discount_percent) && $dynamic_discount_percent > 0}
                        <span style="color: #ff144e; font-weight: bold;">-{$dynamic_discount_percent}</span>
                    {/if}
                </div>
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

{* Style pour mobile - Prix remisé en 11px *}
{if !isset($price_remise_mobile_css_added)}
    {assign var="price_remise_mobile_css_added" value=true}
    <style>
        /* Masque SOLDES pour les produits dynamicproduct */
        body.dynamic-product .price-remise-label {
            display: none !important;
        }
        
        @media (max-width: 767.98px) {
            .price-remise-label {
                font-size: 10px !important;
            }
            .regular-price {
                font-size: 10px !important;
            }
            .banner-quick-view-row .col-price:has(.product-discount.has-discount) .product-prices {
                flex-direction: column;
                align-items: flex-start !important;
            }
            .banner-quick-view-row .product-discount {
                margin-left: 0 !important;
            }
        }
    </style>
{/if}
