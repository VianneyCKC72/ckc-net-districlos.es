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

        {* Liste des promotions appliquées *}
        {if $cart.vouchers.added}
            {block name='cart_voucher_list'}
                <ul class="applied-vouchers">
                    {foreach from=$cart.vouchers.added item=voucher}
                        <li class="applied-voucher">
                            <span class="applied-voucher__tag">PROMO</span>
                            <span class="applied-voucher__label">{$voucher.name}</span>
                            <span class="applied-voucher__value">{$voucher.reduction_formatted}{if $pro.env == 1} HT{/if}</span>
                            {if isset($voucher.code) && $voucher.code !== ''}
                                <a href="{$voucher.delete_url}" data-link-action="remove-voucher" class="applied-voucher__remove" aria-label="{l s='Remove' d='Shop.Theme.Actions'}">
                                    <svg width="9" height="14" viewBox="0 0 9 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3.375 0.5L2.8125 1.15H0.5625C0.225 1.15 0 1.41 0 1.8C0 2.19 0.225 2.45 0.5625 2.45H1.6875H7.3125H8.4375C8.775 2.45 9 2.19 9 1.8C9 1.41 8.775 1.15 8.4375 1.15H6.1875L5.625 0.5H3.375ZM0.5625 3.75V12.2C0.5625 12.915 1.06875 13.5 1.6875 13.5H7.3125C7.93125 13.5 8.4375 12.915 8.4375 12.2V3.75H0.5625ZM2.8125 5.05C3.15 5.05 3.375 5.31 3.375 5.7V11.55C3.375 11.94 3.15 12.2 2.8125 12.2C2.475 12.2 2.25 11.94 2.25 11.55V5.7C2.25 5.31 2.475 5.05 2.8125 5.05ZM6.1875 5.05C6.525 5.05 6.75 5.31 6.75 5.7V11.55C6.75 11.94 6.525 12.2 6.1875 12.2C5.85 12.2 5.625 11.94 5.625 11.55V5.7C5.625 5.31 5.85 5.05 6.1875 5.05Z" fill="currentColor"></path>
                                    </svg>
                                </a>
                            {/if}
                        </li>
                    {/foreach}
                </ul>
            {/block}
        {/if}
    </div>
{/block}
{*{/if}*}
