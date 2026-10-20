<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->
<div class="col-lg-9">
    <div class="form-group">
        {foreach from=$languages item=language name=index_lang}
            <div class="translatable-field lang-{$language.id_lang|escape:'htmlall':'UTF-8'}"
                 {if $language.id_lang != $id_lang_context}style="display:none" {/if}>
                <div class="col-lg-9">
                    <input type="text" id="SEO_{$type_name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}"
                           name="SEO_{$type_name|escape:'htmlall':'UTF-8'}_{$language.id_lang|escape:'htmlall':'UTF-8'}"
                           class=""
                           value="{$seo_url[$type_name][$language.id_lang]|escape:'htmlall':'UTF-8'}"
                           onkeyup="if (isArrowKey(event)) return ;updateFriendlyURL();">
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1"
                            data-toggle="dropdown">
                        {$language.iso_code|escape:'htmlall':'UTF-8'}
                        <i class="icon-caret-down"></i>
                    </button>
                    <ul class="dropdown-menu">
                        {foreach from=$languages item=language_bis}
                            <li><a href="javascript:hideOtherLanguage({$language_bis.id_lang|escape:'htmlall':'UTF-8'});"
                                   tabindex="-1">{$language_bis.name|escape:'htmlall':'UTF-8'}</a></li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        {/foreach}
    </div>
</div>
