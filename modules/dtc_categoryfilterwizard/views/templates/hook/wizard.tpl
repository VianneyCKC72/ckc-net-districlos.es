{**
 * Category Filter Wizard — Template front-office
 *
 * Variables disponibles :
 *   $dtc_wizard_config   : array  (title, intro, active)
 *   $dtc_wizard_steps    : array  de steps (label, source_type, id_source, options[])
 *   (no besoin d'URL côté client dans cette version)
 **}
<div class="dtc-wizard"
>

    {* En-tête legacy-like : dots + compteur + reset *}
    <div class="dtc-wizard__header" aria-hidden="true">
        <div class="dtc-wizard__progress">
            {foreach $dtc_wizard_steps as $stepIdx => $step}
                <span class="dtc-wizard__dot{if $stepIdx === 0} dtc-wizard__dot--active{/if}"
                      data-step="{$stepIdx + 1|intval}"></span>
            {/foreach}
        </div>

        <span class="dtc-wizard__step-count">
      {l s='Étape' d='Shop.Theme.Customdtc'} <strong class="dtc-wizard__current-step">1</strong> / {$dtc_wizard_steps|count|intval}
    </span>

        <button type="button" class="dtc-wizard__btn dtc-wizard__btn--reset-all"
                aria-label="{l s='Réinitialiser tous les filtres' d='Shop.Theme.Customdtc'}">
            {l s='Réinitialiser' d='Shop.Theme.Customdtc'}
        </button>
    </div>

    {* Étapes *}
    {foreach $dtc_wizard_steps as $stepIdx => $step}
        <div class="dtc-wizard__step{if $stepIdx !== 0} dtc-wizard__step--hidden{/if}"
             data-step="{$stepIdx|intval}"
             data-source-type="{$step.source_type|escape:'htmlall':'UTF-8'}"
             data-source-id="{$step.id_source|intval}">

            <p class="dtc-wizard__step-label">{$step.label|escape:'htmlall':'UTF-8'}</p>

            <div class="dtc-wizard__options">
                {foreach $step.options as $option}
                    {if isset($option.mode) && $option.mode === 'custom'}
                        {assign var="imgSrc" value=$option.image_url}
                        {if isset($option.image_url) && $option.image_url|strlen > 4 && ($option.image_url|substr:0:4) == '/img'}
                            {* Convertit "/img/..." vers "{$urls.theme_assets}img/..." comme dans tes TPL legacy *}
                            {assign var="imgPath" value=$option.image_url|substr:1}
                            {assign var="imgSrc" value=$urls.theme_assets|cat:$imgPath}
                        {/if}
                        <button type="button"
                                class="dtc-wizard__option"
                                data-mode="custom"
                                data-option-index="{$option.option_index|intval}"
                                data-label="{$option.label|escape:'htmlall':'UTF-8'}"
                                data-product-ids="{$option.product_ids_csv|escape:'htmlall':'UTF-8'}">
                            {if !empty($option.image_url)}
                                <img class="dtc-wizard__option-img" src="{$imgSrc|escape:'htmlall':'UTF-8'}" alt="{$option.label|escape:'htmlall':'UTF-8'}" loading="lazy">
                            {/if}
                            <span class="dtc-wizard__option-label">{$option.label|escape:'htmlall':'UTF-8'}</span>
                        </button>
                    {else}
                        <button type="button"
                                class="dtc-wizard__option"
                                data-mode="native"
                                data-value="{$option.id|intval}"
                                data-label="{$option.label|escape:'htmlall':'UTF-8'}">
                            <span class="dtc-wizard__option-label">{$option.label|escape:'htmlall':'UTF-8'}</span>
                        </button>
                    {/if}
                    {foreachelse}
                    <p class="dtc-wizard__empty">{l s='Aucune option disponible.' d='Shop.Theme.Customdtc'}</p>
                {/foreach}

                {* Nav dans le même flux flex que les options → se place juste après la dernière *}
                <div class="dtc-wizard__nav">
                    {if $stepIdx > 0}
                        <button type="button" class="dtc-wizard__btn dtc-wizard__btn--prev">
                            ← {l s='Retour' d='Shop.Theme.Customdtc'}
                        </button>
                    {/if}
                    {if !$step@last}
                        <button type="button"
                                class="dtc-wizard__btn dtc-wizard__btn--next"
                                disabled>
                            {l s='Suivant' d='Shop.Theme.Customdtc'} →
                        </button>
                    {else}
                        <button type="button"
                                class="dtc-wizard__btn dtc-wizard__btn--submit"
                                disabled>
                            {l s='Voir les produits' d='Shop.Theme.Customdtc'}
                        </button>
                    {/if}
                </div>
            </div>
        </div>
    {/foreach}

    {* Note: pas d'appel AJAX côté client dans cette version. *}
</div>
