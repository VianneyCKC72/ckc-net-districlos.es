{if $product.has_discount}
    <div class="product-discount tag-discount">
        {* Réduction à commentée - si affichage de price_old.tpl pour les SOLDES *}
        <div class="font-weight-bold">
            {if isset($ap5_pack_reduction_percent) && $ap5_pack_reduction_percent > 0}
                <span class="discount-value">
                    {l s='- %percentage%' d='Shop.Theme.Catalog' sprintf=['%percentage%' => $ap5_pack_reduction_percent|cat:'%']}
                </span>
            {elseif $product.discount_type === 'percentage'}
                <span
                    class="discount-value">{l s='- %percentage%' d='Shop.Theme.Catalog' sprintf=['%percentage%' => $product.discount_percentage_absolute]}</span>
            {else}
                <span class="discount-value">
                    {l s='- %amount%' d='Shop.Theme.Catalog' sprintf=['%amount%' => $product.discount_to_display]}
                </span>
            {/if}
        </div>
        {* <div class="tag-discount">Promotion</div> *}
    </div>
{/if}