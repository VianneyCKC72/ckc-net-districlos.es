{if isset($productPromos)}
    {foreach $productPromos as $productPromo}
        {if isset($productPromo['text'])}
            <div class="has-discount d-flex align-items-center">
                    {$productPromo['text'] nofilter}
            </div>
        {/if}
    {/foreach}
{/if}