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
            <svg width="28" height="20" viewBox="0 0 28 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                d="M1.39569 0.16902C1.30087 0.167615 1.20672 0.185976 1.11873 0.223036C1.03074 0.260096 0.950649 0.315116 0.88312 0.384899C0.815592 0.454682 0.76197 0.537836 0.72537 0.629529C0.68877 0.721221 0.669922 0.819624 0.669922 0.919017C0.669922 1.01841 0.68877 1.11681 0.72537 1.20851C0.76197 1.3002 0.815592 1.38335 0.88312 1.45314C0.950649 1.52292 1.03074 1.57794 1.11873 1.615C1.20672 1.65206 1.30087 1.67042 1.39569 1.66901H2.71037C3.05763 1.66901 3.3471 1.92048 3.4129 2.27839L3.55545 3.05963L5.09375 11.5274C5.37193 13.0551 6.65618 14.169 8.14053 14.169H17.0722C18.5565 14.169 19.8413 13.0556 20.1189 11.5274L21.6572 3.05963C21.677 2.95122 21.6736 2.83957 21.6475 2.73266C21.6213 2.62575 21.5729 2.52621 21.5058 2.44115C21.4387 2.35608 21.3545 2.28759 21.2593 2.24055C21.164 2.19351 21.06 2.16908 20.9547 2.16901H4.84964L4.81889 1.99811C4.62658 0.941783 3.73656 0.16902 2.71037 0.16902H1.39569ZM5.12264 3.66901H20.0901L18.713 11.2471C18.5622 12.0769 17.8786 12.669 17.0722 12.669H8.14053C7.33438 12.669 6.65124 12.0769 6.49974 11.2471V11.2461L5.12264 3.66901ZM9.267 15.169C8.88744 15.169 8.52342 15.327 8.25503 15.6083C7.98664 15.8896 7.83586 16.2711 7.83586 16.669C7.83586 17.0668 7.98664 17.4483 8.25503 17.7296C8.52342 18.0109 8.88744 18.1689 9.267 18.1689C9.64657 18.1689 10.0106 18.0109 10.279 17.7296C10.5474 17.4483 10.6982 17.0668 10.6982 16.669C10.6982 16.2711 10.5474 15.8896 10.279 15.6083C10.0106 15.327 9.64657 15.169 9.267 15.169ZM15.9457 15.169C15.5661 15.169 15.2021 15.327 14.9337 15.6083C14.6653 15.8896 14.5145 16.2711 14.5145 16.669C14.5145 17.0668 14.6653 17.4483 14.9337 17.7296C15.2021 18.0109 15.5661 18.1689 15.9457 18.1689C16.3253 18.1689 16.6893 18.0109 16.9577 17.7296C17.2261 17.4483 17.3768 17.0668 17.3768 16.669C17.3768 16.2711 17.2261 15.8896 16.9577 15.6083C16.6893 15.327 16.3253 15.169 15.9457 15.169Z"
                fill="white" />
              <rect x="13.3298" y="5.83057" width="13" height="13" rx="6.5" fill="white" />
              <rect x="13.3298" y="5.83057" width="13" height="13" rx="6.5" stroke="#7CBA49" stroke-width="2" />
              <path
                d="M22.3798 12.9408H20.4898V14.8808H19.1698V12.9408H17.2798V11.7208H19.1698V9.78076H20.4898V11.7208H22.3798V12.9408Z"
                fill="#7CBA49" />
            </svg>

            {l s='Add to cart' d='Shop.Theme.Actions'}
          </button>
        </div>

        {hook h='displayProductActions' product=$product}
      </div>
    {/block}

    {* {block name='product_availability'}
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


    {/block} *}

    {block name='product_minimal_quantity'}
      <p class="product-minimal-quantity js-product-minimal-quantity">
        {if $product.minimal_quantity > 1}
          {l
                                        s='The minimum purchase order quantity for the product is %quantity%.'
                                        d='Shop.Theme.Checkout'
                                        sprintf=['%quantity%' => $product.minimal_quantity]
                                        }
        {/if}
      </p>
    {/block}
  {/if}
</div>