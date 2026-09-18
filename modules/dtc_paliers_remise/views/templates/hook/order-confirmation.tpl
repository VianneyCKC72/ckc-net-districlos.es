<div class="dtc-club-pro-confirm dtc-club-pro-confirm--{$dtc_confirm.level|escape:'htmlall':'UTF-8'}">
  {if $dtc_confirm.mode === 'upgrade'}
    <p class="dtc-club-pro-confirm__title">
      {l s='Félicitation ! Vous êtes passés au niveau supérieur de notre programme Club Pro !' d='Shop.Theme.Customdtc'}
    </p>
    <p class="dtc-club-pro-confirm__text">
      {l s='Grâce à cette commande, votre cumul sur les 12 derniers mois vous fait désormais accéder au statut' d='Shop.Theme.Customdtc'}
      <strong>
        {$dtc_confirm.label|escape:'htmlall':'UTF-8'}
        {l s='— et vous offre' d='Shop.Theme.Customdtc'}
        {$dtc_confirm.discount_display|escape:'htmlall':'UTF-8'}
        {l s='de réduction' d='Shop.Theme.Customdtc'} !
      </strong>
      <br>
      {l s='Cette remise s\'appliquera automatiquement sur vos prochaines commandes.' d='Shop.Theme.Customdtc'}
      <br>
      {l s='Pour la conserver, pensez à commander régulièrement : les commandes de plus d\'un an sortent progressivement du calcul de votre cumul.' d='Shop.Theme.Customdtc'}
    </p>
  {else}
    <p class="dtc-club-pro-confirm__title">
      {l s='Félicitation ! Vous conservez votre statut' d='Shop.Theme.Customdtc'}
      <strong> {$dtc_confirm.label|escape:'htmlall':'UTF-8'} </strong>
      {l s='de notre programme Club Pro !' d='Shop.Theme.Customdtc'}
    </p>
    <p class="dtc-club-pro-confirm__text">
      {l s='Grâce à cette commande, vous maintenez votre cumul sur les 12 prochains mois et conservez ainsi votre statut' d='Shop.Theme.Customdtc'}
      <strong>
        {$dtc_confirm.label|escape:'htmlall':'UTF-8'}
        {l s='— et' d='Shop.Theme.Customdtc'}
        {$dtc_confirm.discount_display|escape:'htmlall':'UTF-8'}
        {l s='de réduction' d='Shop.Theme.Customdtc'} !
      </strong>
      <br>
      {l s='Cette remise s\'appliquera automatiquement sur vos prochaines commandes.' d='Shop.Theme.Customdtc'}
      <br>
      {l s='Pour la conserver, pensez à commander régulièrement : les commandes de plus d\'un an sortent progressivement du calcul de votre cumul.' d='Shop.Theme.Customdtc'}
    </p>
  {/if}

  <p class="dtc-club-pro-confirm__disclaimer">
    {l s='*Sous réserve du paiement de la commande' d='Shop.Theme.Customdtc'}
  </p>

  {if $dtc_confirm.status_url}
    <div class="dtc-club-pro-confirm__actions">
      <a class="btn btn-primary dtc-club-pro-confirm__btn" href="{$dtc_confirm.status_url|escape:'htmlall':'UTF-8'}">
        {l s='Découvrir mon statut actuel' d='Shop.Theme.Customdtc'}
      </a>
    </div>
  {/if}
</div>
