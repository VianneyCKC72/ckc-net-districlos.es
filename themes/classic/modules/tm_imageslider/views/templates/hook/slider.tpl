{**
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if !$env_pro}
    {if $tmhomeslider.slides}
        <div class="flexslider flexslider-desktop" data-interval="{$tmhomeslider.speed}" data-pause="{$tmhomeslider.pause}" style="min-height: 291px">
            <div class="loadingdiv"></div>
            {if $tmhomeslider.slides[0]}
                <div class="loadingdiv">
                    <img src="{$tmhomeslider.slides[0].image_url}" alt="{$tmhomeslider.slides[0].legend}" />
                </div>
            {/if}
            <ul class="slides">
                {foreach from=$tmhomeslider.slides item=slide}
                    <li class="slide">
                        <a href="{$slide.url}" title="{$slide.title}" aria-label="{$slide.title}">
                            <img src="{$slide.image_url}" alt="{$slide.title}" title="{$slide.title}" />
                        </a>
                        {if $slide.description }
                            <div class="caption-description">
                                {$slide.description nofilter}
                            </div>
                        {/if}
                    </li>
                {/foreach}
            </ul>
        </div>
        <div class="flexslider flexslider-mobile" data-interval="{$tmhomeslider.speed}" data-pause="{$tmhomeslider.pause}" style="min-height: 500px">
            <div class="loadingdiv"></div>
            {if $tmhomeslider.slides[0]}
                <div class="loadingdiv">
                    <img src="{$tmhomeslider.slides[0].image_mobile_url}" alt="{$tmhomeslider.slides[0].legend}" title="{$tmhomeslider.slides[0].title}" />
                </div>
            {/if}
            <ul class="slides">
                {foreach from=$tmhomeslider.slides item=slide}
                    <li class="slide">
                        <a href="{$slide.url}" title="{$slide.title}" aria-label="{$slide.title}">
                            {*<img class="lazyload" data-src="{$slide.image_mobile_url}" alt="{$slide.title}" title="{$slide.title}" />*}
                            <img src="{$slide.image_mobile_url}" alt="{$slide.title}" title="{$slide.title}" />
                        </a>
                        {if $slide.description }
                            <div class="caption-description">
                                {$slide.description nofilter}
                            </div>
                        {/if}
                    </li>
                {/foreach}
            </ul>
        </div>
    {/if}
{else}
    {if $tmhomeslider.slidesClientPro}
        <div class="flexslider flexslider-desktop" data-interval="{$tmhomeslider.speed}" data-pause="{$tmhomeslider.pause}" style="min-height: 291px">
            <div class="loadingdiv"></div>
            {if $tmhomeslider.slidesClientPro[0]}
                <div class="loadingdiv">
                    <img src="{$tmhomeslider.slidesClientPro[0].image_url}" alt="{$tmhomeslider.slidesClientPro[0].legend}" />
                </div>
            {/if}
            <ul class="slides">
                {foreach from=$tmhomeslider.slidesClientPro item=slide}
                    <li class="slide">
                        <a href="{$slide.url}" title="{$slide.title}" aria-label="{$slide.title}">
                            <img src="{$slide.image_url}" alt="{$slide.title}" title="{$slide.title}" />
                        </a>
                        {if $slide.description }
                            <div class="caption-description">
                                {$slide.description nofilter}
                            </div>
                        {/if}
                    </li>
                {/foreach}
            </ul>
        </div>
        <div class="flexslider flexslider-mobile" data-interval="{$tmhomeslider.speed}" data-pause="{$tmhomeslider.pause}" style="min-height: 500px">
            <div class="loadingdiv"></div>
            {if $tmhomeslider.slidesClientPro[0]}
                <div class="loadingdiv">
                    <img src="{$tmhomeslider.slidesClientPro[0].image_mobile_url}" alt="{$tmhomeslider.slidesClientPro[0].legend}" title="{$tmhomeslider.slidesClientPro[0].title}" />
                </div>
            {/if}
            <ul class="slides">
                {foreach from=$tmhomeslider.slidesClientPro item=slide}
                    <li class="slide">
                        <a href="{$slide.url}" title="{$slide.title}" aria-label="{$slide.title}">
                            <img class="lazyload" data-src="{$slide.image_mobile_url}" alt="{$slide.title}" title="{$slide.title}" />
                        </a>
                        {if $slide.description }
                            <div class="caption-description">
                                {$slide.description nofilter}
                            </div>
                        {/if}
                    </li>
                {/foreach}
            </ul>
        </div>
    {/if}
{/if}
