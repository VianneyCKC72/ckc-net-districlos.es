{block name='product_prices'}
    {if !$priceDisplay || $priceDisplay == 2}
        {assign var='productPrice' value=AdvancedPack::getPackPrice($product.id, true, true, true, $priceDisplayPrecision, $packAttributesList, $packQuantityList, $packExcludeList, true)}
        {assign var='productPriceWithoutReduction' value=AdvancedPack::getPackPrice($product.id, true, false, true, $priceDisplayPrecision, $packAttributesList, $packQuantityList, $packExcludeList, true)}
    {elseif $priceDisplay == 1}
        {assign var='productPrice' value=AdvancedPack::getPackPrice($product.id, false, true, true, $priceDisplayPrecision, $packAttributesList, $packQuantityList, $packExcludeList, true)}
        {assign var='productPriceWithoutReduction' value=AdvancedPack::getPackPrice($product.id, false, false, true, $priceDisplayPrecision, $packAttributesList, $packQuantityList, $packExcludeList, true)}
    {/if}

    {assign var='packReductionAmount' value=$productPriceWithoutReduction-$productPrice}
    {if $productPriceWithoutReduction > 0}
        {assign var='packReductionPercent' value=($packReductionAmount*100)/$productPriceWithoutReduction}
    {else}
        {assign var='packReductionPercent' value=0}
    {/if}

    {* Used by theme override of product-prices/has-discount.tpl to display percentage for packs *}
    {assign var='ap5_pack_reduction_percent' value=$packReductionPercent|round:0}

    {include file='catalog/_partials/product-prices.tpl'}
{/block}
