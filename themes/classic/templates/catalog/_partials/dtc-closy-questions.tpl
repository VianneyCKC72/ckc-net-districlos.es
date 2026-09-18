{**
 * Closy — bulles questions fiche produit (i18n thème, module dtc_closy non patché)
 *}
{if Module::isInstalled('dtc_closy') && Module::isEnabled('dtc_closy') && isset($product)}
  {capture assign='closy_q_delivery'}{l s='Quel sont les délais de livraison ?' d='Shop.Theme.Customdtc'}{/capture}
  <div class="dtc-closy-questions dtc-closy-questions--i18n"
       data-product-id="{$product.id}"
       data-product-url="{$product.url}"
       data-product-name="{$product.name|escape:'html':'UTF-8'}"
       data-panel-enabled="1">
    <div class="dtc-closy-questions__header">
      <span class="dtc-closy-questions__icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
          <path d="M12 17h.01"></path>
        </svg>
      </span>
      <p class="dtc-closy-questions__title">{l s='Une question sur le produit ?' d='Shop.Theme.Customdtc'}</p>
    </div>
    <div class="dtc-closy-questions__list">
      <button type="button"
              class="dtc-closy-questions__bubble dtc-closy-questions__bubble--static"
              data-question="{$closy_q_delivery|escape:'html':'UTF-8'}">
        {$closy_q_delivery|escape:'html':'UTF-8'}
      </button>
      <button type="button"
              class="dtc-closy-questions__bubble dtc-closy-questions__bubble--cta"
              data-action="open-chat">
        {l s='Posez votre question' d='Shop.Theme.Customdtc'}
      </button>
    </div>
  </div>
{/if}
