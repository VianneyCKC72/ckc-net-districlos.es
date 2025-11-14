{if $product.has_discount}
    <div class="product-discount has-discount-header">
        <div class="tag-discount">Promotion</div>
        <div class="">
            Bénéficiez de
            <span class="font-weight-bold">
                {if $product.discount_type === 'percentage'}
                    <span class="discount-value">
                        {l s='- %percentage%' d='Shop.Theme.Catalog' sprintf=['%percentage%' => $product.discount_percentage_absolute]}
                    </span>
                {else}
                    <span class="discount-value">
                        {l s='- %amount%' d='Shop.Theme.Catalog' sprintf=['%amount%' => $product.discount_to_display]}
                    </span>
                {/if}
            </span>
            directement <strong>dans votre panier</strong>
        </div>

    </div>
{/if}