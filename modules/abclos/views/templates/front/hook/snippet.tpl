{**
 * AbClos — snippet front (displayHeader).
 * Anti-flicker toujours actif. Tracking panier / revenue via JS du module.
 *}
{literal}
<style id="abclos-antiflicker">.abclos-hide{opacity:0!important}</style>
<script>
document.documentElement.classList.add('abclos-hide');
setTimeout(function () {
  document.documentElement.classList.remove('abclos-hide');
}, 1500);
</script>
{/literal}
<script
  src="{$abclos_snippet_url|escape:'html':'UTF-8'}"
  data-abclos-key="{$abclos_api_key|escape:'html':'UTF-8'}"
  data-abclos-endpoint="{$abclos_endpoint|escape:'html':'UTF-8'}"
  {if isset($abclos_debug) && $abclos_debug}data-abclos-debug="true"{/if}
  async
></script>
