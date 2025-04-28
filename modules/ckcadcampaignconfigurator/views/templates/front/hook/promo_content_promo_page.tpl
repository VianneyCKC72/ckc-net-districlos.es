{if isset($promoPagePromos)}
    <div class="promo-page-promo-container">
        {foreach $promoPagePromos as $promoPagePromo}
            {if isset($promoPagePromo['promoPageText']) && "" !== $promoPagePromo['promoPageText']}
                <div class="promo-page-promo-content">
                    {$promoPagePromo['promoPageText'] nofilter}
                </div>
            {/if}
        {/foreach}
    </div>
{/if}