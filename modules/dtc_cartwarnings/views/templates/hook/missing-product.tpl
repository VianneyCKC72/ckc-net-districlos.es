{**
 * Avertissement produit manquant — markup d'origine (CartController).
 *}
{if !empty($cart_warning_include_css)}
<style>
  .product-message-container { display: contents; }
  .product-message-row { display: contents; }
  @media (max-width: 767px) {
    .product-message-container {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .product-message-row {
      display: flex;
      flex-direction: row;
      gap: 10px;
      align-items: flex-start;
    }
    .product-message-txt {
      width: auto !important;
      flex: 1;
      min-width: 0;
    }
    .product-message-price {
      display: flex !important;
      justify-content: space-between !important;
      flex-direction: row;
      align-items: center;
      gap: 5px;
      width: 100%;
      flex-wrap: wrap;
      margin-top: 10px;
    }
  }
</style>
{/if}
<div class="product-message-container">
  <div class="product-message-row">
    <div class="product-message-img" style="border: 2px solid #dbe6e8;">
      {if $cart_warning.image_url}
        <a href="{$cart_warning.product_url|escape:'html':'UTF-8'}" class="product-image media-middle" title="{$cart_warning.product_name|escape:'html':'UTF-8'}" style="display: block; cursor: pointer;">
          <img src="{$cart_warning.image_url|escape:'html':'UTF-8'}" alt="{$cart_warning.product_name|escape:'html':'UTF-8'}" style="max-width: 100px;" />
        </a>
      {/if}
    </div>
    <div class="product-message-txt" style="width: 60%;">
      <span class="text-red" style="font-size: 14px;">{l s='Attention :' d='Shop.Theme.Customdtc'} {$cart_warning.alert_main|escape:'html':'UTF-8'}{if $cart_warning.alert_extra}<br/><em>{$cart_warning.alert_extra|escape:'html':'UTF-8'}</em>{/if}</span><br/>
      {if $cart_warning.product_name}
        <a href="{$cart_warning.product_url|escape:'html':'UTF-8'}" class="h4 text-black mb-2 d-block" style="text-decoration: none; cursor: pointer;">
          <strong>{$cart_warning.product_name|escape:'html':'UTF-8'}</strong>
        </a>
      {/if}
      {$cart_warning.combination_html nofilter}
    </div>
  </div>
  <div class="product-message-price text-xs-right d-flex flex_cln" style="gap: 5px;">
    {if $cart_warning.price_html}{l s='Prix unitaire :' d='Shop.Theme.Customdtc'} {$cart_warning.price_html} {/if}
    {if $cart_warning.add_to_cart_url}
      <a href="{$cart_warning.add_to_cart_url|escape:'html':'UTF-8'}" class="btn btn-secondary" style="padding: 10px 20px;">{l s='Ajouter au panier' d='Shop.Theme.Customdtc'}</a>
    {/if}
  </div>
</div>
