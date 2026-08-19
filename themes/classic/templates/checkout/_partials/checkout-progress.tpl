{**
 * Fil d'Ariane des étapes du checkout
 *}
<nav class="checkout-progress" aria-label="{l s='Checkout steps' d='Shop.Theme.Checkout'}">
  <ol class="checkout-progress__list">
    {foreach from=$steps item="step" key="index" name="checkoutProgress"}
      <li class="{[
          'checkout-progress__item' => true,
          '-active' => $step.is_current,
          '-complete' => $step.is_complete,
          '-reachable' => $step.is_reachable && !$step.is_current,
          '-unreachable' => !$step.is_reachable
        ]|classnames}"
        data-step="{$step.identifier|escape:'html':'UTF-8'}"
      >
        {if $step.identifier == 'checkout-personal-information-step'}
          {assign var='progress_label' value={l s='Informations' d='Shop.Theme.Checkout'}}
        {elseif $step.identifier == 'checkout-delivery-step'}
          {assign var='progress_label' value={l s='Livraison' d='Shop.Theme.Checkout'}}
        {else}
          {assign var='progress_label' value=$step.title}
        {/if}
        {if $step.is_reachable}
          <button type="button"
            class="checkout-progress__btn{if !$step.is_current} js-checkout-progress-step{/if}"
            data-step-target="{$step.identifier|escape:'html':'UTF-8'}"
            {if $step.is_current}aria-current="step"{/if}
            {if $step.is_current}disabled{/if}
            aria-label="{$progress_label|escape:'html':'UTF-8'}"
          >
        {else}
          <span class="checkout-progress__btn">
        {/if}

          <span class="checkout-progress__icon" aria-hidden="true">
            {if $step.identifier == 'checkout-personal-information-step'}
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            {elseif $step.identifier == 'checkout-addresses-step'}
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
            {elseif $step.identifier == 'checkout-delivery-step'}
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
            {else}
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
            {/if}
          </span>

          <span class="checkout-progress__label">
            <span class="checkout-progress__index">étape {$index + 1}</span>
            <span class="checkout-progress__name">{$progress_label|escape:'html':'UTF-8'}</span>
          </span>

        {if $step.is_reachable}
          </button>
        {else}
          </span>
        {/if}
      </li>
    {/foreach}
  </ol>
</nav>

<script>
  (function () {
    var progress = document.currentScript && document.currentScript.previousElementSibling;
    if (!progress || !progress.classList || !progress.classList.contains('checkout-progress')) {
      progress = document.querySelector('.checkout-progress');
    }
    var topbar = document.querySelector('.checkout-topbar');
    // Déplacer avant le 1er paint des colonnes (paiement / récap) pour éviter le CLS
    if (progress && topbar && progress.parentNode !== topbar) {
      topbar.insertAdjacentElement('afterbegin', progress);
    }
  })();

  document.addEventListener('DOMContentLoaded', function () {
    var progress = document.querySelector('.checkout-progress');
    if (!progress) {
      return;
    }

    function syncCheckoutProgress() {
      progress.querySelectorAll('.checkout-progress__item').forEach(function (item) {
        var id = item.getAttribute('data-step');
        var step = id ? document.getElementById(id) : null;
        if (!step) {
          return;
        }
        var isCurrent = step.classList.contains('-current') || step.classList.contains('js-current-step');
        var isComplete = step.classList.contains('-complete');
        var isReachable = step.classList.contains('-reachable');
        item.classList.toggle('-active', isCurrent);
        item.classList.toggle('-complete', isComplete);
        item.classList.toggle('-reachable', isReachable && !isCurrent);
        item.classList.toggle('-unreachable', !isReachable);

        var btn = item.querySelector('button.checkout-progress__btn');
        if (btn) {
          btn.classList.toggle('js-checkout-progress-step', isReachable && !isCurrent);
          btn.disabled = isCurrent;
          if (isCurrent) {
            btn.setAttribute('aria-current', 'step');
          } else {
            btn.removeAttribute('aria-current');
          }
        }
      });
    }

    progress.addEventListener('click', function (e) {
      var btn = e.target.closest('.js-checkout-progress-step');
      if (!btn || !progress.contains(btn)) {
        return;
      }
      e.preventDefault();
      var targetId = btn.getAttribute('data-step-target');
      if (!targetId) {
        return;
      }
      var stepTitle = document.querySelector('#' + targetId + ' .js-step-title');
      if (stepTitle) {
        stepTitle.click();
        syncCheckoutProgress();
      }
    });

    document.querySelectorAll('.checkout-step .js-step-title').forEach(function (title) {
      title.addEventListener('click', function () {
        setTimeout(syncCheckoutProgress, 0);
      });
    });

    document.addEventListener('click', function (e) {
      var prevBtn = e.target.closest('.js-checkout-prev-step');
      if (!prevBtn) {
        return;
      }
      e.preventDefault();
      var current = document.querySelector('.checkout-step.js-current-step, .checkout-step.-current');
      if (!current) {
        return;
      }
      var previous = current.previousElementSibling;
      while (previous && (!previous.classList || !previous.classList.contains('checkout-step'))) {
        previous = previous.previousElementSibling;
      }
      if (previous && previous.classList.contains('-reachable')) {
        var prevTitle = previous.querySelector('.js-step-title');
        if (prevTitle) {
          prevTitle.click();
          syncCheckoutProgress();
        }
      }
    });
  });
</script>
