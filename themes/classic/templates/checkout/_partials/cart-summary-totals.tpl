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

{block name='cart_summary_total'}
  {if !$configuration.display_prices_tax_incl && $configuration.taxes_enabled}
    <div class="cart-summary-line">
      <span class="label">{$cart.totals.total.label}&nbsp;{if $pro.env}HT{else}{$cart.labels.tax_short}{/if}</span>
      <span class="value">{$cart.totals.total.value}</span>
    </div>

    {if $pro.env == 1 && isset($cart.totals.total_including_tax.value) && isset($cart.totals.total.value)}

      {* Nettoyage des valeurs TTC & HT pour les convertir en float *}
      {assign var="ttc_clean" value=$cart.totals.total_including_tax.value|replace:'€':''|replace:' ':' '|replace:',':'.'|regex_replace:"/[^\d\.]/":""}
      {assign var="ht_clean"  value=$cart.totals.total.value|replace:'€':''|replace:' ':' '|replace:',':'.'|regex_replace:"/[^\d\.]/":""}

      {* Conversion en float *}
      {assign var="totalTTC" value=$ttc_clean+0}
      {assign var="totalHT" value=$ht_clean+0}

      {* Calcul TVA *}
      {assign var="tva_calc" value=$totalTTC - $totalHT}

      <div class="cart-summary-line cart-summary-tva">
        <span class="label">Montant TVA (20%)</span>
        <span class="value">
                                    {$tva_calc|number_format:2:',':' '} €
                                </span>
      </div>

    {/if}

    <div class="cart-summary-line cart-total">
      <span class="label">{$cart.totals.total_including_tax.label}</span>
      <span class="value">
        {if $page.page_name == 'cart' && isset($cart.subtotals.discounts) && $cart.subtotals.discounts.amount > 0}
          <span class="value-before-promo">{($cart.totals.total_including_tax.amount + $cart.subtotals.discounts.amount)|number_format:2:',':' '}&nbsp;€</span>
        {/if}
        {$cart.totals.total_including_tax.value}
      </span>
    </div>
  {else}
    {if isset($isTVAAttested)}
      {if $isTVAAttested}
        <div class="cart-summary-line">
          <span class="label">{$cart.totals.total_excluding_tax.label}</span>
          <span class="value">{$cart.totals.total_excluding_tax.value}</span>
        </div>
      {/if}
    {/if}
    {*      <div class="cart-summary-line cart-total">
            <span class="label">{$cart.totals.total.label}&nbsp;{if $configuration.display_taxes_label && $configuration.taxes_enabled}{$cart.labels.tax_short}{/if}</span>
            <span class="value">{$cart.totals.total.value}</span>
          </div>*}
    {if $page.page_name == 'cart'}
      <div class="cart-summary-line cart-total">
        <span class="label">{$cart.totals.total_for_cart.label}&nbsp;{if $configuration.taxes_enabled && $env_pro != 1}{$cart.labels.tax_short}{/if}</span>
        <span class="value">
          {if isset($cart.subtotals.discounts) && $cart.subtotals.discounts.amount > 0}
            <span class="value-before-promo">{($cart.totals.total_for_cart.amount + $cart.subtotals.discounts.amount)|number_format:2:',':' '}&nbsp;€</span>
          {/if}
          {$cart.totals.total_for_cart.value}
        </span>
      </div>
    {else}
      <div class="cart-summary-line cart-total">
        <span class="label">{$cart.totals.total.label}&nbsp;{if $configuration.taxes_enabled && $env_pro != 1}{$cart.labels.tax_short}{/if}</span>
        <span class="value">{$cart.totals.total.value}</span>
      </div>
    {/if}
  {/if}

  {if isset($cart.subtotals.discounts) && $cart.subtotals.discounts.amount > 0}
    <div class="cart-summary-line cart-savings" id="cart-savings" style="justify-content: center; font-size: 14px;margin-top: 10px;">
      <span class="label">{l s='Vous économisez [1]%amount% €[/1] sur votre commande' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>', '%amount%' => $cart.subtotals.discounts.amount|round] d='Shop.Theme.Customcheckout'}</span>
    </div>
  {/if}
{/block}

{block name='cart_summary_tax'}
  {if $cart.subtotals.tax}
    <div class="cart-summary-line">
      <span class="label sub">{l s='%label%:' sprintf=['%label%' => $cart.subtotals.tax.label] d='Shop.Theme.Global'}</span>
      <span class="value sub">{$cart.subtotals.tax.value}</span>
    </div>
  {/if}
{/block}

