{if isset($devisPromos)}
    {foreach $devisPromos as $devisPromo}
        {if isset($devisPromo['textDevis']) && "" !== $devisPromo['textDevis']}
            <div style="background-color: {$devisPromo['colorDevis']};" class="bloc_promo_devis">
                    {$devisPromo['textDevis'] nofilter}
            </div>
        {/if}
    {/foreach}
{/if}