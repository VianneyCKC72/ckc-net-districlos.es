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
<section id="order-summary-content" class="page-content page-order-confirmation">
  <div class="row">
    <div class="col-md-12">
      <h2 class="h2 black">{l s='Récapitulatif' d='Shop.Theme.Checkout'}</h2>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
      <h4 class="h4">
        {l s='Addresses' d='Shop.Theme.Checkout'}
        <span class="step-edit step-to-addresses js-edit-addresses"><i class="material-icons edit">mode_edit</i>
          {l s='edit' d='Shop.Theme.Actions'}</span>
      </h4>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6">
      <div class="card-sand">
        <div class="card-block">
          <h4 class="h5 black addresshead">{l s='Your Delivery Address' d='Shop.Theme.Checkout'}</h4>
          {$customer.addresses[$cart.id_address_delivery]['formatted'] nofilter}
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card-sand">
        <div class="card-block">
          <h4 class="h5 black addresshead">{l s='Your Invoice Address' d='Shop.Theme.Checkout'}</h4>
          {$customer.addresses[$cart.id_address_invoice]['formatted'] nofilter}
        </div>
      </div>
    </div>
  </div>

  {if !$cart.is_virtual}
    <div class="row mt-4">
      <div class="col-md-12">
        <h4 class="h4">
          {l s='Shipping Method' d='Shop.Theme.Checkout'}
          <span class="step-edit step-to-delivery js-edit-delivery"><i class="material-icons edit">mode_edit</i>
            {l s='edit' d='Shop.Theme.Actions'}</span>
        </h4>

        <div class="card-sand summary-selected-carrier">
          <div class="row">
            {if $selected_delivery_option.logo}
              <div class="col-md-2">
                <div class="logo-container">
                  <img src="{$selected_delivery_option.logo}" alt="{$selected_delivery_option.name}" loading="lazy">
                </div>
              </div>
            {/if}
            <div class="col-md-auto">
              <span class="carrier-name">{$selected_delivery_option.name}</span>
            </div>
            <div class="col-md-4">
              <span class="carrier-delay">{$selected_delivery_option.delay nofilter}</span>
            </div>
            <div class="col-md-2">
              <span class="carrier-price">{$selected_delivery_option.price}</span>
            </div>
          </div>
        </div>
        {if $is_recyclable_packaging}
          <em>{l s='You have given permission to receive your order in recycled packaging.' d="Shop.Theme.Customeraccount"}</em>
        {/if}
      </div>
    </div>
  {/if}

  <div class="row mt-4">
    {block name='order_confirmation_table'}
      {include file='checkout/_partials/order-final-summary-table.tpl'
        products=$cart.products
        products_count=$cart.products_count
        subtotals=$cart.subtotals
        totals=$cart.totals
        labels=$cart.labels
        add_product_link=true
      }
    {/block}
  </div>
</section>