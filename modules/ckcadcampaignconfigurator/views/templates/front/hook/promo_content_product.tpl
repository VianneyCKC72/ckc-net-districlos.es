{if isset($productPromos)}
    {foreach $productPromos as $productPromo}
        {if isset($productPromo['text'])}
            <div class="has-discount d-flex align-items-center {if $productPromo@last}mb-4{else}mb-1{/if}">
                {html_entity_decode($productPromo['text']) nofilter}
            </div>
        {/if}
    {/foreach}
{/if}