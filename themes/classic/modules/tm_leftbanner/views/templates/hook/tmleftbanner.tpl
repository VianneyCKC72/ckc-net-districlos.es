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

{if $tmleftbanner.slides}
	<div id="tmleftbanner" class="block">
		<ul class="hidden-md-down">
			{foreach from=$tmleftbanner.slides item=slide}
				{if isset($slide.mobile_only) && $slide.mobile_only}
					{continue}
				{/if}
				{if $tmleftbanner.hookName == 'displayHeaderCategory' && $slide.header}
					<li class="slide tmleftbanner-container">
						{if $slide.url != '#'}
							<a href="{$slide.url}" title="{$slide.title}">
						{/if}
							<img class="lazyload" data-src="{$slide.image_url}" alt="{$slide.title}" title="{$slide.title}" />
						{if $slide.url != '#'}
							</a>
						{/if}
					</li>
				{elseif $tmleftbanner.hookName == 'displayHeaderProduct' && $slide.header_product}
					<li class="slide tmleftbanner-container">
						{if $slide.url != '#'}
							<a href="{$slide.url}" title="{$slide.title}">
						{/if}
						<img class="lazyload" data-src="{$slide.image_url}" alt="{$slide.title}" title="{$slide.title}" />
						{if $slide.url != '#'}
							</a>
						{/if}
					</li>
				{elseif $tmleftbanner.hookName != 'displayHeaderCategory' && !$slide.header && !$slide.header_product && $tmleftbanner.hookName != 'displayHeaderProduct'}
					<li class="slide tmleftbanner-container">
						{if $slide.url != '#'}
						<a href="{$slide.url}" title="{$slide.title}">
							{/if}
							<img class="lazyload" data-src="{$slide.image_url}" alt="{$slide.title}" title="{$slide.title}" />
							{if $slide.url != '#'}
						</a>
						{/if}
					</li>
				{/if}
			{/foreach}
		</ul>
		<div class="block hidden-lg-up">
			<div class="col-md-12 col-xs-12 block_content" style="padding: 0;">
				<ul>
					{foreach from=$tmleftbanner.slides item=slide}
						{if $tmleftbanner.hookName == 'displayHeaderCategory' && $slide.header}
							<li class="slide tmleftbanner-container">
								{if $slide.url != '#'}
								<a href="{$slide.url}" title="{$slide.title}">
									{/if}
									<img class="lazyload" data-src="{$slide.image_mobile_url}" alt="{$slide.title}" title="{$slide.title}" />
									{if $slide.url != '#'}
								</a>
								{/if}
							</li>
						{elseif $tmleftbanner.hookName == 'displayHeaderProduct' && $slide.header_product}
							<li class="slide tmleftbanner-container">
								{if $slide.url != '#'}
								<a href="{$slide.url}" title="{$slide.title}">
									{/if}
									<img class="lazyload" data-src="{$slide.image_mobile_url}" alt="{$slide.title}" title="{$slide.title}" />
									{if $slide.url != '#'}
								</a>
								{/if}
							</li>
						{elseif $tmleftbanner.hookName != 'displayHeaderCategory' && !$slide.header && !$slide.header_product && $tmleftbanner.hookName != 'displayHeaderProduct'}
							<li class="slide tmleftbanner-container">
								{if $slide.url != '#'}
								<a href="{$slide.url}" title="{$slide.title}">
									{/if}
									<img class="lazyload" data-src="{$slide.image_mobile_url}" alt="{$slide.title}" title="{$slide.title}" />
									{if $slide.url != '#'}
								</a>
								{/if}
							</li>
						{/if}
					{/foreach}
				</ul>
			</div>
		</div>
	</div>
{/if}
