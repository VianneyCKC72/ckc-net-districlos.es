{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

{if $product.id_product == 1215 || $product.id_product == 1213 || $product.id_product == 1302 || $product.id_product == 1223 || $product.id_product == 1285 || $product.id_product == 1305 || $product.id_product == 1306 }
    {if isset($custom_product_message) && sizeof($custom_product_message) > 0 && isset($custom_product_message[$product.id_product]) && $custom_product_message[$product.id_product] != ''}
        <div class="custom-alert alert alert-warning">
            {$custom_product_message[$product.id_product] nofilter}
        </div>
    {/if}
{/if}

<div class="product-line-grid">
    <!--  product line left content: image-->
    <div class="product-line-grid-left">
        <a href="{$product.url}" class="product-image media-middle">
            {if $product.default_image}
                <picture>
                    {if !empty($product.default_image.bySize.cart_default.sources.avif)}
                        <source srcset="{$product.default_image.bySize.cart_default.sources.avif}"
                                type="image/avif">{/if}
                    {if !empty($product.default_image.bySize.cart_default.sources.webp)}
                        <source srcset="{$product.default_image.bySize.cart_default.sources.webp}"
                                type="image/webp">{/if}
                    <img src="{$product.default_image.bySize.cart_default.url}" alt="{$product.name|escape:'quotes'}"
                         loading="lazy">
                </picture>
            {else}
                <picture>
                    {if !empty($urls.no_picture_image.bySize.cart_default.sources.avif)}
                        <source srcset="{$urls.no_picture_image.bySize.cart_default.sources.avif}"
                                type="image/avif">{/if}
                    {if !empty($urls.no_picture_image.bySize.cart_default.sources.webp)}
                        <source srcset="{$urls.no_picture_image.bySize.cart_default.sources.webp}"
                                type="image/webp">{/if}
                    <img src="{$urls.no_picture_image.bySize.cart_default.url}" loading="lazy"/>
                </picture>
            {/if}

            {if $product.has_discount}
                {if $product.discount_type === 'percentage'}
                    <span class="discount discount-percentage">
              -{$product.discount_percentage_absolute}
            </span>
                {else}
                    <span class="discount discount-amount">
              -{$product.discount_to_display}
            </span>
                {/if}
            {/if}
        </a>

        <div class="product-details d-flex flex-column">
            <div class="product-line-info">
                <a class="h4 text-black mb-2 d-block" href="{$product.url}"
                   data-id_customization="{$product.id_customization|intval}">{$product.name}</a>
            </div>

            {foreach from=$product.attributes key="attribute" item="value"}
                <div class="product-line-info {$attribute|lower}">
                    <span class="label">{$attribute} </span>
                    <span class="value">{$value}</span>
                </div>
            {/foreach}

            <div class="product-line-info prix-unitaire">
                <span class="label">Prix unitaire </span>
                <span class="value">{$product.price} {if $env_pro == 1}HT{else}TTC{/if}</span>
            </div>
            {if is_array($product.customizations) && $product.customizations|count}
                <br>
                {block name='cart_detailed_product_line_customization'}
                    {foreach from=$product.customizations item="customization"}
                        <a href="#" data-toggle="modal"
                           data-target="#product-customizations-modal-{$customization.id_customization}">{l s='Product customization' d='Shop.Theme.Catalog'}</a>
                        <div class="modal fade customization-modal js-customization-modal"
                             id="product-customizations-modal-{$customization.id_customization}" tabindex="-1"
                             role="dialog"
                             aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="{l s='Close' d='Shop.Theme.Global'}">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title">{l s='Product customization' d='Shop.Theme.Catalog'}</h4>
                                    </div>
                                    <div class="modal-body">
                                        {foreach from=$customization.fields item="field"}
                                            <div class="product-customization-line row">
                                                <div class="col-sm-3 col-xs-4 label">
                                                    {$field.label}
                                                </div>
                                                <div class="col-sm-9 col-xs-8 value">
                                                    {if $field.type == 'text'}
                                                        {if (int)$field.id_module}
                                                            {$field.text nofilter}
                                                        {else}
                                                            {$field.text}
                                                        {/if}
                                                    {elseif $field.type == 'image'}
                                                        <img src="{$field.image.small.url}" loading="lazy">
                                                    {/if}
                                                </div>
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                {/block}
            {/if}
        </div>
    </div>

    <!--  product line right content: actions (quantity, delete), price -->
    <div class="product-line-grid-right product-line-actions">
        <div class="d-flex gap-16 align-items-center w-100 justify-content-between">
            <div class="qty">
                {if !empty($product.is_gift)}
                    <span class="gift-quantity">{$product.quantity}</span>
                {else}
                    <div class="custom-quantity-selector">
                        <button type="button" class="btn-quantity decrease">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                 class="lucide lucide-minus">
                                <path d="M5 12h14"/>
                            </svg>
                        </button>
                        <input type="number" name="qty" id="quantity_wanted" inputmode="numeric" pattern="[0-9]*"
                               data-down-url="{$product.down_quantity_url}"
                               data-up-url="{$product.up_quantity_url}"
                               data-update-url="{$product.update_quantity_url}"
                               data-product-id="{$product.id_product}"
                               data-valueqty={$product.quantity}
                               {if $product.quantity_wanted}
                               value="{$product.quantity_wanted}"
                               min="{$product.minimal_quantity}"
                                {else}
                               value="1" min="1" {/if} class="quantity-input js-cart-line-product-quantity"
                               aria-label="{l s='Quantity' d='Shop.Theme.Actions'}">
                        <button type="button" class="btn-quantity increase">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                 class="lucide lucide-plus">
                                <path d="M5 12h14"/>
                                <path d="M12 5v14"/>
                            </svg>
                        </button>
                    </div>
                    {* <input class="js-cart-line-product-quantity" data-down-url="{$product.down_quantity_url}"
                          data-up-url="{$product.up_quantity_url}" data-update-url="{$product.update_quantity_url}"
                          data-product-id="{$product.id_product}" type="number" inputmode="numeric" pattern="[0-9]*"
                          value="{$product.quantity}" name="product-quantity-spin"
                          aria-label="{l s='%productName% product quantity field' sprintf=['%productName%' => $product.name] d='Shop.Theme.Checkout'}" /> *}
                {/if}
            </div>
            <div class="price">
                <div class="product-line-info product-price h5 mb-0">
                  <span class="product-price">
                        {if !empty($product.is_gift)}
                            <span class="gift">{l s='Gift' d='Shop.Theme.Checkout'}</span>
                           {else}
                             {if $product.has_discount}
                                    <strong>{$product.total}</strong>
                              {else}
                                <strong>{$product.total}</strong>
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
                                fill="black"/>
                    </svg>
                {/if}
            </a>

            {block name='hook_cart_extra_product_actions'}
                {hook h='displayCartExtraProductActions' product=$product}
            {/block}

        </div>
    </div>
</div>
