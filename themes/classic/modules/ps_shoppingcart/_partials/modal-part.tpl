
<div class="col-md-5 divide-right">
    <div class="row">
        <div class="col-md-6">
            {if $product.default_image}
                <img src="{$product.default_image.medium.url}"
                     data-full-size-image-url="{$product.default_image.large.url}" title="{$product.default_image.legend}"
                     alt="{$product.default_image.legend}" loading="lazy" class="product-image">
            {else}
                <img src="{$urls.no_picture_image.bySize.medium_default.url}" loading="lazy" class="product-image" />
            {/if}
        </div>
        <div class="col-md-6">
            <h6 class="h6 product-name">{$product.name}</h6>
            {if isset($is_pack) && !$is_pack}
                {assign var="price_parts" value=","|explode:$product.price}
                {assign var="price_integer" value=$price_parts[0]}
                {assign var="price_decimal" value=$price_parts[1]}
                <div class="product-price {if $product.has_discount}has-discount{/if}">
                    <span class="price-integer">{$price_integer}</span><span
                            class="price-decimal">,{$price_decimal}</span></span>
                </div>
                {hook h='displayProductPriceBlock' product=$product type="unit_price"}
                <div class="small">
                    {foreach from=$product.attributes item="property_value" key="property"}
                        <span
                                class="{$property|lower}">{l s='%label%:' sprintf=['%label%' => $property] d='Shop.Theme.Global'}<strong>
      {$property_value}</strong></span><br>
                    {/foreach}
                </div>
                <span
                        class="product-quantity small">{l s='Quantity:' d='Shop.Theme.Checkout'}&nbsp;<strong>{$product.cart_quantity}</strong></span>
            {else}
                {isset($pack_content ) && $pack_content nofilter}
            {/if}
        </div>
    </div>
</div>
<div class="col-md-7">
    <div class="cart-content">
        {if $cart.products_count > 1}
            <p class="cart-products-count">
                {l s='There are %products_count% items in your cart.' sprintf=['%products_count%' => $cart.products_count] d='Shop.Theme.Checkout'}
            </p>
        {else}
            <p class="cart-products-count">
                {l s='There is %products_count% item in your cart.' sprintf=['%products_count%' =>$cart.products_count] d='Shop.Theme.Checkout'}
            </p>
        {/if}
        <p><span class="label">{l s='Subtotal:' d='Shop.Theme.Checkout'}</span>&nbsp;<span
                    class="subtotal value">{$cart.subtotals.products.value}</span></p>

        {if !$configuration.display_prices_tax_incl && $configuration.taxes_enabled}
            <p>
                <span>{$cart.totals.total.label}{if $configuration.display_taxes_label}&nbsp;{$cart.labels.tax_short}{/if}</span>&nbsp;<span>{$cart.totals.total.value}</span>
            </p>
            <p class="product-total"><span class="label">{$cart.totals.total_including_tax.label}</span>&nbsp;<span
                        class="value">{$cart.totals.total_including_tax.value}</span></p>
        {else}
            <p class="product-total"><span
                        class="label">{$cart.totals.total.label}&nbsp;{if $configuration.taxes_enabled && $configuration.display_taxes_label}{$cart.labels.tax_short}{/if}</span>&nbsp;<span
                        class="value">{$cart.totals.total.value}</span></p>
        {/if}

        {if $cart.subtotals.tax}
            <p class="product-tax">
                {l s='%label%:' sprintf=['%label%' => $cart.subtotals.tax.label] d='Shop.Theme.Global'}&nbsp;<span
                        class="value">{$cart.subtotals.tax.value}</span></p>
        {/if}
        {hook h='displayCartModalContent' product=$product}
        <div class="cart-content-btn">
            <button type="button" class="btn btn-secondary"
                    data-dismiss="modal">{l s='Continue shopping' d='Shop.Theme.Actions'}</button>
            <a href="{$cart_url}" class="btn btn-primary">{l s='Proceed to checkout' d='Shop.Theme.Actions'}</a>
        </div>
    </div>
</div>
