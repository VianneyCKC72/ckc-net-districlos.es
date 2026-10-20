<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->
{if isset($url_image) && !empty($url_image)}<img src="{$url_image|escape:'htmlall':'UTF-8'}">{else}{l s='No photo' mod='responsivegallery'}{/if}
