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
{if $product.show_price}
  <div class="product-prices js-product-prices mb-4">
    {block name='product_price'}
      {include file='catalog/_partials/product-prices/has-discount.tpl'}
      {include file='catalog/_partials/product-prices/price.tpl'}
      <div>
        <p class="mb-0">Payez en plusieurs fois<span style="margin-inline: 10px"><img
              src="/themes/classic/assets/img/services/multiple-paiement.svg" loading="lazy" alt=""></span>en carte
          bancaire sans frais</p>
        <p class="mb-0 thin">Un crédit vous engage et doit être remboursé. vérifiez vos capacités de remboursement avant
          de
          vous engager.</p>
      </div>

      {* <div class="product-price{if $product.has_discount}has-discount{/if}">

        <div class="current-price">
          <span class='current-price-value' content="{$product.rounded_display_price}">
            {capture name='custom_price'}{hook h='displayProductPriceBlock' product=$product type='custom_price' hook_origin='product_sheet'}{/capture}
            {if '' !== $smarty.capture.custom_price}
              {$smarty.capture.custom_price nofilter}
            {else}
              {$product.price}
            {/if}
          </span>

          {if $product.has_discount}
            {if $product.discount_type === 'percentage'}
              <span
                class="discount discount-percentage">{l s='Save %percentage%' d='Shop.Theme.Catalog' sprintf=['%percentage%' => $product.discount_percentage_absolute]}</span>
            {else}
              <span class="discount discount-amount">
                {l s='Save %amount%' d='Shop.Theme.Catalog' sprintf=['%amount%' => $product.discount_to_display]}
              </span>
            {/if}
          {/if}
        </div>

        {block name='product_unit_price'}
          {if $displayUnitPrice}
            <p class="product-unit-price sub">{$product.unit_price_full}</p>
          {/if}
        {/block}
      </div> *}
    {/block}

    {block name='product_without_taxes'}
      {if $priceDisplay == 2}
        <p class="product-without-taxes">
          {l s='%price% tax excl.' d='Shop.Theme.Catalog' sprintf=['%price%' => $product.price_tax_exc]}</p>
      {/if}
    {/block}

    {block name='product_pack_price'}
      {if $displayPackPrice}
        <p class="product-pack-price">
          <span>{l s='Instead of %price%' d='Shop.Theme.Catalog' sprintf=['%price%' => $noPackPrice]}</span>
        </p>
      {/if}
    {/block}

    {block name='product_ecotax'}
      {if !$product.is_virtual && $product.ecotax.amount > 0}
        <p class="price-ecotax">
          {l s='Including %amount% for ecotax' d='Shop.Theme.Catalog' sprintf=['%amount%' => $product.ecotax.value]}
          {if $product.has_discount}
            {l s='(not impacted by the discount)' d='Shop.Theme.Catalog'}
          {/if}
        </p>
      {/if}
    {/block}

    {hook h='displayProductPriceBlock' product=$product type="weight" hook_origin='product_sheet'}

    <div class="banner-quick-view">
      <div class="container">
        <div class="banner-quick-view-content">
          <div class="price-name">
            {include file='catalog/_partials/product-prices/has-discount.tpl'}
            {block name='page_header_container'}
              {block name='page_header'}
                <h2 class="h2 mb-0 mt-2">{block name='page_title'}{$product.name}{/block}</h2>
                <p class="mb-0">Avis</p>
              {/block}
            {/block}
          </div>
          <div class="product-price-add-to-cart">
            <div class="price">
              {block name='product_price'}
                {include file='catalog/_partials/product-prices/price.tpl'}
              {/block}
            </div>
            <div class="add-to-cart">
              {block name='product_add_to_cart'}
                {include file='catalog/_partials/product-add-to-cart.tpl'}
              {/block}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
{/if}