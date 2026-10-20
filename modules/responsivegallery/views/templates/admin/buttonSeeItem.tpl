<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->
<span class="btn-group-action">
	<span class="btn-group">
        <a class="btn btn-default _blank" href="{$link->getAdminLink('AdminResponsiveGalleryItem')|escape:'html':'UTF-8'}&amp;id_responsive_gallery_gallery={$id_gallery|escape:'htmlall':'UTF-8'}">
			<i class="icon-file-text"></i> {l s='see images' mod='responsivegallery'}
		</a>
	</span>
</span>