<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->
{block name='head_seo_title'}{$RG_TITLE|escape:'html':'UTF-8'}{/block}

{extends file='page.tpl'}
{block name="page_content"}
    {if ResponsiveGalleryGallery::getGalleries()|count>1}
        <a class="back-to-gallery" href="{$link->getModuleLink('responsivegallery','galleries')|escape:'html':'UTF-8'}">{l s='Back to our galleries' mod='responsivegallery'}</a>
    {/if}

    <h1 id="gallery_title">{$RG_TITLE|escape:'html':'UTF-8'}</h1>

    <div id="gallery_desc">{$RG_DESCRIPTION nofilter}</div>{* cannot be escape *}

    <ul id="gallery">
        {foreach from=$gallery_items item='item'}
            {include file='module:responsivegallery/views/templates/front/galleryoneitem.tpl' item=$item page=1}
        {/foreach}
    </ul>

    {include file='module:responsivegallery/views/templates/front/javascript.tpl'}

{/block}