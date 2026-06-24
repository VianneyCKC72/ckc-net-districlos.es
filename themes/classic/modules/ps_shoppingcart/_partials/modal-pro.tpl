{* LEFT — PRODUCT *}
<div class="col-md-5 divide-right cart-pro-left">

    <div class="cart-pro-product-box">

        <div class="cart-pro-image-wrap">
            {if isset($product.default_image)}
                <img src="{$product.default_image.medium.url}"
                     alt="{$product.default_image.legend|escape:'html'}"
                     class="cart-pro-image">
            {else}
                <img src="https://via.placeholder.com/300x300?text=No+Image"
                     class="cart-pro-image" />
            {/if}
        </div>

        <div class="cart-pro-product-name">
            {$product.name|escape:'html'}
        </div>

    </div>

</div>

{* RIGHT — CART SUMMARY *}
<div class="col-md-7 cart-pro-right">
    <div class="cart-content cart-pro-summary">

        {* Nombre d'articles *}
        <p class="cart-products-count cart-pro-count">
            {l s='Il y a %products_count% articles dans votre panier.' sprintf=['%products_count%'=>$cart.products_count] d='Shop.Theme.Custommodules'}
        </p>

        <div class="cart-pro-table">

            {* Prix unitaire HT *}
            <div class="cart-pro-row">
                <div class="cart-pro-label">{l s='Prix unitaire HT' d='Shop.Theme.Custommodules'}</div>
                <div class="cart-pro-value">
                    {if isset($product.price_tax_exc)}{$product.price_tax_exc} €{/if}
                </div>
            </div>

            {* Quantité ajoutée *}
            <div class="cart-pro-row">
                <div class="cart-pro-label">{l s='Quantité ajoutée' d='Shop.Theme.Custommodules'}</div>
                <div class="cart-pro-value">
                    {if isset($product.cart_quantity)}{$product.cart_quantity}{/if}
                </div>
            </div>

            {* Total ligne HT *}
            <div class="cart-pro-row">
                <div class="cart-pro-label">{l s='Total HT' d='Shop.Theme.Custommodules'}</div>
                <div class="cart-pro-value">
                    {if isset($product.total)}{$product.total}{/if}
                </div>
            </div>

            <div class="cart-pro-separator"></div>

            {* Sous-total HT du panier *}
            <div class="cart-pro-row">
                <div class="cart-pro-label">{l s='Sous-total panier HT' d='Shop.Theme.Custommodules'}</div>
                <div class="cart-pro-value">
                    {if isset($cart.totals.total_excluding_tax.value)}{$cart.totals.total_excluding_tax.value}{/if}
                </div>
            </div>

            {* TVA *}
            {if isset($cart.subtotals.tax)}
                <div class="cart-pro-row">
                    <div class="cart-pro-label">{l s='TVA' d='Shop.Theme.Custommodules'}</div>
                    <div class="cart-pro-value">
                        {$cart.subtotals.tax.value}
                    </div>
                </div>
            {/if}

            {* Total TTC *}
            <div class="cart-pro-row cart-pro-total-row">
                <div class="cart-pro-label cart-pro-total-label">{l s='Total TTC' d='Shop.Theme.Custommodules'}</div>
                <div class="cart-pro-value cart-pro-total-value">
                    {if isset($cart.totals.total_including_tax.value)}
                        {$cart.totals.total_including_tax.value}
                    {/if}
                </div>
            </div>

        </div>

        {hook h='displayCartModalContent' product=$product}

        <div class="cart-pro-actions">

            <a class="cart-pro-back" data-dismiss="modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                {l s='Continuer mes achats' d='Shop.Theme.Custommodules'}
            </a>

            <a href="{$cart_url}" class="btn btn-primary cart-pro-btn-primary">
                {l s='Commander' d='Shop.Theme.Custommodules'}
            </a>

        </div>

    </div>
</div>

