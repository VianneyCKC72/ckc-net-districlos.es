{if isset($productPromos)}
    {foreach $productPromos as $productPromo}
        {if isset($productPromo['text'])}
            <div class="has-discount d-flex align-items-center {if !empty($showProductCountdowns) && !empty($productPromo['show_countdown']) && !empty($productPromo['countdown_end_timestamp'])}ckc-product-promo--countdown{/if} {if $productPromo@last}mb-4{else}mb-1{/if}">
                {html_entity_decode($productPromo['text']) nofilter}
                {if !empty($showProductCountdowns) && !empty($productPromo['show_countdown']) && !empty($productPromo['countdown_end_timestamp'])}
                    <div class="ckc-product-countdown" data-ckc-countdown-end="{$productPromo['countdown_end_timestamp']|escape:'htmlall':'UTF-8'}" role="timer">
                        <span>{l s='Fin dans' d='Shop.Theme.Customdtc'}</span>
                        <strong>
                            <span data-countdown-days>00</span>{l s='j' d='Shop.Theme.Customdtc'} :
                            <span data-countdown-hours>00</span>{l s='h' d='Shop.Theme.Customdtc'} :
                            <span data-countdown-minutes>00</span>{l s='min' d='Shop.Theme.Customdtc'} :
                            <span data-countdown-seconds>00</span>{l s='sec' d='Shop.Theme.Customdtc'}
                        </strong>
                    </div>
                {/if}
            </div>
        {/if}
    {/foreach}
{/if}