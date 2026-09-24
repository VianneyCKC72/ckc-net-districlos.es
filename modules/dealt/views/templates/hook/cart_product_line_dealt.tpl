<div class="product-line-grid dealt-service-product">
    <div class="product-line-grid-left" style="width: 70%;">
        <div class="product-details d-flex flex-column">
            <div class="product-line-info">
                <a class="h4 text-black d-block m-0" data-id_customization="{$product.id_customization|intval}" data-product-id-dealt="{$product_id_dealt}">
                    {$product.name}
                </a>
            </div>
            {foreach from=$product.attributes key="attribute" item="value"}
                <div class="product-line-info {$attribute|lower}">
                    <span class="label">{$attribute} </span>
                    <span class="value">{$value}</span>
                </div>
            {/foreach}
            <div class="product-line-info prix-unitaire">
                <span class="label">{l s='Prix unitaire' d='Shop.Theme.Customdealt'} {if $pro.env == 1}HT{else}TTC{/if} </span>
                <span class="value dealt-service-unit-price">{$product.price}</span>
            </div>
        </div>
    </div>
    <div class="product-line-grid-right product-line-actions">
        <div class="d-flex gap-16 align-items-center w-100">
            <div class="price">
                <div class="product-line-info product-price h5 mb-0">
                    <span class="product-price dealt-service-total-price">
                        {if !empty($product.is_gift)}
                            <span class="gift">{l s='Gift' d='Shop.Theme.Checkout'}</span>
                        {else}
                            {if $product.has_discount}
                                <strong>{$product.total} {if $pro.env == 1}HT{/if}</strong>
                            {else}
                                <strong>{$product.total} {if $pro.env == 1}HT{/if}</strong>
                            {/if}
                        {/if}
                    </span>
                </div>
            </div>
        </div>
        <div class="cart-line-product-actions">
            <a class="remove-from-cart" rel="nofollow" href="{$product.remove_from_cart_url}"
                data-link-action="delete-from-cart" data-id-product="{$product.id_product|escape:'javascript'}"
                data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}"
                data-id-customization="{$product.id_customization|default|escape:'javascript'}">
                {if empty($product.is_gift)}
                    <svg width="9" height="14" viewBox="0 0 9 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M3.375 0.5L2.8125 1.15H0.5625C0.225 1.15 0 1.41 0 1.8C0 2.19 0.225 2.45 0.5625 2.45H1.6875H7.3125H8.4375C8.775 2.45 9 2.19 9 1.8C9 1.41 8.775 1.15 8.4375 1.15H6.1875L5.625 0.5H3.375ZM0.5625 3.75V12.2C0.5625 12.915 1.06875 13.5 1.6875 13.5H7.3125C7.93125 13.5 8.4375 12.915 8.4375 12.2V3.75H0.5625ZM2.8125 5.05C3.15 5.05 3.375 5.31 3.375 5.7V11.55C3.375 11.94 3.15 12.2 2.8125 12.2C2.475 12.2 2.25 11.94 2.25 11.55V5.7C2.25 5.31 2.475 5.05 2.8125 5.05ZM6.1875 5.05C6.525 5.05 6.75 5.31 6.75 5.7V11.55C6.75 11.94 6.525 12.2 6.1875 12.2C5.85 12.2 5.625 11.94 5.625 11.55V5.7C5.625 5.31 5.85 5.05 6.1875 5.05Z"
                            fill="black" />
                    </svg>
                {/if}
            </a>
        </div>
    </div>
</div>

