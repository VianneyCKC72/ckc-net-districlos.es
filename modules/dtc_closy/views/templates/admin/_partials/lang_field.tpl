<div class="form-group">
    <label class="control-label col-lg-3">{$dtc_lang_label|escape:'html':'UTF-8'}</label>
    <div class="col-lg-9">
        {foreach from=$dtc_closy_languages item=language}
            <div class="form-group translatable-field lang-{$language.id_lang}"{if $language.id_lang != $dtc_closy_id_lang} style="display:none;"{/if}>
                <div class="col-lg-7">
                    {if $dtc_lang_type == 'textarea'}
                        <textarea name="{$dtc_lang_name}_{$language.id_lang}" class="form-control" rows="3">{$dtc_lang_values[$language.id_lang]|escape:'html':'UTF-8'}</textarea>
                    {else}
                        <input type="text" name="{$dtc_lang_name}_{$language.id_lang}" value="{$dtc_lang_values[$language.id_lang]|escape:'html':'UTF-8'}" class="form-control">
                    {/if}
                </div>
                {if $dtc_closy_languages|count > 1}
                    <div class="col-lg-2">
                        <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
                            {$language.iso_code|escape:'html':'UTF-8'}
                            <i class="icon-caret-down"></i>
                        </button>
                        <ul class="dropdown-menu">
                            {foreach from=$dtc_closy_languages item=lang}
                                <li><a href="javascript:hideOtherLanguage({$lang.id_lang|intval});">{$lang.name|escape:'html':'UTF-8'}</a></li>
                            {/foreach}
                        </ul>
                    </div>
                {/if}
            </div>
        {/foreach}
        {if isset($dtc_lang_help) && $dtc_lang_help}
            <p class="help-block">{$dtc_lang_help nofilter}</p>
        {/if}
    </div>
</div>
