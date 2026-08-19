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
<div id="order-items" class="col-md-12">
    <div class="row">
        {block name='order_items_table_head'}
            <h3 class="card-title h3 col-md-6 col-12">{l s='Order items' d='Shop.Theme.Checkout'}</h3>
            <h3 class="card-title h3 col-md-2 text-md-center _desktop-title">{l s='Unit price' d='Shop.Theme.Checkout'}</h3>
            <h3 class="card-title h3 col-md-2 text-md-center _desktop-title">{l s='Quantity' d='Shop.Theme.Checkout'}</h3>
            <h3 class="card-title h3 col-md-2 text-md-center _desktop-title">{l s='Total products' d='Shop.Theme.Checkout'}</h3>
        {/block}
    </div>

    <div class="order-confirmation-table">

        {block name='order_confirmation_table'}
            <div class="order-lines">
                {foreach from=$products item=product}
                    <div class="order-line">
                        <div class="row">

                            <div class="col-sm-2 col-xs-3">
              <span class="image">
                {if !empty($product.default_image)}
                    <picture>
                    {if !empty($product.default_image.medium.sources.avif)}
                        <source srcset="{$product.default_image.medium.sources.avif}" type="image/avif">{/if}
                        {if !empty($product.default_image.medium.sources.webp)}
                            <source srcset="{$product.default_image.medium.sources.webp}" type="image/webp">{/if}
                    <img src="{$product.default_image.medium.url}" alt="{$product.name}" loading="lazy" width="80" height="80"/>
                  </picture>
                {else}
                    <img src="{$urls.no_picture_image.bySize.medium_default.url}" alt="{l s='No picture' d='Shop.Theme.Catalog'}" loading="lazy" width="80" height="80"/>
                {/if}
              </span>
                            </div>

                            <div class="col-sm-4 col-xs-9 details">
                                <span class="product-name">{$product.name|regex_replace:'/\s*\(.*\)\s*$/':''|escape:'html'}</span>

                                {if isset($product.attributes) && $product.attributes}
                                    <div class="product-attributes-inline">
                                        {foreach from=$product.attributes key="attribute" item="value" name="attrs"}
                                            <span>{$attribute} : {$value}</span>{if !$smarty.foreach.attrs.last}<br>{/if}
                                        {/foreach}
                                    </div>
                                {/if}

                                {if is_array($product.customizations) && $product.customizations|count}
                                    {foreach from=$product.customizations item="customization"}
                                        <div class="customizations">
                                            <a href="#" data-toggle="modal"
                                               data-target="#product-customizations-modal-{$customization.id_customization}">
                                                {l s='Product customization' d='Shop.Theme.Catalog'}
                                            </a>
                                        </div>
                                    {/foreach}
                                {/if}

                                {hook h='displayProductPriceBlock' product=$product type="unit_price"}
                            </div>

                            <div class="col-sm-6 col-xs-12 qty">
                                <div class="row">
                                    {if isset($pro) && $pro.env == 1}
                                        <div class="col-xs-4 text-sm-center text-xs-left">
                                            {$product.price_with_reduction_without_tax|number_format:2:',':' '} € {l s='HT' d='Shop.Theme.Customcheckout'}
                                        </div>
                                        <div class="col-xs-4 text-sm-center">
                                            {$product.quantity}
                                        </div>
                                        <div class="col-xs-4 text-sm-center text-xs-right bold">
                                            {$product.total} HT
                                        </div>
                                    {else}
                                        <div class="col-xs-4 text-sm-center text-xs-left">
                                            {$product.price} TTC
                                        </div>
                                        <div class="col-xs-4 text-sm-center">
                                            {$product.quantity}
                                        </div>
                                        <div class="col-xs-4 text-sm-center text-xs-right bold">
                                            {$product.total} TTC
                                        </div>
                                    {/if}
                                </div>
                            </div>

                        </div>
                    </div>
                {/foreach}
            </div>
            <div class="order-confirmation-recap">
            {block name='order_confirmation_totals'}
            <div class="order-confirmation-recap__totals">
            <table class="order-confirmation-total">

                {foreach $subtotals as $subtotal}
                    {if $subtotal !== null && $subtotal.type !== 'tax' && $subtotal.label !== null}
                        <tr>
                            <td>{$subtotal.label} &nbsp;{if isset($pro) && $pro.env == 1}HT{else}TTC{/if}</td>
                            <td>{if 'discount' == $subtotal.type}-&nbsp;{/if}{$subtotal.value}</td>
                        </tr>
                    {/if}
                {/foreach}

                {if !$configuration.display_prices_tax_incl && $configuration.taxes_enabled}
                    <tr class="total-value">
                        <td class="label"><span class="text-uppercase">{$totals.total.label}&nbsp;{if isset($pro) && $pro.env == 1}HT{else}TTC{/if}</span></td>
                        <td class="value">{$totals.total.value}</td>
                    </tr>
                    <tr class="total-value">
                        <td class="label"><span class="text-uppercase">{$totals.total_including_tax.label}</span></td>
                        <td class="value">{$totals.total_including_tax.value}</td>
                    </tr>
                {else}
                    <tr class="total-value">
                        <td class="label">
            <span class="text-uppercase">
              {$totals.total.label}
                {if $configuration.taxes_enabled && $configuration.display_taxes_label}{$labels.tax_short}{/if}
            </span>
                        </td>
                        <td class="value">{$totals.total.value}</td>
                    </tr>
                {/if}

                {if isset($pro) && $pro.env == 1 && $subtotals.tax !== null}
                    <tr class="total-value tva-line">
                        <td class="label">TVA (20%)</td>
                        <td class="value">{$subtotals.tax.value}</td>
                    </tr>
                {/if}

                {if $subtotals.tax !== null && $subtotals.tax.label !== null}
                    <tr class="sub taxes">
                        <td colspan="2">
            <span class="label">
              {l s='%label%:' sprintf=['%label%' => $subtotals.tax.label] d='Shop.Theme.Global'}
            </span>
                            <span class="value">{$subtotals.tax.value}</span>
                        </td>
                    </tr>
                {/if}

            </table>
            </div>
            {/block}

            {block name='order_confirmation_details'}
              {if isset($order)}
                <div id="order-details" class="order-confirmation-recap__details">
                  <h3 class="h3 card-title">{l s='Order details' d='Shop.Theme.Checkout'}:</h3>
                  <ul>
                    <li id="order-reference-value">{l s='Order reference: %reference%' d='Shop.Theme.Checkout' sprintf=['%reference%' => $order.details.reference]}</li>
                    <li>{l s='Payment method: %method%' d='Shop.Theme.Checkout' sprintf=['%method%' => $order.details.payment]}</li>
                    {if !$order.details.is_virtual}
                      <li>{l s='Shipping method: %method%' d='Shop.Theme.Checkout' sprintf=['%method%' => $order.carrier.name]}</li>
                      {if $order.carrier.delay}
                        <li class="order-details-delay"><em>{$order.carrier.delay nofilter}</em></li>
                      {/if}
                    {/if}
                    {if $order.details.recyclable}
                      <li><em>{l s='You have given permission to receive your order in recycled packaging.' d="Shop.Theme.Customeraccount"}</em></li>
                    {/if}
                  </ul>
                </div>
              {/if}
            {/block}
            </div>
            {/block}

    </div>
</div>
