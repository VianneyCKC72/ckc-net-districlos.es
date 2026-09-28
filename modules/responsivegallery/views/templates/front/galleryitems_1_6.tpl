<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->
{if ResponsiveGalleryGallery::getGalleries(true)|count>1}
    {capture name=path}<a href="{$link->getModuleLink('responsivegallery', 'galleries')|escape:'htmlall':'UTF-8'}" title="{l s='Galleries' mod='responsivegallery'}" rel="nofollow">{l s='Galleries' mod='responsivegallery'}</a><span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$RG_TITLE|escape:'html':'UTF-8'}{/capture}
    <div class="clearfix">
        <a class="back-to-gallery" href="{$link->getModuleLink('responsivegallery','galleries')|escape:'html':'UTF-8'}">{l s='Back to our galleries' mod='responsivegallery'}</a>
    </div>
{else}
    {capture name=path}{$RG_TITLE|escape:'html':'UTF-8'}{/capture}
{/if}

<h1 class="page-heading">{$RG_TITLE|escape:'html':'UTF-8'}</h1>

<div id="gallery_desc">{$RG_DESCRIPTION}</div> {* cannot be escape *}

<ul id="gallery">
    {foreach from=$gallery_items item='item'}
        {include file='./galleryoneitem.tpl' item=$item page=1}
    {/foreach}
</ul>

{include file='./javascript.tpl'}

