{foreach from=$dtc_club_tiers item=tier}
  <div class="dtc-club-pro__card dtc-club-pro__card--{$tier.level}{if !empty($tier.is_current)} is-current{/if}">
    <div class="dtc-club-pro__card-icon" aria-hidden="true">
      <img
        src="{$urls.base_url}modules/dtc_paliers_remise/views/img/star-{$tier.level}.png"
        alt=""
        width="72"
        height="72"
        loading="lazy"
      >
    </div>
    <h3 class="dtc-club-pro__card-title">{$tier.name|escape:'htmlall':'UTF-8'}</h3>
    <p class="dtc-club-pro__card-discount">-{$tier.discount|string_format:"%g"}%</p>
    {if !empty($dtc_club_range_combined)}
      <p class="dtc-club-pro__card-range">
        {if $tier.max}
          {l s='pour toute commande de' d='Shop.Theme.Customdtc'}
          {$tier.min|number_format:0:',':' '}€
          {l s='à' d='Shop.Theme.Customdtc'}
          {$tier.max|number_format:0:',':' '}€ HT
        {else}
          {l s='pour toute commande de +' d='Shop.Theme.Customdtc'}
          {$tier.min|number_format:0:',':' '}€ HT
        {/if}
      </p>
    {else}
      <p class="dtc-club-pro__card-label">{l s='pour toute commande' d='Shop.Theme.Customdtc'}</p>
      <p class="dtc-club-pro__card-range">
        {if $tier.max}
          {l s='de' d='Shop.Theme.Customdtc'}
          {$tier.min|number_format:0:',':' '}€
          {l s='à' d='Shop.Theme.Customdtc'}
          {$tier.max|number_format:0:',':' '}€ HT
        {else}
          {l s='de +' d='Shop.Theme.Customdtc'}
          {$tier.min|number_format:0:',':' '}€ HT
        {/if}
      </p>
    {/if}
  </div>
{/foreach}
