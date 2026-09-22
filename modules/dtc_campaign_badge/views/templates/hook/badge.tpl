{**
 * Badge campagne — bas droite
 *}
{if $dtc_campaign_badge.link}
  <a href="{$dtc_campaign_badge.link|escape:'html':'UTF-8'}" class="dtc-campaign-badge" aria-label="{$dtc_campaign_badge.title|escape:'html':'UTF-8'}" style="background-image: url('{$dtc_campaign_badge.bg|escape:'html':'UTF-8'}');">
    <span class="dtc-campaign-badge__title">{$dtc_campaign_badge.title|escape:'html':'UTF-8'}</span>
    <span class="dtc-campaign-badge__text">{$dtc_campaign_badge.text nofilter}</span>
  </a>
{else}
  <div class="dtc-campaign-badge" role="status" aria-label="{$dtc_campaign_badge.title|escape:'html':'UTF-8'}" style="background-image: url('{$dtc_campaign_badge.bg|escape:'html':'UTF-8'}');">
    <span class="dtc-campaign-badge__title">{$dtc_campaign_badge.title|escape:'html':'UTF-8'}</span>
    <span class="dtc-campaign-badge__text">{$dtc_campaign_badge.text nofilter}</span>
  </div>
{/if}
