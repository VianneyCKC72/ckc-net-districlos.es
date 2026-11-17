{*
* Panneau Closy + lanceur flottant (toutes pages sauf panier / commande)
*}
<button type="button"
        id="dtc-closy-launcher"
        class="dtc-closy-launcher"
        data-dtc-closy-open
        aria-label="{l s='Ouvrir l’assistant Closy' mod='dtc_closy'}">
    <img src="{$dtc_closy_launcher_icon_url|escape:'html':'UTF-8'}"
         alt=""
         width="96"
         height="96"
         loading="lazy">
</button>

<div id="dtc-pq-panel"
     class="dtc-pq-panel"
     aria-hidden="true"
     {if isset($dtc_closy_panel_id_product) && $dtc_closy_panel_id_product}data-product-id="{$dtc_closy_panel_id_product|intval}"{/if}>
    <div class="dtc-pq-panel__overlay" data-dtc-pq-close tabindex="-1"></div>
    <aside class="dtc-pq-panel__drawer" role="dialog" aria-modal="true" aria-labelledby="dtc-pq-panel-title">
        <header class="dtc-pq-panel__header">
            <div class="dtc-pq-panel__header-text">
                <img id="dtc-pq-panel-title"
                     class="dtc-pq-panel__logo"
                     src="{$dtc_closy_panel_logo_url|escape:'html':'UTF-8'}"
                     alt="{$dtc_closy_panel_title|escape:'html':'UTF-8'}"
                     width="160"
                     height="48"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="dtc-pq-panel__header-actions">
                <p class="dtc-pq-panel__status" role="status">
                    <span class="dtc-pq-panel__status-dot" aria-hidden="true"></span>
                    {l s='En ligne' mod='dtc_closy'}
                </p>
                <button type="button" class="dtc-pq-panel__close" data-dtc-pq-close aria-label="{l s='Fermer' mod='dtc_closy'}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </header>

        {if isset($dtc_closy_panel_product) && $dtc_closy_panel_product}
            <div class="dtc-pq-panel__product-card" data-dtc-pq-product-card>
                <a href="{$dtc_closy_panel_product.url|escape:'html':'UTF-8'}" class="dtc-pq-panel__product-thumb" target="_blank" rel="noopener">
                    {if $dtc_closy_panel_product.image_url}
                        <img src="{$dtc_closy_panel_product.image_url|escape:'html':'UTF-8'}"
                             alt="{$dtc_closy_panel_product.name|escape:'html':'UTF-8'}"
                             width="72"
                             height="72"
                             loading="lazy">
                    {else}
                        <span class="dtc-pq-panel__product-thumb-placeholder" aria-hidden="true"></span>
                    {/if}
                </a>
                <div class="dtc-pq-panel__product-info">
                    <a href="{$dtc_closy_panel_product.url|escape:'html':'UTF-8'}"
                       class="dtc-pq-panel__product-name"
                       target="_blank"
                       rel="noopener">{$dtc_closy_panel_product.name|escape:'html':'UTF-8'}</a>
                    {if $dtc_closy_panel_product.price}
                        <p class="dtc-pq-panel__product-price">{$dtc_closy_panel_product.price|escape:'html':'UTF-8'}</p>
                    {/if}
                </div>
                <a href="{$dtc_closy_panel_product.url|escape:'html':'UTF-8'}"
                   class="dtc-pq-panel__product-cart dtc-pq-panel__product-cart--link"
                   target="_blank"
                   rel="noopener noreferrer">
                    {l s='Voir le produit' mod='dtc_closy'}
                </a>
            </div>
        {/if}

        <div class="dtc-pq-panel__messages" data-dtc-pq-messages role="log" aria-live="polite"></div>

        <footer class="dtc-pq-panel__footer">
            <p class="dtc-pq-panel__disclaimer">{l s='Il peut arriver que l’IA fasse des erreurs.' mod='dtc_closy'}</p>
            <form class="dtc-pq-panel__form" data-dtc-pq-form>
                <label class="sr-only" for="dtc-pq-panel-input">{l s='Votre message' mod='dtc_closy'}</label>
                <textarea id="dtc-pq-panel-input"
                          class="dtc-pq-panel__input"
                          rows="2"
                          placeholder="{l s='Posez votre question…' mod='dtc_closy'}"
                          maxlength="500"></textarea>
                <button type="submit"
                        class="dtc-pq-panel__send"
                        data-dtc-pq-send
                        aria-label="{l s='Envoyer' mod='dtc_closy'}">
                    <svg class="dtc-pq-panel__send-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                        <path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
        </footer>
    </aside>
</div>
