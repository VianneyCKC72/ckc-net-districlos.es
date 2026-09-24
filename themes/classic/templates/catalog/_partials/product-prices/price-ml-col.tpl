{include file='catalog/_partials/product-prices/price-dec.tpl'}
{assign var='parts' value=$ckc_price_dec|explode:$col_price}
<div class="ap5-ml-col ap5-ml-col--{$col_variant}">
  {if $col_discount}
    <div class="ap5-ml-col__promo product-discount">
      <span class="regular-price">{$col_regular}</span>
      <span class="tag-discount"><span class="discount-value">-{$col_percent}%</span></span>
    </div>
  {/if}
  <div class="product-price price{if $col_discount} has-discount{/if}"{if $col_itemprop} itemprop="price"{/if}>
    <span class="price-integer">{$parts[0]}</span><span class="price-decimal">{$ckc_price_dec}{$parts[1]|default:''}</span>
  </div>
  <div class="ap5-ml-col__label">
    {if isset($pro) && $pro.env == 1}
      {if $col_variant == 'unit'}{l s='HT/Prix au mL' d='Shop.Theme.Customcatalog'}{else}{l s='HT/Prix total' d='Shop.Theme.Customcatalog'}{/if}
    {else}
      {if $col_variant == 'unit'}{l s='TTC/Prix au mL' d='Shop.Theme.Customcatalog'}{else}{l s='TTC/Prix total' d='Shop.Theme.Customcatalog'}{/if}
    {/if}
  </div>
</div>