{* ====== BLOC TVA RÉDUITE (affiché 1 seule fois) ====== *}
{if isset($dealt_show_tva_block) && $dealt_show_tva_block && (isset($dealt_tva_enabled) && !$dealt_tva_enabled)}
  <div class="dealt-tva-notice dealt-tva-notice--cartline">
    <div class="dealt-tva-notice__text">
      <span class="dealt-tva-title">{l s='Réduisez votre TVA' d='Shop.Theme.Customdealt'}</span>
      <small class="dealt-tva-description">
        {l s='Vous pouvez bénéficier d\'une TVA à 10% soit une réduction de 10% du montant de vos achats !' d='Shop.Theme.Customdealt'}
      </small>
    </div>
    <div class="dealt-tva-actions-wrapper">
      <a href="{$tva_modal_url|default:'#'|escape:'html':'UTF-8'}" class="btn-secondary btn-add dealt-tva-open-popup dealt-tva-btn-desktop">
        {l s='Ajouter la réduction' d='Shop.Theme.Customdealt'}
      </a>
      <div class="dealt-tva-actions-mobile">
        <a href="{$tva_modal_url|default:'#'|escape:'html':'UTF-8'}" class="btn-secondary btn-add dealt-tva-open-popup">
          {l s='Ajouter la réduction' d='Shop.Theme.Customdealt'}
        </a>
        <div class="dealt-tva-price-mobile">
          <span class="product-price dealt-service-total-price">
            {if !empty($product.is_gift)}
              <span class="gift">{l s='Gift' d='Shop.Theme.Checkout'}</span>
            {else}
              <strong>{$product.total} {if $pro.env == 1}HT{/if}</strong>
            {/if}
          </span>
        </div>
        <div class="cart-line-product-actions-mobile">
          <a class="remove-from-cart" rel="nofollow" href="{$product.remove_from_cart_url}"
              data-link-action="delete-from-cart" data-id-product="{$product.id_product|escape:'javascript'}"
              data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}"
              data-id-customization="{$product.id_customization|default|escape:'javascript'}">
            {if empty($product.is_gift)}
              <svg width="9" height="14" viewBox="0 0 9 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M3.375 0.5L2.8125 1.15H0.5625C0.225 1.15 0 1.41 0 1.8C0 2.19 0.225 2.45 0.5625 2.45H1.6875H7.3125H8.4375C8.775 2.45 9 2.19 9 1.8C9 1.41 8.775 1.15 8.4375 1.15H6.1875L5.625 0.5H3.375ZM0.5625 3.75V12.2C0.5625 12.915 1.06875 13.5 1.6875 13.5H7.3125C7.93125 13.5 8.4375 12.915 8.4375 12.2V3.75H0.5625ZM2.8125 5.05C3.15 5.05 3.375 5.31 3.375 5.7V11.55C3.375 11.94 3.15 12.2 2.8125 12.2C2.475 12.2 2.25 11.94 2.25 11.55V5.7C2.25 5.31 2.475 5.05 2.8125 5.05ZM6.1875 5.05C6.525 5.05 6.75 5.31 6.75 5.7V11.55C6.75 11.94 6.525 12.2 6.1875 12.2C5.85 12.2 5.625 11.94 5.625 11.55V5.7C5.625 5.31 5.85 5.05 6.1875 5.05Z"
                    fill="black" />
              </svg>
            {/if}
          </a>
        </div>
      </div>
    </div>
  </div>

    {* Inclure le modal TVA une seule fois *}
  {include file="module:dealt/views/templates/front/_partials/modal_tva.tpl"}
  {if isset($tva_attestation_url)}
    <script>window.tva_attestation_url = '{$tva_attestation_url|escape:'javascript'}';</script>
  {/if}

{/if}

{* ====== BLOC ACTIONS MOBILE (visible uniquement si TVA activée) ====== *}
{if isset($dealt_show_tva_block) && $dealt_show_tva_block && (isset($dealt_tva_enabled) && $dealt_tva_enabled)}
  <div class="dealt-mobile-actions-always">
    <div class="dealt-tva-price-mobile">
      <span class="product-price dealt-service-total-price">
        {if !empty($product.is_gift)}
          <span class="gift">{l s='Gift' d='Shop.Theme.Checkout'}</span>
        {else}
          <strong>{$product.total} {if $pro.env == 1}HT{/if}</strong>
        {/if}
      </span>
    </div>
    <div class="cart-line-product-actions-mobile">
      <a class="remove-from-cart" rel="nofollow" href="{$product.remove_from_cart_url}"
          data-link-action="delete-from-cart" data-id-product="{$product.id_product|escape:'javascript'}"
          data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}"
          data-id-customization="{$product.id_customization|default|escape:'javascript'}">
        {if empty($product.is_gift)}
          <svg width="9" height="14" viewBox="0 0 9 14" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M3.375 0.5L2.8125 1.15H0.5625C0.225 1.15 0 1.41 0 1.8C0 2.19 0.225 2.45 0.5625 2.45H1.6875H7.3125H8.4375C8.775 2.45 9 2.19 9 1.8C9 1.41 8.775 1.15 8.4375 1.15H6.1875L5.625 0.5H3.375ZM0.5625 3.75V12.2C0.5625 12.915 1.06875 13.5 1.6875 13.5H7.3125C7.93125 13.5 8.4375 12.915 8.4375 12.2V3.75H0.5625ZM2.8125 5.05C3.15 5.05 3.375 5.31 3.375 5.7V11.55C3.375 11.94 3.15 12.2 2.8125 12.2C2.475 12.2 2.25 11.94 2.25 11.55V5.7C2.25 5.31 2.475 5.05 2.8125 5.05ZM6.1875 5.05C6.525 5.05 6.75 5.31 6.75 5.7V11.55C6.75 11.94 6.525 12.2 6.1875 12.2C5.85 12.2 5.625 11.94 5.625 11.55V5.7C5.625 5.31 5.85 5.05 6.1875 5.05Z"
                fill="black" />
          </svg>
        {/if}
      </a>
    </div>
  </div>
{/if}

