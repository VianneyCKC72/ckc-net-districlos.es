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

          {block name='cart_overview'}
            {include file='checkout/_partials/cart-detailed.tpl' cart=$cart}
          {/block}
        </div>

        {* {block name='continue_shopping'}
          <a class="label" href="{$urls.pages.index}">
            <i class="material-icons">chevron_left</i>{l s='Continue shopping' d='Shop.Theme.Actions'}
          </a>
        {/block} *}

        <!-- shipping informations -->
        {block name='hook_shopping_cart_footer'}
          {hook h='displayShoppingCartFooter'}
        {/block}
      </div>

      <!-- Right Block: cart subtotal & cart total -->
      <div class="cart-grid-right">

        {block name='cart_summary'}
          <div class="cart-summary">

            <div>
              <p class="h3">Récapitulatif</p>
              <div class="d-flex align-items-center mb-2 mt-4">
                <img src="/themes/classic/assets/img/services/delivery.svg" loading="lazy" alt="">
                <div class="small ml-4">
                  Livraison à domicile <span class="font-weight-bold text-secondary">offerte dès 1 499 € TTC</span><br>
                  <span class="thin">sous 10 jours</span>
                </div>
              </div>
              <a href="#" class="d-block small text-black font-weight-bold">Estimez vos frais de livraison <svg
                  xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-move-right ml-2">
                  <path d="M18 8L22 12L18 16"></path>
                  <path d="M2 12H22"></path>
                </svg>
              </a>
            </div>

            {block name='hook_shopping_cart'}
              {hook h='displayShoppingCart'}
            {/block}

            <div>
              {block name='cart_totals'}
                {include file='checkout/_partials/cart-detailed-totals.tpl' cart=$cart}
              {/block}

              {block name='cart_actions'}
                {include file='checkout/_partials/cart-detailed-actions.tpl' cart=$cart}
              {/block}
            </div>

            <div>
              <p class="text-red small mb-0">Plus que 942.6€ pour bénéficier de <b>100€ offerts</b>.</p>
              <p class="text-red small mb-0">Plus que 1441.6€ pour bénéficier de <b>la livraison offerte</b>.</p>
            </div>

            <div>
              <p>Moyens de paiement acceptés</p>
              <div class="d-flex flex-wrap gap-16">
                <img style="width: 70px;" src="/themes/classic/assets/img/payment/mastercard.png" loading="lazy"
                  alt="Mastercard">
                <img style="width: 70px;" src="/themes/classic/assets/img/payment/visa.png" loading="lazy" alt="Visa">
                <img style="width: 70px;" src="/themes/classic/assets/img/payment/cb.png" loading="lazy" alt="Carte Bleue">
              </div>
            </div>

          </div>
        {/block}

        {* {block name='hook_reassurance'}
          {hook h='displayReassurance'}
        {/block} *}

      </div>

    </div>

    <div>
      <p class="h2">Découvrez nos moyens de financement</p>
      <div class="payment-methods__listing mt-5">
        <div class="payment-method">
          <div class="img">
            <img src="/themes/classic/assets/img/payment-methods/boncontact.jpg" loading="lazy" alt="Bancontact">
          </div>
          <p>Bancontact</p>
        </div>
        <div class="payment-method">
          <div class="img">
            <img src="/themes/classic/assets/img/payment-methods/cb.jpg" loading="lazy" alt="Carte Bleue">
          </div>
          <p>Carte Bleue</p>
        </div>
        <div class="payment-method">
          <div class="img">
            <img src="/themes/classic/assets/img/payment-methods/paypal.jpg" loading="lazy" alt="Paypal">
          </div>
          <p>Paypal<svg class="ml-1" width="21" height="21" viewBox="0 0 21 21" fill="none"
              xmlns="http://www.w3.org/2000/svg">
              <circle cx="10.5" cy="10.5" r="10.5" fill="#7CBA49" />
              <path
                d="M4.484 12.57V10.997L8.037 6.124H10.061V10.931H10.996V12.57H10.061V14H8.158V12.57H4.484ZM8.312 8.302L6.398 10.931H8.312V8.302ZM15.8786 14L14.6246 12.251L13.6016 14H11.5336L13.5686 10.799L11.4566 7.862H13.5686L14.8116 9.589L15.8346 7.862H17.9026L15.8236 10.986L17.9906 14H15.8786Z"
                fill="white" />
            </svg>
          </p>
        </div>
        <div class="payment-method">
          <div class="img">
            <img src="/themes/classic/assets/img/payment-methods/fintecture.jpg" loading="lazy" alt="Fintecture">
          </div>
          <p>Fintecture</p>
        </div>
        <div class="payment-method">
          <div class="img">
            <img src="/themes/classic/assets/img/payment-methods/oney.jpg" loading="lazy" alt="Oney">
          </div>
          <p>Oney
            <svg class="ml-1" width="103" height="21" viewBox="0 0 103 21" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="10.5" r="10.5" fill="#7CBA49" />
              <path
                d="M7.822 5.882C8.36467 5.882 8.83033 5.97733 9.219 6.168C9.60767 6.35133 9.901 6.608 10.099 6.938C10.3043 7.26067 10.407 7.62733 10.407 8.038C10.407 8.544 10.275 8.951 10.011 9.259C9.75433 9.567 9.43167 9.76133 9.043 9.842V9.897C10.0477 10.2197 10.55 10.8467 10.55 11.778C10.55 12.2253 10.4473 12.625 10.242 12.977C10.0367 13.3217 9.736 13.593 9.34 13.791C8.95133 13.9817 8.482 14.077 7.932 14.077C7.03733 14.077 6.326 13.857 5.798 13.417C5.27733 12.977 4.995 12.306 4.951 11.404H6.788C6.80267 11.7487 6.898 12.02 7.074 12.218C7.25 12.416 7.50667 12.515 7.844 12.515C8.11533 12.515 8.328 12.4343 8.482 12.273C8.64333 12.1117 8.724 11.9027 8.724 11.646C8.724 11.008 8.262 10.689 7.338 10.689H6.986V9.16H7.327C8.15567 9.17467 8.57 8.87767 8.57 8.269C8.57 8.005 8.49667 7.80333 8.35 7.664C8.21067 7.51733 8.02 7.444 7.778 7.444C7.514 7.444 7.305 7.53567 7.151 7.719C6.997 7.895 6.909 8.13333 6.887 8.434H5.05C5.08667 7.61267 5.34333 6.982 5.82 6.542C6.29667 6.102 6.964 5.882 7.822 5.882ZM15.6482 14L14.3942 12.251L13.3712 14H11.3032L13.3382 10.799L11.2262 7.862H13.3382L14.5812 9.589L15.6042 7.862H17.6722L15.5932 10.986L17.7602 14H15.6482Z"
                fill="white" />
              <circle cx="38" cy="10.5" r="10.5" fill="#7CBA49" />
              <path
                d="M31.984 12.57V10.997L35.537 6.124H37.561V10.931H38.496V12.57H37.561V14H35.658V12.57H31.984ZM35.812 8.302L33.898 10.931H35.812V8.302ZM43.3786 14L42.1246 12.251L41.1016 14H39.0336L41.0686 10.799L38.9566 7.862H41.0686L42.3116 9.589L43.3346 7.862H45.4026L43.3236 10.986L45.4906 14H43.3786Z"
                fill="white" />
              <circle cx="65" cy="10.5" r="10.5" fill="#7CBA49" />
              <path
                d="M56.621 7.983V6.223H59.767V14H57.787V7.983H56.621ZM61.6199 12.922C61.1286 12.2107 60.8829 11.2463 60.8829 10.029C60.8829 8.819 61.1286 7.862 61.6199 7.158C62.1112 6.44667 62.8959 6.091 63.9739 6.091C65.0446 6.091 65.8256 6.44667 66.3169 7.158C66.8156 7.862 67.0649 8.819 67.0649 10.029C67.0649 11.2463 66.8156 12.2107 66.3169 12.922C65.8256 13.6333 65.0446 13.989 63.9739 13.989C62.8959 13.989 62.1112 13.6333 61.6199 12.922ZM64.9419 11.646C65.1106 11.2647 65.1949 10.7257 65.1949 10.029C65.1949 9.33233 65.1106 8.80067 64.9419 8.434C64.7806 8.06 64.4579 7.873 63.9739 7.873C63.4826 7.873 63.1526 8.06 62.9839 8.434C62.8152 8.808 62.7309 9.33967 62.7309 10.029C62.7309 10.7257 62.8152 11.2647 62.9839 11.646C63.1526 12.02 63.4826 12.207 63.9739 12.207C64.4579 12.207 64.7806 12.02 64.9419 11.646ZM71.988 14L70.734 12.251L69.711 14H67.643L69.678 10.799L67.566 7.862H69.678L70.921 9.589L71.944 7.862H74.012L71.933 10.986L74.1 14H71.988Z"
                fill="white" />
              <circle cx="92" cy="10.5" r="10.5" fill="#7CBA49" />
              <path
                d="M83.621 7.983V6.223H86.767V14H84.787V7.983H83.621ZM87.7949 12.504C90.1269 10.8393 91.2929 9.51567 91.2929 8.533C91.2929 7.93167 91.0326 7.631 90.5119 7.631C90.2186 7.631 90.0022 7.73367 89.8629 7.939C89.7309 8.14433 89.6576 8.46333 89.6429 8.896H87.8059C87.8499 7.95 88.1359 7.23867 88.6639 6.762C89.1919 6.28533 89.8592 6.047 90.6659 6.047C91.5092 6.047 92.1436 6.25967 92.5689 6.685C93.0016 7.11033 93.2179 7.664 93.2179 8.346C93.2179 9.10133 92.9282 9.842 92.3489 10.568C91.7696 11.2867 91.0766 11.8953 90.2699 12.394H93.3389V13.912H87.7949V12.504ZM98.1394 14L96.8854 12.251L95.8624 14H93.7944L95.8294 10.799L93.7174 7.862H95.8294L97.0724 9.589L98.0954 7.862H100.163L98.0844 10.986L100.251 14H98.1394Z"
                fill="white" />
            </svg>
          </p>
        </div>
      </div>
    </div>

    <div class="mt-5 w-lg-75">
      <div class="d-flex flex-wrap align-items-center gap-16">
        <p class="h3 mb-0">Choix du financement</p>
        <select class="form-control form-control-select w-max-content" id="payment-method" name="payment-method">
          <option value="paypal" title="Paypal" selected="selected">Sélectionner un moyen de financement</option>
          <option value="paypal" title="Paypal">Paypal</option>
          <option value="oney" title="Oney">Oney</option>
          <option value="fintecture" title="Fintecture">Fintecture</option>
        </select>
      </div>

      <div class="card-bg-sand-light px-4 px-md-5 py-4 mt-4">
        <div class="d-flex justify-content-between">
          <p>Montant total financé</p>
          <p>305,85</p>
        </div>
        <div class="d-flex justify-content-between font-weight-bold">
          <p>Apport de suivi de 2 mensualités à hauteur de</p>
          <div>
            <p>101,95 €</p>
            <p>101,95 €</p>
          </div>
        </div>
        <div class="d-flex justify-content-between">
          <p>Dont coût de financement</p>
          <p>0,00 €</p>
        </div>
        <div class="d-flex justify-content-between">
          <p>TAEG</p>
          <p>0,00 %</p>
        </div>
      </div>

      <div class="mt-4">
        <a href="#" class="text-primary font-weight-bold mb-1 d-block"><u>En savoir plus</u></a>
        <p class="small text-uppercase">Un crédit vous engage et doit être remboursé. Vérifiez vos capacités de
          remboursement avant de vous engager.</p>
      </div>
    </div>

    {hook h='displayCrossSellingShoppingCart'}

  </section>
{/block}