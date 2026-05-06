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

                            {* IMAGE *}
                            <div class="col-sm-2 col-xs-3">
              <span class="image">
                {if !empty($product.default_image)}
                    <picture>
                    {if !empty($product.default_image.medium.sources.avif)}
                        <source srcset="{$product.default_image.medium.sources.avif}" type="image/avif">{/if}
                        {if !empty($product.default_image.medium.sources.webp)}
                            <source srcset="{$product.default_image.medium.sources.webp}" type="image/webp">{/if}
                    <img src="{$product.default_image.medium.url}" loading="lazy" width="452" height="452"/>
                  </picture>

{else}

                    <img src="{$urls.no_picture_image.bySize.medium_default.url}" loading="lazy" width="452" height="452"/>
                {/if}
              </span>
                            </div>

                            {* NAME + CUSTOM *}
                            <div class="col-sm-4 col-xs-9 details">

                                <span>{$product.name|escape:'html'}</span>

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

                            {* PRICES *}
                            <div class="col-sm-6 col-xs-12 qty">
                                <div class="row">

                                    {if $pro.env == 1}
                                        {* Sécurité : vérifie les valeurs pour éviter array/string error *}
                                        {assign var="unit_ht" value=$product.unit_price_tax_excl|floatval}
                                        {assign var="total_ht" value=$product.total_price_tax_excl|floatval}
                                        <div class="col-xs-4 text-sm-center text-xs-left">
                                            {$product.price_with_reduction_without_tax|number_format:2:',':' '} € HT
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
            {* TOTALS *}
            <table class="order-confirmation-total">

                {* Sous-totaux *}
                {foreach $subtotals as $subtotal}
                    {if $subtotal !== null && $subtotal.type !== 'tax' && $subtotal.label !== null}
                        <tr>
                            <td>{$subtotal.label} &nbsp;{if $pro.env == 1}HT{else}TTC{/if}</td>
                            <td>{if 'discount' == $subtotal.type}-&nbsp;{/if}{$subtotal.value}</td>
                        </tr>
                    {/if}
                {/foreach}

                {* TOTAL HT & TTC *}
                {if !$configuration.display_prices_tax_incl && $configuration.taxes_enabled}
                    <tr class="total-value">
                        <td class="label"><span class="text-uppercase">{$totals.total.label}&nbsp;{if $pro.env == 1}HT{else}TTC{/if}</span></td>
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

                {* TVA PRO *}
                {if $pro.env == 1 && $subtotals.tax !== null}
                    <tr class="total-value tva-line">
                        <td class="label">TVA (20%)</td>
                        <td class="value">{$subtotals.tax.value}</td>
                    </tr>
                {/if}

                {* TVA pour particuliers (déjà gérée) *}
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
        {/block}

    </div>
