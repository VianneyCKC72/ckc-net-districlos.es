<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->
{if count($gallery_items) > 0}
    <div id="gallery_home">
        <p class="page-heading">{$RG_TITLE|escape:'html':'UTF-8'}</p>

        <div class="rte">
            {$RG_DESCRIPTION} {* cannot be escape *}
        </div>

        <ul id="gallery">
            {foreach from=$gallery_items item='item'}
                {include file='../front/galleryoneitem.tpl' item=$item page=1}
            {/foreach}
        </ul>

        <div class="btn-wrapper">
            <a class="btn btn-default button button-small" href="{$link->getModuleLink('responsivegallery', 'galleries')|escape:'htmlall':'UTF-8'}">
                <span>{l s='see all galleries' mod='responsivegallery'}</span>
            </a>
        </div>
    </div>
{/if}

{include file='../front/javascript.tpl'}
