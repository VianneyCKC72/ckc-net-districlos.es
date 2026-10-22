{block name='product_prices'}
    {if !$priceDisplay || $priceDisplay == 2}
        {assign var='productPrice' value=AdvancedPack::getPackPrice($product.id, true, true, true, $priceDisplayPrecision, $packAttributesList, $packQuantityList, $packExcludeList, true)}
        {assign var='productPriceWithoutReduction' value=AdvancedPack::getPackPrice($product.id, true, false, true, $priceDisplayPrecision, $packAttributesList, $packQuantityList, $packExcludeList, true)}
    {elseif $priceDisplay == 1}
        {assign var='productPrice' value=AdvancedPack::getPackPrice($product.id, false, true, true, $priceDisplayPrecision, $packAttributesList, $packQuantityList, $packExcludeList, true)}
        {assign var='productPriceWithoutReduction' value=AdvancedPack::getPackPrice($product.id, false, false, true, $priceDisplayPrecision, $packAttributesList, $packQuantityList, $packExcludeList, true)}
    {/if}

    {* Badge pack uniquement si remise pack/ML homogène — pas de moyenne des promos produits *}
    {assign var='ap5_pack_reduction_percent' value=AdvancedPack::getPackLevelDisplayPercent($product.id)}

    {include file='catalog/_partials/product-prices.tpl'}
{/block}
