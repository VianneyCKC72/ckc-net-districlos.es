{if $product.has_discount}
    <div class="product-discount has-discount">
        {* Réduction à commentée - si affichage de price_old.tpl pour les SOLDES *}
        <div class="font-weight-bold">
            {if $product.discount_type === 'percentage'}
                <span
                    class="discount-value">{l s='- %percentage%' d='Shop.Theme.Catalog' sprintf=['%percentage%' => $product.discount_percentage_absolute]}</span>
            {else}
                <span class="discount-value">
                    {l s='- %amount%' d='Shop.Theme.Catalog' sprintf=['%amount%' => $product.discount_to_display]}
                </span>
            {/if}
        </div>
        <div class="tag-discount">Promotion</div>
    </div>
{/if}