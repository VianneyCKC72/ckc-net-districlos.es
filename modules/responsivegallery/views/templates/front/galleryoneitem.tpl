<!--{*
 * Responsive gallery
 *
 * @author    Studio Kiwik
 * @copyright Studio Kiwik 2013-2021
 * @license   http://licences.studio-kiwik.fr/responsivegallery
 *}-->
{if isset($item.image) && !empty($item.image)}
    <li data-title="{$item.legend|strip_tags|escape:'html':'UTF-8'}" class="page-{$page|intval}">
        <div class="img-wrapper">
            {if isset($item.link) && !empty($item.link)}
                <a href="{$item.link|escape:'html':'UTF-8'}" class="zoom-link"></a>
            {/if}
            <a href="{$item.path_image|escape:'html':'UTF-8'}" 
                class="zoom-img fancy-group" 
                rel="group"
                data-fancybox-title="{if isset($item.link) && !empty($item.link)}
                            <a class='img-link' href='{$item.link|escape:'htmlall':'UTF-8'}'>
                        {/if} 
                        {if isset($item.legend) && !empty($item.legend)}
                            {$item.legend|escape:'html':'UTF-8'}
                        {/if}
                        {if isset($item.link) && !empty($item.link)}
                            </a>
                        {/if}"
            >
                <img src="{$item.path_image|escape:'html':'UTF-8'}" 
                    {if isset($item.image) && !empty($item.image)} 
                        alt="{$item.alt|strip_tags|escape:'html':'UTF-8'}" 
                        title="{$item.image|strip_tags|escape:'html':'UTF-8'}"
                    {/if}/>
                {if isset($item.legend) && !empty($item.legend) && isset($item.legend_on_hover) && $item.legend_on_hover}
                    <span class="legend">
                        <span class="legend-inner">{$item.legend|strip_tags|escape:'html':'UTF-8'|truncate:180:'...'}</span>
                    </span>
                {/if}
            </a>
        </div>
    </li>
{/if}