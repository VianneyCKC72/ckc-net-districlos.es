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

  <script>
    window.CKC_PICKUP_I18N = {
      distanceExact: '{l s='Se situe à %distance% km de chez vous' d='Shop.Theme.Customcheckout' js=1}',
      distanceApprox: '{l s='Se situe à environ %distance% km de chez vous' d='Shop.Theme.Customcheckout' js=1}',
      seeOtherStores: '{l s='Voir les autres magasins' d='Shop.Theme.Customcheckout' js=1}',
      closed: '{l s='Fermé' d='Shop.Theme.Customcheckout' js=1}',
      monday: '{l s='Lundi' d='Shop.Theme.Customcheckout' js=1}',
      tuesday: '{l s='Mardi' d='Shop.Theme.Customcheckout' js=1}',
      wednesday: '{l s='Mercredi' d='Shop.Theme.Customcheckout' js=1}',
      thursday: '{l s='Jeudi' d='Shop.Theme.Customcheckout' js=1}',
      friday: '{l s='Vendredi' d='Shop.Theme.Customcheckout' js=1}',
      saturday: '{l s='Samedi' d='Shop.Theme.Customcheckout' js=1}',
      sunday: '{l s='Dimanche' d='Shop.Theme.Customcheckout' js=1}',
      availableFrom: '{l s='Disponible à partir du' d='Shop.Theme.Customcheckout' js=1}',
      deliveredBetween: '{l s='Livré entre le' d='Shop.Theme.Customcheckout' js=1}',
      andThe: '{l s=' et le ' d='Shop.Theme.Customcheckout' js=1}',
      byAppointment: '{l s='Sur rendez-vous' d='Shop.Theme.Customcheckout' js=1}',
      confirmationEmail: '{l s='Vous recevrez un email de confirmation.' d='Shop.Theme.Customcheckout' js=1}',
      subjectToStock: '{l s='Sous réserve de stock' d='Shop.Theme.Customcheckout' js=1}',
      unloadingHelp: '{l s='Déchargement avec aide requise' d='Shop.Theme.Customcheckout' js=1}',
      unloadingByUs: '{l s='Déchargement par nos soins' d='Shop.Theme.Customcheckout' js=1}',
      saveOnDelivery: '{l s='Economisez sur la livraison' d='Shop.Theme.Customcheckout' js=1}',
      within10Days: '{l s='Sous 10 jours (sous réserve des stocks disponibles) et sur rendez-vous uniquement après confirmation que votre commande est prête' d='Shop.Theme.Customcheckout' js=1}'
    };
    (function () {
      var I = window.CKC_PICKUP_I18N || {};
      var days = {
        'Lundi': I.monday || 'Monday',
        'Mardi': I.tuesday || 'Tuesday',
        'Mercredi': I.wednesday || 'Wednesday',
        'Jeudi': I.thursday || 'Thursday',
        'Vendredi': I.friday || 'Friday',
        'Samedi': I.saturday || 'Saturday',
        'Dimanche': I.sunday || 'Sunday'
      };

      function localizePickup(root) {
        root = root || document;
        root.querySelectorAll('.pickup-distance').forEach(function (el) {
          var text = (el.textContent || '').trim();
          var mExact = text.match(/^Se situe à\s+([\d.,]+)\s*km de chez vous$/i);
          var mApprox = text.match(/^Se situe à environ\s+([\d.,]+)\s*km de chez vous$/i);
          if (mExact) {
            el.textContent = (I.distanceExact || 'Located %distance% km from you').replace('%distance%', mExact[1]);
          } else if (mApprox) {
            el.textContent = (I.distanceApprox || 'About %distance% km from you').replace('%distance%', mApprox[1]);
          }
        });

        root.querySelectorAll('#show-other-pickups, .clickShop').forEach(function (el) {
          if (/voir les autres magasins/i.test(el.textContent || '')) {
            el.textContent = I.seeOtherStores || 'See other stores';
          }
        });

        root.querySelectorAll('.hours-list li > span:first-child').forEach(function (el) {
          var day = (el.textContent || '').trim();
          if (days[day]) {
            el.textContent = days[day];
          }
        });

        root.querySelectorAll('.hours-list li > span:last-child').forEach(function (el) {
          if (/^ferm[eé]$/i.test((el.textContent || '').trim())) {
            el.textContent = I.closed || 'Closed';
          }
        });

        root.querySelectorAll('.carrier-delay').forEach(function (el) {
          var html = el.innerHTML;
          if (!html) return;
          html = html
            .replace(/Disponible à partir du/g, I.availableFrom || 'Available from')
            .replace(/Livré entre le/g, I.deliveredBetween || 'Delivered between')
            .replace(/ et le /g, I.andThe || ' and ')
            .replace(/Sur rendez-vous/g, I.byAppointment || 'By appointment')
            .replace(/Vous recevrez un email de confirmation\./g, I.confirmationEmail || 'You will receive a confirmation email.')
            .replace(/Sous réserve de stock/g, I.subjectToStock || 'Subject to stock availability')
            .replace(/Déchargement avec aide requise/g, I.unloadingHelp || 'Unloading with assistance required')
            .replace(/Déchargement par nos soins/g, I.unloadingByUs || 'Unloaded by our team')
            .replace(/Sous 10 jours \(sous réserve des stocks disponibles\) et sur rendez-vous uniquement après confirmation que votre commande est prête/g, I.within10Days || 'Within 10 days (subject to stock availability) and by appointment only after confirmation that your order is ready');
          // French weekdays/months inside <strong> if DeliveryDelayHelper not yet deployed
          html = html
            .replace(/\b(lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche)\b/gi, function (d) {
              var key = d.charAt(0).toUpperCase() + d.slice(1).toLowerCase();
              var map = {Lundi:'Monday',Mardi:'Tuesday',Mercredi:'Wednesday',Jeudi:'Thursday',Vendredi:'Friday',Samedi:'Saturday',Dimanche:'Sunday'};
              return map[key] || d;
            })
            .replace(/\b(janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout|septembre|octobre|novembre|décembre|decembre)\b/gi, function (m) {
              var map = {janvier:'January',février:'February',fevrier:'February',mars:'March',avril:'April',mai:'May',juin:'June',juillet:'July','août':'August',aout:'August',septembre:'September',octobre:'October',novembre:'November','décembre':'December',decembre:'December'};
              return map[m.toLowerCase()] || m;
            });
          el.innerHTML = html;
        });

        root.querySelectorAll('.carrier-badge').forEach(function (el) {
          var t = (el.textContent || '').trim();
          if (/^conomisez sur la livraison$/i.test(t.replace(/^É/i, 'E').replace(/^é/i, 'e')) || /^[EÉ]conomisez sur la livraison$/i.test(t)) {
            el.textContent = I.saveOnDelivery || 'Save on delivery';
          }
        });
      }

      function start() {
        localizePickup(document);
        if (typeof MutationObserver === 'undefined') return;
        var target = document.querySelector('.delivery-options-list') || document.body;
        var obs = new MutationObserver(function () { localizePickup(document); });
        obs.observe(target, { childList: true, subtree: true });
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
      } else {
        start();
      }
    })();
  </script>
{/block}
