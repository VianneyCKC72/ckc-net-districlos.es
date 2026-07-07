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
{extends file=$layout}

{block name='content'}
    <section id="main">
        <div class="cart-grid mt-5">

            <!-- Left Block: cart product informations & shipping -->
            <div class="cart-grid-body">

                <!-- cart products detailed -->
                <div class="cart-container">
                    <div class="card-block">
                        <h1 class="h2">{l s='Shopping Cart' d='Shop.Theme.Checkout'}</h1>
                    </div>

                    {hook h='displayBeforeCartOverview'}

                    {block name='cart_overview'}
                        {include file='checkout/_partials/cart-detailed.tpl' cart=$cart}
                    {/block}
                </div>

                {* {block name='continue_shopping'}
                  <a class="label" href="{$urls.pages.index}">
                    <i class="material-icons">chevron_left</i>{l s='Continue shopping' d='Shop.Theme.Actions'}
                  </a>
                {/block} *}

            </div>

            <!-- Right Block: cart subtotal & cart total -->
            <div class="cart-grid-right">

                <div class="text-right">
                    <a class="exitCart" href="/">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                        Retour à la boutique</a>
                </div>
                {block name='cart_summary'}
                <div class="cart-summary">
                    <div class="container cart-summary-inside">
                        <div>
                            <p class="h3 mb-4">Récapitulatif</p>
                            {include file='checkout/_partials/cart-summary-top.tpl' cart=$cart}
                        </div>

                        {block name='hook_shopping_cart'}
                            {hook h='displayShoppingCart'}
                        {/block}

                        <div class="block-checkout">
                            {block name='cart_totals'}
                                {include file='checkout/_partials/cart-detailed-totals.tpl' cart=$cart}
                            {/block}

                            {if file_exists($smarty.const._PS_MODULE_DIR_|cat:'ckc_shipping_estimator/views/templates/front/cart/shipping_estimator.tpl')}
                                {include file="module:ckc_shipping_estimator/views/templates/front/cart/shipping_estimator.tpl"}
                            {/if}

                            {hook h='displayAfterCartDetailedActions'}

                            {block name='cart_voucher'}
                                {include file='checkout/_partials/cart-voucher.tpl'}
                            {/block}

                            <div class="cart-detailed-totals">
                                {block name='cart_summary_totals'}
                                    {include file='checkout/_partials/cart-summary-totals.tpl' cart=$cart}
                                {/block}
                            </div>
                        </div>

                        {block name='cart_actions'}
                            {include file='checkout/_partials/cart-detailed-actions.tpl' cart=$cart}
                        {/block}

                        <div class="action_sticky">
                            {block name='cart_actions_sticky'}
                                {include file='checkout/_partials/cart-detailed-actions.tpl' cart=$cart}
                            {/block}
                        </div>


                        {*                        <div>*}
                        {*                            todo : Tony va reprendre le code dans un controller *}
                        {*                            {if $cart.subtotals.products.next_promotion > 0}*}
                        {*                            <p class="text-red small mb-0">*}
                        {*                                Plus que {$cart.subtotals.products.next_promotion}€ pour bénéficier de <b>100€ offerts</b>.*}
                        {*                            </p>*}

                        {*                            <p class="text-red small mb-0">*}
                        {*                                Plus que {$cart.subtotals.products.next_promotion}€ pour bénéficier de <b>la livraison*}
                        {*                                    offerte</b>.</p>*}
                        {*                            {/if}*}
                        {*                        </div>*}

                    </div>
                    {/block}

                    {* {block name='hook_reassurance'}
                      {hook h='displayReassurance'}
                    {/block} *}

                </div>

                {include file='checkout/_partials/cart-moyen-paiement.tpl'}
            </div>
        </div>

        {include file='checkout/_partials/alert-exit-cart.tpl'}

        <div class="col-12">
            <!-- shipping informations -->
            {block name='hook_shopping_cart_footer'}
                {hook h='displayShoppingCartFooter'}
            {/block}

            {hook h='displayCrossSellingShoppingCart'}
        </div>

        <div class="col-12 reassu-cart">
            {hook h='CETemplate' id=1}
        </div>

    </section>
{/block}
