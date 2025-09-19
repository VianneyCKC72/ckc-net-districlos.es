{if isset($productPromos)}
    {foreach $productPromos as $productPromo}
        {if isset($productPromo['text'])}
            <div class="has-discount d-flex align-items-center mb-5">
                {html_entity_decode($productPromo['text']) nofilter}
            </div>
        {/if}
    {/foreach}
{/if}