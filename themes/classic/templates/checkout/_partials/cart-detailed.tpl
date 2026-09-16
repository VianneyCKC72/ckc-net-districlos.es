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
{block name='cart_detailed_product'}
  <div class="cart-overview js-cart" data-refresh-url="{url entity='cart' params=['ajax' => true, 'action' => 'refresh']}">
    {if $cart.products}
    <ul class="cart-items">
      {foreach from=$cart.products item=product}
          {assign var="cart_line_key" value="{$product.id_product}_{$product.id_product_attribute}"}
          {if isset($custom_product_message[$cart_line_key]) && $custom_product_message[$cart_line_key] != ''}
              {$custom_product_message[$cart_line_key] nofilter}
          {elseif isset($custom_product_message) && isset($custom_product_message[$product.id_product]) && $custom_product_message[$product.id_product] != ''}
              {$custom_product_message[$product.id_product] nofilter}
          {/if}
        <li class="cart-item{if !empty($product.dtc_dealt_is_service) || (isset($product.id_category_default) && $product.id_category_default == 369)} dtc-dealt-service-cart-item{/if}">
          {block name='cart_detailed_product_line'}
            {* Appeler le hook displayCartProductLine *}
            {assign var="customProductLine" value={hook h='displayCartProductLine' product=$product pro=$pro urls=$urls}}
            {if $customProductLine}
              {$customProductLine nofilter}
            {else}
              {* Template par défaut si le hook ne retourne rien *}
              {include file='checkout/_partials/cart-detailed-product-line.tpl' product=$product}
            {/if}
          {/block}
          {hook h='displayAfterCartProductLine' product=$product}
        </li>
        {if is_array($product.customizations) && $product.customizations|count >1}<hr>{/if}
      {/foreach}
    </ul>
    {else}
      <span class="no-items">{l s='There are no more items in your cart' d='Shop.Theme.Checkout'}</span>
    {/if}
  </div>
{/block}
