{**
 * Wizard de filtre interactif — rendu dynamique depuis la config du module
 * Variables : wizard_config, wizard_config_json, wizard_category_id, wizard_urls_theme_assets
 *}
<div class="filter-wizard" id="filter-wizard-{$wizard_category_id|intval}" data-wizard-config="{$wizard_config_json|escape:'html':'UTF-8'}">

    <div class="filter-wizard__header">
        <div class="filter-wizard__dots">
            {section name=dots loop=$wizard_config.totalSteps}
                <span class="filter-wizard__dot{if $smarty.section.dots.index == 0} is-active{/if}" data-step="{$smarty.section.dots.index+1}"></span>
            {/section}
        </div>
        <span class="filter-wizard__step-count">
            {$wizard_config.i18n.stepLabel|escape:'html':'UTF-8'} <strong id="fw-current-{$wizard_category_id|intval}">1</strong> / {$wizard_config.totalSteps|intval}
        </span>
        <button type="button" class="filter-wizard__btn-reset-all filter-wizard__btn-reset" aria-label="{$wizard_config.i18n.resetLabel|escape:'html':'UTF-8'}">{$wizard_config.i18n.resetLabel|escape:'html':'UTF-8'}</button>
    </div>

    {foreach $wizard_config.steps as $step key=k}
        <div class="filter-wizard__step{if $k == 0} is-active{/if}" data-step="{$step.step|intval}">
            <p class="filter-wizard__question">{$step.question|escape:'html':'UTF-8'}</p>
            <div class="filter-wizard__options">
                {if isset($step.options_by_style)}
                    {* Étape avec options selon le style (ex. hauteur) *}
                    {foreach $step.options_by_style as $style_key => $opts}
                        <div class="filter-wizard__options-by-style" data-style="{$style_key|escape:'html':'UTF-8'}"{if $style_key != 'pleines'} style="display:none;"{/if}>
                            {foreach $opts as $opt}
                                <label class="filter-wizard__option{if !empty($opt.text_only)} filter-wizard__option--text{/if}" data-name="{$step.name|escape:'html':'UTF-8'}" data-value="{$opt.value|escape:'html':'UTF-8'}">
                                    {if empty($opt.text_only) && !empty($opt.image)}
                                        <span class="filter-wizard__option-img">
                                            <img src="{$wizard_urls_theme_assets|escape:'html':'UTF-8'}img/{$opt.image|escape:'html':'UTF-8'}" alt="{$opt.label|escape:'html':'UTF-8'}" loading="lazy">
                                        </span>
                                    {/if}
                                    <span class="filter-wizard__option-label">{$opt.label|escape:'html':'UTF-8'}</span>
                                </label>
                            {/foreach}
                        </div>
                    {/foreach}
                {else}
                    {* Étape avec options fixes *}
                    {foreach $step.options as $opt}
                        <label class="filter-wizard__option{if !empty($opt.text_only)} filter-wizard__option--text{/if}" data-name="{$step.name|escape:'html':'UTF-8'}" data-value="{$opt.value|escape:'html':'UTF-8'}">
                            {if empty($opt.text_only) && !empty($opt.image)}
                                <span class="filter-wizard__option-img">
                                    <img src="{$wizard_urls_theme_assets|escape:'html':'UTF-8'}img/{$opt.image|escape:'html':'UTF-8'}" alt="{$opt.label|escape:'html':'UTF-8'}" loading="lazy">
                                </span>
                            {/if}
                            <span class="filter-wizard__option-label">{$opt.label|escape:'html':'UTF-8'}</span>
                        </label>
                    {/foreach}
                {/if}
                <div class="filter-wizard__actions">
                    {if $step.step == $wizard_config.totalSteps}
                        <button class="filter-wizard__btn-validate filter-wizard__btn-submit" disabled>{$wizard_config.i18n.seeProducts|escape:'html':'UTF-8'}</button>
                    {else}
                        <button class="filter-wizard__btn-validate" data-next="{$step.step + 1|intval}" disabled>{$wizard_config.i18n.validate|escape:'html':'UTF-8'}</button>
                    {/if}
                    {if $step.step > 1}
                        <button class="filter-wizard__btn-back" data-prev="{$step.step - 1|intval}">{$wizard_config.i18n.back|escape:'html':'UTF-8'}</button>
                    {/if}
                </div>
            </div>
        </div>
    {/foreach}

    <div class="filter-wizard__result" id="fw-result-{$wizard_category_id|intval}">
        <p class="filter-wizard__question">{$wizard_config.i18n.productsShown|escape:'html':'UTF-8'}</p>
        <div class="filter-wizard__result-placeholder" id="fw-products-{$wizard_category_id|intval}"></div>
    </div>
</div>
