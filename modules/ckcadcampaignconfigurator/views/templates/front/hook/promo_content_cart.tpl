<div class="checkout cart-promos js-cart-promos card-block">
    {if isset($cartPromos)}
        {foreach $cartPromos as $cartPromo}
            {if isset($cartPromo['textFinal'])}
                <div style="color: {$cartPromo['text_color']};">
                    {html_entity_decode($cartPromo['textFinal']) nofilter}
                </div>
            {/if}
        {/foreach}
    {/if}
</div>