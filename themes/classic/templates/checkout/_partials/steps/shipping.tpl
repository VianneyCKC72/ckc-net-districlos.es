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
{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  <div id="hook-display-before-carrier">
    {$hookDisplayBeforeCarrier nofilter}
  </div>

  <div class="delivery-options-list">
    {if $delivery_options|count}
      <form class="clearfix" id="js-delivery"
        data-url-update="{url entity='order' params=['ajax' => 1, 'action' => 'selectDeliveryOption']}" method="post">
        <div class="form-fields">
          {block name='delivery_options'}
            <div class="delivery-options">
              {foreach from=$delivery_options item=carrier key=carrier_id}
                {assign var='is_selected' value=($delivery_option == $carrier_id)}
                {assign var='is_pickup' value=(isset($custompickup_main_carrier_id) && $carrier.id == $custompickup_main_carrier_id)}
                {assign var='price_lower' value=$carrier.price|lower}
                {assign var='is_free_price' value=($price_lower == 'gratuit' || $price_lower == 'free')}
                {assign var='has_reduc' value=(isset($carrier.reduc) && $carrier.reduc)}

                <div class="delivery-option js-delivery-option{if $is_selected} -selected{/if}{if $is_pickup} -pickup{/if}">
                  <label for="delivery_option_{$carrier.id}" class="delivery-option-card">
                    <span class="custom-radio white">
                      <input type="radio" name="delivery_option[{$id_address}]" id="delivery_option_{$carrier.id}"
                        value="{$carrier_id}" {if $is_selected} checked{/if}>
                      <span></span>
                    </span>

                    <div class="delivery-option-card__content">
                      <div class="delivery-option-card__header">
                        <div class="delivery-option-card__title">
                          <span class="carrier-name">{$carrier.name}</span>
                          {if isset($carrier.badge) && $carrier.badge}
                            <span class="carrier-badge">{$carrier.badge|escape:'html':'UTF-8'}</span>
                          {/if}
                        </div>
                        <span class="carrier-price">
                          {if $has_reduc}
                            <span class="carrier-price__old">{$carrier.reduc}</span>
                          {/if}
                          {if $is_free_price}
                            <span class="carrier-price__current">{l s='Gratuit' d='Shop.Theme.Checkout'}</span>
                          {else}
                            <span class="carrier-price__current">{$carrier.price|escape:'html':'UTF-8'}</span>
                          {/if}
                        </span>
                      </div>

                      {if $carrier.delay}
                        <div class="carrier-delay">{$carrier.delay nofilter}</div>
                      {/if}
                    </div>
                  </label>

                  {if $is_pickup}
                    <div class="delivery-option-card__extra delivery-option-card__extra--pickup">
                      {hook h='displayCarrierExtraContent' carrier=$carrier.id}
                    </div>
                  {/if}

                  {if !$is_pickup && $carrier.extraContent}
                    <div class="carrier-extra-content js-carrier-extra-content"{if !$is_selected} style="display:none"{/if}>
                      {$carrier.extraContent nofilter}
                    </div>
                  {/if}
                </div>
              {/foreach}
            </div>
          {/block}

          <div class="order-options">
            <div id="delivery">
              <label for="delivery_message"
                class="small">{l s='If you would like to add a comment about your order, please write it in the field below.' d='Shop.Theme.Checkout'}</label>
              <textarea rows="3" id="delivery_message" name="delivery_message">{$delivery_message}</textarea>
            </div>

            {if $recyclablePackAllowed}
              <span class="custom-checkbox">
                <input type="checkbox" id="input_recyclable" name="recyclable" value="1" {if $recyclable} checked {/if}>
                <span><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-check">
                    <path d="M20 6 9 17l-5-5" />
                  </svg></span>
                <label
                  for="input_recyclable">{l s='I would like to receive my order in recycled packaging.' d='Shop.Theme.Checkout'}</label>
              </span>
            {/if}

            {if $gift.allowed}
              <span class="custom-checkbox">
                <input class="js-gift-checkbox" id="input_gift" name="gift" type="checkbox" value="1"
                  {if $gift.isGift}checked="checked" {/if}>
                <span><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-check">
                    <path d="M20 6 9 17l-5-5" />
                  </svg></span>
                <label for="input_gift">{$gift.label}</label>
              </span>

              <div id="gift" class="collapse{if $gift.isGift} in{/if}">
                <label
                  for="gift_message">{l s='If you\'d like, you can add a note to the gift:' d='Shop.Theme.Checkout'}</label>
                <textarea rows="3" id="gift_message" name="gift_message">{$gift.message}</textarea>
              </div>
            {/if}
          </div>
        </div>

        <div class="delivery-actions">
          <button type="button" class="btn-link js-checkout-prev-step">
            {l s='Étape précédente' d='Shop.Theme.Customcheckout'}
          </button>
          <button type="submit" class="continue btn btn-primary" name="confirmDeliveryOption" value="1">
            {l s='Passer au paiement' d='Shop.Theme.Customcheckout'}
          </button>
        </div>
      </form>
    {else}
      <p class="alert alert-danger mt-3">
        {l s='Unfortunately, there are no carriers available for your delivery address.' d='Shop.Theme.Checkout'}</p>
    {/if}
  </div>

  <div id="hook-display-after-carrier">
    {$hookDisplayAfterCarrier nofilter}
  </div>

  <div id="extra_carrier"></div>
{/block}
