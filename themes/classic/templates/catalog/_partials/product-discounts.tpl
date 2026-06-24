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
{if $product.quantity_discounts}

  {if $pro.env}
    <section class="product-discounts js-product-discounts mt-4">
      <span class="h6">{l s='Le prix au volume' d='Shop.Theme.Catalog'}</span>
      {block name='product_discount_table'}
        <table class="table w-100 table-striped">
          
          <tbody>
            {foreach from=$product.quantity_discounts item='quantity_discount' name='quantity_discounts'}
              <tr data-discount-type="{$quantity_discount.reduction_type}" data-discount="{$quantity_discount.real_value}"
                data-discount-quantity="{$quantity_discount.quantity}">
                <td>{l s='Quantité' d='Shop.Theme.Customcatalog'}</td>
                <td class="text-bold text-right">{$quantity_discount.quantity}</td>
              </tr>
              <tr data-discount-type="{$quantity_discount.reduction_type}" data-discount="{$quantity_discount.real_value}"
                data-discount-quantity="{$quantity_discount.quantity}">
                <td>{l s='Prix par unité' d='Shop.Theme.Customcatalog'}</td>
                <td class="text-bold text-right">{$quantity_discount.discount} /{l s='pièce' d='Shop.Theme.Customcatalog'}</td>
              </tr>
              <tr data-discount-type="{$quantity_discount.reduction_type}" data-discount="{$quantity_discount.real_value}"
                data-discount-quantity="{$quantity_discount.quantity}">
                <td>{l s='Économisez' d='Shop.Theme.Customcatalog'}</td>
                <td class="text-bold text-right">{$quantity_discount.save}</td>
            </tr>
            {/foreach}
          </tbody>
        </table>
      {/block}
    </section>
    {else}
    <section class="product-discounts js-product-discounts">
      <p class="h6 product-discounts-title">{l s='Volume discounts' d='Shop.Theme.Catalog'}</p>
      {block name='product_discount_table'}
        <table class="table-product-discounts">
          <thead>
            <tr>
              <th>{l s='Quantity' d='Shop.Theme.Catalog'}</th>
              <th>{$configuration.quantity_discount.label}</th>
              <th>{l s='You Save' d='Shop.Theme.Catalog'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$product.quantity_discounts item='quantity_discount' name='quantity_discounts'}
              <tr data-discount-type="{$quantity_discount.reduction_type}" data-discount="{$quantity_discount.real_value}"
                data-discount-quantity="{$quantity_discount.quantity}">
                <td>{$quantity_discount.quantity}</td>
                <td>{$quantity_discount.discount}</td>
                <td>{$quantity_discount.save}</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {/block}
    </section>
  {/if}
{/if}