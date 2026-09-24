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
{if isset($pro) && $pro.env == 1}
  {capture assign='tax_prefix'}{l s='HT' d='Shop.Theme.Customcatalog'}{/capture}
{else}
  {capture assign='tax_prefix'}{l s='TTC' d='Shop.Theme.Customcatalog'}{/capture}
{/if}
{capture assign='label_total'}{$tax_prefix}/{l s='Prix total' d='Shop.Theme.Customcatalog'}{/capture}
{capture assign='label_unit'}{$tax_prefix}/{l s='Prix au mL' d='Shop.Theme.Customcatalog'}{/capture}

<div class="d-flex product-prices ap5-price-dual">
  {include file='catalog/_partials/product-prices/price-ml-col.tpl'
    col_price=$d_price
    col_regular=$d_regular
    col_discount=$d_discount
    col_percent=$d_percent
    col_label=$label_total
    col_variant='total'
    col_itemprop=true
  }
  <div class="ap5-price-dual__sep" aria-hidden="true"></div>
  {include file='catalog/_partials/product-prices/price-ml-col.tpl'
    col_price=$packMlUnitPrice
    col_regular=$packMlUnitRegularPrice
    col_discount=$d_discount
    col_percent=$d_percent
    col_label=$label_unit
    col_variant='unit'
    col_itemprop=false
  }
</div>
