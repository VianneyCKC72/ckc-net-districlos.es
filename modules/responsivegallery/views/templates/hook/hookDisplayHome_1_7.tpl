<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->
{if count($gallery_items) > 0}
    <div id="gallery_home">
        <p class="h1">{$RG_TITLE|escape:'html':'UTF-8'}</p>

        <div id="gallery_desc">{$RG_DESCRIPTION nofilter}</div> {* cannot be escape *}

        <ul id="gallery">
            {foreach from=$gallery_items item='item'}
                {include file='module:responsivegallery/views/templates/front/galleryoneitem.tpl' item=$item page=1}
            {/foreach}
        </ul>

        <div class="btn-wrapper"><a class="btn btn-primary" href="{$link->getModuleLink('responsivegallery', 'galleries')}">{l s='see all galleries' mod='responsivegallery'}</a></div>
    </div>
{/if}

{include file='module:responsivegallery/views/templates/front/javascript.tpl'}
