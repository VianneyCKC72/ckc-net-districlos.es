{if isset($language.iso_code) && $language.iso_code != 'fr'}
  {assign var='ckc_price_dec' value='.' scope='parent'}
{else}
  {assign var='ckc_price_dec' value=',' scope='parent'}
{/if}
