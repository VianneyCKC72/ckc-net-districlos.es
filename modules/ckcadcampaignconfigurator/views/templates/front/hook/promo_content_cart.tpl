<div class="checkout cart-promos js-cart-promos card-block">
    {if isset($cartPromos)}
        {foreach $cartPromos as $cartPromo}
            {if isset($cartPromo['textFinal'])}
                <div style="color: {$cartPromo['text_color']}; display: flex; align-items: center; gap: 8px;">
                    <img src="/themes/classic/assets/img/etiquette-soldes.svg" alt="Étiquette soldes" style="width: 24px; height: 25px; flex-shrink: 0;">
                    {html_entity_decode($cartPromo['textFinal']) nofilter}
                </div>
            {/if}
        {/foreach}
    {/if}
</div>