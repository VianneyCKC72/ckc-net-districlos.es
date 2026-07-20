{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}

  {hook h='displayPaymentTop'}

  {* used by javascript to correctly handle cart updates when we are on payment step (eg vouchers added) *}
  <div style="display:none" class="js-cart-payment-step-refresh"></div>

  {if !empty($display_transaction_updated_info)}
    <p class="cart-payment-step-refreshed-info">
      {l s='Transaction amount has been correctly updated' d='Shop.Theme.Checkout'}
    </p>
  {/if}

  {if $is_free}
    <p class="cart-payment-step-not-needed-info">{l s='No payment needed for this order' d='Shop.Theme.Checkout'}</p>
  {/if}
  <div class="payment-options {if $is_free}hidden-xs-up{/if}">
    {foreach from=$payment_options item="module_options"}
      {foreach from=$module_options item="option"}
        {assign var='is_quote_payment' value=($option.module_name == 'opartdevis' or $option.call_to_action_text|strstr:'devis' or $option.call_to_action_text|strstr:'Devis')}
        <div class="payment-option-card{if $is_quote_payment} payment-option-card--quote{/if}{if ($selected_payment_option == $option.id || $is_free) || ($payment_options|@count === 1 && $module_options|@count === 1)} -selected{/if}"
             data-payment-option="{$option.id}">
          <div id="{$option.id}-container" class="payment-option clearfix">
            {* This is the way an option should be selected when Javascript is enabled *}
            <span class="custom-radio">
              <input class="ps-shown-by-js {if $option.binary} binary {/if}" id="{$option.id}"
                     data-module-name="{$option.module_name}" name="payment-option" type="radio" required
                {if ($selected_payment_option == $option.id || $is_free) || ($payment_options|@count === 1 && $module_options|@count === 1)}
                checked {/if}>
              <span></span>
            </span>
            {* This is the way an option should be selected when Javascript is disabled *}
            <form method="GET" class="ps-hidden-by-js">
              {if $option.id === $selected_payment_option}
                {l s='Selected' d='Shop.Theme.Checkout'}
              {else}
                <button class="ps-hidden-by-js" type="submit" name="select_payment_option" value="{$option.id}">
                  {l s='Choose' d='Shop.Theme.Actions'}
                </button>
              {/if}
            </form>

            {* Descriptions courtes sous le titre (PrestaShop n'a pas de champ dédié) *}
            {capture assign='payment_option_desc'}
              {if $option.module_name == 'ps_cashondelivery'}
                {l s='Vous payez lors de la livraison de votre commande' d='Shop.Theme.Checkout'}
              {elseif $option.module_name == 'ps_wirepayment'}
              {elseif $option.module_name == 'everpsadvancepayment'}
                {l s='Vous payez un acompte, le solde sera facturé ensuite' d='Shop.Theme.Checkout'}
              {elseif $option.module_name == 'payplug'}
                {if $option.call_to_action_text|strstr:'carte' or $option.call_to_action_text|strstr:'Carte' or $option.call_to_action_text|strstr:'card' or $option.call_to_action_text|strstr:'Card'}
                  {l s='Carte bleue, Visa, Mastercard' d='Shop.Theme.Checkout'}
                {else}
                  {l s='Paiement sécurisé en ligne' d='Shop.Theme.Checkout'}
                {/if}
              {elseif $option.module_name == 'paypal' or $option.module_name == 'paypal_bnpl'}
                {l s='Au comptant ou quatre fois sans frais' d='Shop.Theme.Checkout'}
              {elseif $option.module_name == 'ckc_paywithphone' or $option.call_to_action_text|strstr:'éléphone' or $option.call_to_action_text|strstr:'Phone'}
                {l s='Veuillez nous appeler immédiatement après la confirmation de votre commande pour procéder au paiement. Vous recevrez une confirmation de commande par e-mail contenant les détails et le numéro de commande. Nous traiterons la commande immédiatement après réception du paiement.' d='Shop.Theme.Checkout'}
              {elseif $option.module_name == 'mh_oney' or $option.module_name == 'ckc_oney_simulator'}
                {l s='Payez en plusieurs fois avec Oney' d='Shop.Theme.Checkout'}
              {elseif $option.module_name == 'local_payment_hipay'}
                {if $option.call_to_action_text|strstr:'3x' or $option.call_to_action_text|strstr:'4x'}
                  {l s='Payez en 3 ou 4 fois sans frais avec votre carte bancaire' d='Shop.Theme.Checkout'}
                {elseif $option.call_to_action_text|strstr:'Apple' or $option.call_to_action_text|strstr:'apple'}
                {else}
                  {l s='Paiement sécurisé en ligne' d='Shop.Theme.Checkout'}
                {/if}
              {elseif $option.module_name == 'credit_card'}
                {l s='Carte bleue, Visa, Mastercard' d='Shop.Theme.Checkout'}
              {/if}
            {/capture}

            <label for="{$option.id}">
              <span class="payment-option__row">
                <span class="payment-option__text">
                  <span class="payment-option__title">{$option.call_to_action_text}</span>
                  {if $payment_option_desc|trim}
                    <span class="payment-option__desc">{$payment_option_desc|trim}</span>
                  {/if}
                </span>
                <span class="payment-option__logos">
                  {if $option.logo && $option.module_name != 'everpsadvancepayment'}
                    <img src="{$option.logo}" alt="{$option.name|default:''}" loading="lazy">
                  {/if}
                </span>
              </span>
            </label>
          </div>

          {* Masquer les infos additionnelles pour certains modes (déjà décrits sous le titre) *}
          {assign var='hide_payment_additional' value=false}
          {if $option.module_name == 'ckc_paywithphone'
            or $option.module_name == 'opartdevis'
            or $option.module_name == 'everpsadvancepayment'
            or $option.module_name == 'ps_cashondelivery'
            or $option.call_to_action_text|strstr:'éléphone'
            or $option.call_to_action_text|strstr:'devis'
            or $option.call_to_action_text|strstr:'Devis'
            or $option.call_to_action_text|strstr:'acompte'
            or $option.call_to_action_text|strstr:'Acompte'
            or $option.call_to_action_text|strstr:'comptant à la livraison'
            or $option.call_to_action_text|strstr:'Comptant à la livraison'
          }
            {assign var='hide_payment_additional' value=true}
          {/if}

          {if $option.additionalInformation && !$hide_payment_additional}
            <div id="{$option.id}-additional-information"
                 class="js-additional-information definition-list additional-information{if $option.id != $selected_payment_option} ps-hidden {/if}">
              {$option.additionalInformation nofilter}
            </div>
          {/if}

          {* Formulaires sans contenu visible (ex. acompte) : pas d'espace réservé à la sélection *}
          {assign var='payment_form_is_empty' value=!$option.form}
          <div id="pay-with-{$option.id}-form"
               class="js-payment-option-form{if $payment_form_is_empty} js-payment-option-form--empty{/if}{if $option.id != $selected_payment_option} ps-hidden{/if}">
            {if $option.form}
              {$option.form nofilter}
            {else}
              <form id="payment-{$option.id}-form" method="POST" action="{$option.action nofilter}">
                {foreach from=$option.inputs item=input}
                  <input type="{$input.type}" name="{$input.name}" value="{$input.value}">
                {/foreach}
                <button style="display:none" id="pay-with-{$option.id}" type="submit"></button>
              </form>
            {/if}
          </div>
        </div>
      {/foreach}
      {foreachelse}
      <p class="alert alert-danger">{l s='Unfortunately, there are no payment method available.' d='Shop.Theme.Checkout'}
      </p>
    {/foreach}
  </div>

  <div class="js-payment-checkout-actions payment-checkout-actions">
  {if $conditions_to_approve|count}
    <p class="ps-hidden-by-js">
      {* At the moment, we're not showing the checkboxes when JS is disabled
         because it makes ensuring they were checked very tricky and overcomplicates
         the template. Might change later.
      *}
      {l s='By confirming the order, you certify that you have read and agree with all of the conditions below:' d='Shop.Theme.Checkout'}
    </p>

    <form id="conditions-to-approve" class="js-conditions-to-approve" method="GET">
      <ul>
        {foreach from=$conditions_to_approve item="condition" key="condition_name"}
          <li class="condition-row">
            <span class="custom-checkbox">
              <input id="conditions_to_approve[{$condition_name}]"
                     name="conditions_to_approve[{$condition_name}]"
                     required
                     type="checkbox"
                     value="1"
                     class="ps-shown-by-js">
              <span aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                     class="lucide lucide-check checkbox-checked">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              </span>
            </span>
            <label class="condition-label" for="conditions_to_approve[{$condition_name}]">
              {l s='J\'ai lu les conditions générales de vente et j\'y adhère sans réserve.' d='Shop.Theme.Checkout'}*
            </label>
          </li>
        {/foreach}
      </ul>
    </form>
  {/if}

  <div id="payment-confirmation" class="js-payment-confirmation mt-4">
    <div class="ps-shown-by-js">
      <div class="payment-confirmation__actions">
        <a href="#" class="js-checkout-prev-step payment-confirmation__prev">
          {l s='Etape précédente' d='Shop.Theme.Checkout'}
        </a>
        <button type="submit"
                class="btn btn-primary payment-confirmation__submit{if !$selected_payment_option} disabled{/if}">
          {l s='PASSER COMMANDE' d='Shop.Theme.Checkout'}
        </button>
      </div>
      {if $show_final_summary}
        <article class="alert alert-danger mt-3 js-alert-payment-conditions" role="alert" data-alert="danger">
          {l
          s='Veuillez vous assurer que vous avez sélectionné un [1]moyen de paiement[/1] et accepté les [2]conditions générales de vente[/2].'
          sprintf=[
          '[1]' => '<a href="#checkout-payment-step">',
          '[/1]' => '</a>',
          '[2]' => '<a href="#conditions-to-approve">',
          '[/2]' => '</a>'
          ]
          d='Shop.Theme.Checkout'
          }
        </article>
      {/if}
    </div>
    <div class="ps-hidden-by-js">
      {if $selected_payment_option and $all_conditions_approved}
        <label
                for="pay-with-{$selected_payment_option}">{l s='Order with an obligation to pay' d='Shop.Theme.Checkout'}</label>
      {/if}
    </div>
  </div>
  </div>
  <div class="js-payment-checkout-actions-anchor" hidden></div>

  {hook h='displayCheckoutBeforeConfirmation'}

  {if $show_final_summary}
    {include file='checkout/_partials/order-final-summary.tpl'}
  {/if}

  {hook h='displayPaymentByBinaries'}
{/block}
