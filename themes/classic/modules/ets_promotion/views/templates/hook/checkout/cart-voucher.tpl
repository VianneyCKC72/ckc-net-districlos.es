{*
 * Copyright ETS Software Technology Co., Ltd
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 website only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 * @author ETS Software Technology Co., Ltd
 * @copyright  ETS Software Technology Co., Ltd
 * @license    Valid for 1 website (or project) for each purchase of license
*}
<div class="block-promo">
    <div class="cart-voucher">
        {if $cart.vouchers.added}
            {block name='cart_voucher_list'}
                <ul class="promo-name card-block">
                    {foreach from=$cart.vouchers.added item=voucher}
                        <li class="cart-summary-line">
                            <span class="label">{$voucher.name|escape:'html':'UTF-8'}</span>
                            <div class="float-xs-right">
                                <span>{$voucher.reduction_formatted|escape:'html':'UTF-8'} {if $pro.env == 1}HT{/if}</span>
                                {if isset($voucher.code) && $voucher.code !== ''}
                                    <a href="{$voucher.delete_url|escape:'html':'UTF-8'}" data-link-action="remove-voucher">
                                        <i class="material-icons">&#xE872;</i>
                                    </a>
                                {/if}
                            </div>
                        </li>
                    {/foreach}
                    {hook h='displayCustomDiscountRule' added=true}
                </ul>
            {/block}
        {else}
            {hook h='displayCustomDiscountRule' added=false}
        {/if}

        <p class="promo-code-button display-promo{if $cart.discounts|count > 0} with-discounts{/if}">
            <a class="collapse-button" href="#promo-code">
                {l s='Have a promo code?' mod='ets_promotion'}
            </a>
        </p>

        <div id="promo-code" class="collapse{if $cart.discounts|count > 0} in{/if}">
            <div class="promo-code">
                {block name='cart_voucher_form'}
                    <form action="{$urls.pages.cart|escape:'html':'UTF-8'}" data-link-action="add-voucher" method="post">
                        <input type="hidden" name="token" value="{$static_token|escape:'html':'UTF-8'}">
                        <input type="hidden" name="addDiscount" value="1">
                        <input class="promo-input" type="text" name="discount_name" placeholder="{l s='Promo code' mod='ets_promotion'}">
                        <button type="submit" class="btn btn-primary"><span>{l s='OK' mod='ets_promotion'}</span></button>
                    </form>
                {/block}

                {block name='cart_voucher_notifications'}
                    <div class="alert alert-danger js-error" role="alert">
                        <i class="material-icons">&#xE001;</i><span class="ml-1 js-error-text"></span>
                    </div>
                {/block}

                <a class="collapse-button promo-code-button cancel-promo" role="button" data-toggle="collapse" data-target="#promo-code" aria-expanded="true" aria-controls="promo-code">
                    {l s='Close' mod='ets_promotion'}
                </a>
            </div>
        </div>

        {if $cart.discounts|count > 0}
            <p class="block-promo promo-highlighted">
                {l s='Take advantage of our exclusive offers:' mod='ets_promotion'}
            </p>
            <ul class="js-discount card-block promo-discounts">
                {foreach from=$cart.discounts item=discount}
                    <li class="cart-summary-line">
            <span class="label">
              <span class="code">{$discount.code|escape:'html':'UTF-8'}</span> - {$discount.name|escape:'html':'UTF-8'}
            </span>
                    </li>
                {/foreach}
            </ul>
        {/if}
    </div>
</div>