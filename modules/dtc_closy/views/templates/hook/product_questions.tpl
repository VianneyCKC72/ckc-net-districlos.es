{*
* dtc_closy — bulles sous la galerie (hook displayProductBelowGallery)
*}
{if isset($dtc_closy_ai_questions) || $dtc_closy_static_transport || $dtc_closy_cta_label}
<div class="dtc-closy-questions"
     data-product-id="{$dtc_closy_id_product|intval}"
     {if isset($dtc_closy_product_url) && $dtc_closy_product_url}data-product-url="{$dtc_closy_product_url|escape:'html':'UTF-8'}"{/if}
     {if isset($dtc_closy_product_name) && $dtc_closy_product_name}data-product-name="{$dtc_closy_product_name|escape:'html':'UTF-8'}"{/if}
     {if isset($dtc_closy_panel_enabled) && $dtc_closy_panel_enabled}data-panel-enabled="1"{/if}>
    <div class="dtc-closy-questions__header">
        <span class="dtc-closy-questions__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                <path d="M12 17h.01"/>
            </svg>
        </span>
        <p class="dtc-closy-questions__title">{$dtc_closy_header_label|escape:'html':'UTF-8'}</p>
    </div>

    <div class="dtc-closy-questions__list">
        {if isset($dtc_closy_ai_questions) && $dtc_closy_ai_questions|count > 0}
            {foreach from=$dtc_closy_ai_questions item=question}
                <button type="button"
                        class="dtc-closy-questions__bubble"
                        data-question="{$question|escape:'html':'UTF-8'}">
                    {$question|escape:'html':'UTF-8'}
                </button>
            {/foreach}
        {/if}

        {if $dtc_closy_static_transport}
            <button type="button"
                    class="dtc-closy-questions__bubble dtc-closy-questions__bubble--static"
                    data-question="{$dtc_closy_static_transport|escape:'html':'UTF-8'}">
                {$dtc_closy_static_transport|escape:'html':'UTF-8'}
            </button>
        {/if}

        {if $dtc_closy_cta_label}
            <button type="button"
                    class="dtc-closy-questions__bubble dtc-closy-questions__bubble--cta"
                    data-action="open-chat">
                {$dtc_closy_cta_label|escape:'html':'UTF-8'}
            </button>
        {/if}
    </div>
</div>
{/if}
