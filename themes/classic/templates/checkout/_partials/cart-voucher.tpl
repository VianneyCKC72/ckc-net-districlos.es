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
{*{if $cart.vouchers.allowed}*}
{block name='cart_voucher'}
    <div class="block-promo mt-3">
        <div class="cart-voucher js-cart-voucher">
            <div class="promo-code">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <p class="mb-0 collapse-toggle" data-bs-toggle="collapse" href="#promoCollapse" role="button"
                       aria-expanded="false" aria-controls="promoCollapse">
                        Un code promo ?
                    </p>
                    <span class="chevron-toggle" data-bs-toggle="collapse" href="#promoCollapse" role="button"
                          aria-expanded="false" aria-controls="promoCollapse"
                          onclick="$('#promoCollapse').collapse('toggle');">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                           stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                           class="lucide lucide-chevron-down">
                        <path d="m6 9 6 6 6-6"/>
                      </svg>
                    </span>
                </div>

                <div class="collapse" id="promoCollapse">
                    {block name='cart_voucher_form'}
                        <form action="{$urls.pages.cart}" data-link-action="add-voucher" method="post">
                            <input type="hidden" name="token" value="{$static_token}">
                            <input type="hidden" name="addDiscount" value="1">
                            <input class="promo-input mb-2" type="text" name="discount_name"
                                   placeholder="{l s='Promo code' d='Shop.Theme.Checkout'}">
                            <button type="submit" class="btn btn-primary"><span>{l s='OK' d='Shop.Theme.Actions'}</span>
                            </button>
                        </form>
                    {/block}

{*                    {block name='cart_voucher_notifications'}*}
                        <div class="alert alert-danger js-error mt-2" role="alert">
                            <span class="ml-1 js-error-text"></span>
                        </div>
{*                    {/block}*}
                </div>
            </div>


            {if $cart.discounts|count > 0}
                <p class="block-promo promo-highlighted">
                    {l s='Take advantage of our exclusive offers:' d='Shop.Theme.Actions'}
                </p>
                <ul class="js-discount card-block promo-discounts">
                    {foreach from=$cart.discounts item=discount}
                        <li class="cart-summary-line">
                <span class="label">
                  <span class="code">{$discount.code}</span> - {$discount.name}
                </span>
                        </li>
                    {/foreach}
                </ul>
            {/if}
        </div>
    </div>
{/block}
{*{/if}*}
