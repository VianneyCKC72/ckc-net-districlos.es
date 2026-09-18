<div class="dtc-club-pro dtc-club-pro--account">
  <h2 class="dtc-club-pro__title">{l s='Le Club Pro' d='Shop.Theme.Customdtc'}</h2>

  <div class="dtc-club-pro__top">
    <div class="dtc-club-pro__progress-track" aria-hidden="true">
      <div class="dtc-club-pro__progress-fill" style="width: {$dtc_club.progress}%"></div>
      {foreach from=$dtc_club.tiers item=tier}
        <span class="dtc-club-pro__progress-marker" style="left: {$tier.position}%"></span>
      {/foreach}
    </div>

    <div class="dtc-club-pro__summary">
      <p class="dtc-club-pro__summary-label">{l s='Votre cumul sur les 12 derniers mois :' d='Shop.Theme.Customdtc'}</p>
      <p class="dtc-club-pro__summary-value">
        {$dtc_club.amount|number_format:0:',':' '} € HT
        {if $dtc_club.current_name}
          — {l s='Statut' d='Shop.Theme.Customdtc'}
          {$dtc_club.current_name|replace:'Membre ':''|escape:'htmlall':'UTF-8'}
          ({$dtc_club.current_discount|string_format:"%g"}%)
        {else}
          — {l s='Aucun statut atteint' d='Shop.Theme.Customdtc'}
        {/if}
      </p>
    </div>

    <div class="dtc-club-pro__progress-labels" aria-hidden="true">
      {foreach from=$dtc_club.tiers item=tier}
        <span class="dtc-club-pro__progress-label{if $tier.is_current} is-current{/if}" style="left: {$tier.position}%">
          {$tier.name|escape:'htmlall':'UTF-8'}
        </span>
      {/foreach}
    </div>
  </div>

  <p class="dtc-club-pro__intro">
    {l s='Notre programme de fidélité est exclusivement réservé à nos clients professionnels sur le web. Votre statut est déterminé par le cumul de vos commandes sur les 12 derniers mois glissants :' d='Shop.Theme.Customdtc'}
  </p>

  <div class="dtc-club-pro__cards">
    {include file='module:dtc_paliers_remise/views/templates/hook/_partials/tiers-cards.tpl' dtc_club_tiers=$dtc_club.tiers}
  </div>

  <p class="dtc-club-pro__howto">
    {l s='Concrètement, chaque nouvelle commande s\'ajoute à votre cumul, et chaque commande de plus d\'un an en sort progressivement. Votre statut évolue donc en continu, à la hausse comme à la baisse, selon votre activité récente. Pour le conserver ou le faire progresser, l\'essentiel est de commander régulièrement plutôt qu\'une seule fois sur douze mois.' d='Shop.Theme.Customdtc'}
  </p>

  <div class="dtc-club-pro__status">
    <h3 class="dtc-club-pro__status-title">{l s='Votre statut' d='Shop.Theme.Customdtc'}</h3>
    <p class="dtc-club-pro__status-subtitle">{l s='Vos commandes des 12 derniers mois' d='Shop.Theme.Customdtc'}</p>

    <div class="dtc-club-pro__status-grid">
      <div class="dtc-club-pro__orders">
        {if $dtc_club.orders|@count > 0}
          <table>
            <tbody>
              {foreach from=$dtc_club.orders item=order}
                <tr>
                  <td>{$order.date}</td>
                  <td>{$order.amount|number_format:2:',':' '} € HT</td>
                </tr>
              {/foreach}
            </tbody>
          </table>
        {else}
          <p class="dtc-club-pro__empty">{l s='Aucune commande sur les 12 derniers mois.' d='Shop.Theme.Customdtc'}</p>
        {/if}
      </div>

      <div class="dtc-club-pro__recap">
        <div class="dtc-club-pro__recap-item">
          <span>{l s='Cumul sur 12 mois' d='Shop.Theme.Customdtc'}</span>
          <strong>{$dtc_club.amount|number_format:0:',':' '} €</strong>
        </div>
        <div class="dtc-club-pro__recap-item">
          <span>{l s='Statut' d='Shop.Theme.Customdtc'}</span>
          <strong>
            {if $dtc_club.current_name}
              {$dtc_club.current_name|escape:'htmlall':'UTF-8'}
            {else}
              —
            {/if}
          </strong>
        </div>
        <div class="dtc-club-pro__recap-item">
          <span>{l s='Remise' d='Shop.Theme.Customdtc'}</span>
          <strong>{$dtc_club.current_discount|string_format:"%g"}%</strong>
        </div>
      </div>
    </div>
  </div>
</div>
