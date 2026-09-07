{assign var='parts' value=','|explode:$col_price}
<div class="ap5-ml-col ap5-ml-col--{$col_variant}">
  {if $col_discount}
    <div class="ap5-ml-col__promo product-discount">
      <span class="regular-price">{$col_regular}</span>
      <span class="tag-discount"><span class="discount-value">-{$col_percent}%</span></span>
    </div>
  {/if}
  <div class="product-price price{if $col_discount} has-discount{/if}"{if $col_itemprop} itemprop="price"{/if}>
    <span class="price-integer">{$parts[0]}</span><span class="price-decimal">,{$parts[1]|default:''}</span>
  </div>
  <div class="ap5-ml-col__label">{$col_label}</div>
</div>
