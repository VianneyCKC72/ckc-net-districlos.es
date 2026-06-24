{if $product.has_discount}
    <div class="product-discount has-discount-header">
        <div class="tag-discount">{l s='Promotion' d='Shop.Theme.Customcatalog'}</div>
        <div class="">
            {l s='Bénéficiez de' d='Shop.Theme.Customcatalog'}
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
            {l s='directement [1]dans votre panier[/1]' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>'] d='Shop.Theme.Customcatalog'}
        </div>

    </div>
{/if}