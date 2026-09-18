{if $dtc_tracking_url}
  <div class="dtc-ot-order-confirmation">
    <a class="btn btn-primary" href="{$dtc_tracking_url|escape:'htmlall':'UTF-8'}">
      {l s='Suivre ma commande' mod='dtc_ordertracking'}
    </a>
  </div>
{/if}

