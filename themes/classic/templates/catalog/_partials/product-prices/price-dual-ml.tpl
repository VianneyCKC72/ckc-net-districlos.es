{if $has_campaign_discount}
  {assign var='d_price' value=$campaignProductDiscount.price}
  {assign var='d_regular' value=$campaignProductDiscount.regular_price}
  {assign var='d_discount' value=true}
  {assign var='d_percent' value=$campaignProductDiscount.percentage}
{else}
  {assign var='d_price' value=$product.price}
  {assign var='d_regular' value=$product.regular_price}
  {assign var='d_discount' value=$product.has_discount}
  {assign var='d_percent' value=$ap5_pack_reduction_percent|default:0}
{/if}
<div class="d-flex product-prices ap5-price-dual">
  {include file='catalog/_partials/product-prices/price-ml-col.tpl'
    col_price=$d_price
    col_regular=$d_regular
    col_discount=$d_discount
    col_percent=$d_percent
    col_variant='total'
    col_itemprop=true
  }
  <div class="ap5-price-dual__sep" aria-hidden="true"></div>
  {include file='catalog/_partials/product-prices/price-ml-col.tpl'
    col_price=$packMlUnitPrice
    col_regular=$packMlUnitRegularPrice
    col_discount=$d_discount
    col_percent=$d_percent
    col_variant='unit'
    col_itemprop=false
  }
</div>
