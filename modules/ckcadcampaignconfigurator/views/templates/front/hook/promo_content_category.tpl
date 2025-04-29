{if isset($categoryPromos)}
    {foreach $categoryPromos as $categoryPromo}
        {if isset($categoryPromo['categoryText'])}
            <div style="background-color: {$categoryPromo['categoryColor']};" class="has-discount d-flex align-items-center">
                    {$categoryPromo['categoryText'] nofilter}
            </div>
        {/if}
    {/foreach}
{/if}