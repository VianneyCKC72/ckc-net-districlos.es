{**
 * Bandeau réservation panier (compte à rebours visuel)
 *}
{if isset($dtc_reservation_duration_seconds) && $dtc_reservation_duration_seconds > 0}
<div class="dtc-reserved-banner" id="dtc-reserved-banner"
     style="display: none;"
     aria-live="polite"
     data-duration="{$dtc_reservation_duration_seconds|intval}"
     data-deadline-key="dtc_cart_reservation_deadline"
     data-reset-flag-key="dtc_cart_reservation_reset"
     data-label-min="{l s='min' d='Shop.Theme.Customdtc' js=1}"
     data-label-sec="{l s='sec' d='Shop.Theme.Customdtc' js=1}">
    <svg class="dtc-rb-clock" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
    </svg>
    <span class="dtc-rb-text">
        <strong>{l s='Produits réservés :' d='Shop.Theme.Customdtc'}</strong>
        {l s='Finalisez votre commande pour garantir leur disponibilité.' d='Shop.Theme.Customdtc'}
        <span class="dtc-rb-timer"></span>
    </span>
</div>
{/if}
