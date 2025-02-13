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
<div class="product-add-to-cart js-product-add-to-cart">
  {if !$configuration.is_catalog}
    {block name='product_quantity'}
      <div class="product-quantity">
        <div class="qty">
          {*<input
            type="number"
            name="qty"
            id="quantity_wanted"
            inputmode="numeric"
            pattern="[0-9]*"
            {if $product.quantity_wanted}
              value="{$product.quantity_wanted}"
              min="{$product.minimal_quantity}"
            {else}
              value="1"
              min="1"
            {/if}
            class="input-group"
            aria-label="{l s='Quantity' d='Shop.Theme.Actions'}"
          >*}
          <div class="custom-quantity-selector">
            <button type="button" class="btn-quantity decrease">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-minus">
                <path d="M5 12h14" />
              </svg>
            </button>
            <input type="number" name="qty" id="quantity_wanted" inputmode="numeric" pattern="[0-9]*"
              {if $product.quantity_wanted} value="{$product.quantity_wanted}" min="{$product.minimal_quantity}" 
              {else}
              value="1" min="1" {/if} class="quantity-input" aria-label="{l s='Quantity' d='Shop.Theme.Actions'}">
            <button type="button" class="btn-quantity increase">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-plus">
                <path d="M5 12h14" />
                <path d="M12 5v14" />
              </svg>
            </button>
          </div>
        </div>

        <div class="add">
          <button class="btn btn-secondary add-to-cart" data-button-action="add-to-cart" type="submit"
            {if !$product.add_to_cart_url} disabled {/if}>

            <svg width="28" height="20" version="1.1" id="Calque_1" xmlns="http://www.w3.org/2000/svg"
              xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 28 20" xml:space="preserve"
              fill="currentColor">
              <g>
                <path class="st0" d="M9.3,15.2c-0.4,0-0.7,0.2-1,0.4c-0.3,0.3-0.4,0.7-0.4,1.1c0,0.4,0.2,0.8,0.4,1.1c0.3,0.3,0.6,0.4,1,0.4
                  c0.4,0,0.7-0.2,1-0.4c0.3-0.3,0.4-0.7,0.4-1.1c0-0.4-0.2-0.8-0.4-1.1C10,15.3,9.6,15.2,9.3,15.2z" />
                <path class="st0" d="M17,15.6c-0.3-0.3-0.6-0.4-1-0.4c-0.2,0-0.5,0.1-0.7,0.2c0.5,0.8,1.2,1.4,2,1.8c0.1-0.2,0.1-0.3,0.1-0.5
                  C17.4,16.3,17.2,15.9,17,15.6z" />
                <path class="st0" d="M12.6,14.2c-0.1-0.5-0.2-1-0.2-1.5H8.1c-0.8,0-1.5-0.6-1.6-1.4v0L5.1,3.7h15l-0.2,1.2c0.5,0,1,0.1,1.4,0.1
                  l0.3-1.9c0-0.1,0-0.2,0-0.3c0-0.1-0.1-0.2-0.1-0.3c-0.1-0.1-0.2-0.2-0.2-0.2c-0.1,0-0.2-0.1-0.3-0.1H4.8l0-0.2
                  C4.6,0.9,3.7,0.2,2.7,0.2H1.4c-0.1,0-0.2,0-0.3,0.1C1,0.3,1,0.3,0.9,0.4C0.8,0.5,0.8,0.5,0.7,0.6c0,0.1-0.1,0.2-0.1,0.3
                  c0,0.1,0,0.2,0.1,0.3c0,0.1,0.1,0.2,0.2,0.2C1,1.5,1,1.6,1.1,1.6c0.1,0,0.2,0.1,0.3,0.1h1.3c0.3,0,0.6,0.3,0.7,0.6l0.1,0.8l1.5,8.5
                  c0.3,1.5,1.6,2.6,3,2.6H12.6z" />
              </g>
              <path class="st0" d="M19.8,6.5c-3.2,0-5.8,2.6-5.8,5.8s2.6,5.8,5.8,5.8c3.2,0,5.8-2.6,5.8-5.8S23,6.5,19.8,6.5z M22.4,12.9h-1.9v1.9
                h-1.3v-1.9h-1.9v-1.2h1.9V9.8h1.3v1.9h1.9V12.9z" />
            </svg>
            {l s='Add to cart' d='Shop.Theme.Actions'}
          </button>
        </div>

        {hook h='displayProductActions' product=$product}
      </div>
    {/block}

    {*{block name='product_availability'}
      <span id="product-availability" class="js-product-availability">
        {if $product.show_availability && $product.availability_message}
          {if $product.availability == 'available'}
            <i class="material-icons rtl-no-flip product-available">&#xE5CA;</i>
          {elseif $product.availability == 'last_remaining_items'}
            <i class="material-icons product-last-items">&#xE002;</i>
          {else}
            <i class="material-icons product-unavailable">&#xE14B;</i>
          {/if}
          {$product.availability_message}
        {/if}
      </span>
    {/block}*}

    {block name='product_minimal_quantity'}
      <p class="product-minimal-quantity js-product-minimal-quantity">
        {if $product.minimal_quantity > 1}
          {l s='The minimum purchase order quantity for the product is %quantity%.' d='Shop.Theme.Checkout' sprintf=['%quantity%' => $product.minimal_quantity]}
        {/if}
      </p>
    {/block}
  {/if}
</div>