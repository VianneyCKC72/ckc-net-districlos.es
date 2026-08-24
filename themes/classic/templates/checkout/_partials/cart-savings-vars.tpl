{* Calcule les économies panier : remises catalogue (prix barrés) + règles panier.
   Expose $cart_catalog_savings et $cart_total_savings au template parent. *}
{assign var='_catalog_savings' value=0}
{foreach from=$cart.products item=p}
  {if $p.has_discount && empty($p.is_gift)}
    {assign var='_catalog_savings' value=$_catalog_savings + ($p.regular_price_amount - $p.price_amount) * $p.quantity}
  {/if}
{/foreach}
{assign var='cart_catalog_savings' value=$_catalog_savings scope='parent'}
{assign var='cart_total_savings' value=$_catalog_savings + ($cart.subtotals.discounts.amount|default:0) scope='parent'}
