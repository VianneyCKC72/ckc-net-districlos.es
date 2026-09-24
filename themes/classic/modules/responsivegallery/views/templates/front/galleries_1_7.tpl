<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->

{extends file='page.tpl'}
{block name="page_content"}
    {if isset($RG_TITLE_PAGE) && !empty($RG_TITLE_PAGE)}
        <h1 class="h1">{l s='Vos réalisations' d='Shop.Theme.Customglobal'}</h1>
    {/if}
    {if isset($RG_DESCRIPTION_PAGE) && !empty($RG_DESCRIPTION_PAGE)}
        <div class="rte">{$RG_DESCRIPTION_PAGE nofilter}</div> {* cannot be escape *}
    {/if}
    <div id="galleries" class="row clearfix">
        {foreach from=$all_galleries item='gallery'}
            <div class="gallery-single col-md-6 col-sm-6 col-xs-6 clearfix">
                <a class="img-link" href="{$gallery.link|escape:'html':'UTF-8'}" title="{$gallery.title|escape:'html':'UTF-8'}">
                    <span class="img-wrapper">
                        <img class="img-fluid"
                             src="{$path_img_galleries|escape:'html':'UTF-8'}{$gallery.id_responsive_gallery_gallery}-{$gallery.image|escape:'html':'UTF-8'}"
                             alt="{$gallery.title|escape:'html':'UTF-8'}"/>
                        {if isset($gallery.description) && $gallery.description && isset($gallery.legend_on_photo) && $gallery.legend_on_photo}
                            <span class="description-wrapper">
                                <span class="description">{$gallery.description nofilter}</span> {* cannot be escape *}
                            </span>
                        {/if}
                    </span>
                    <span class="title">{$gallery.title|escape:'html':'UTF-8'}</span>
                </a>
            </div>
        {/foreach}
    </div>
{/block}