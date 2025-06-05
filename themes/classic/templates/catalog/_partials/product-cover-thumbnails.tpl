{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}

<div class="images-container wrapper__swiper__product-img mb-5 mb-lg-0" style="--product-img-width: auto">
  {block name='product_images'}
    <div class="wrapper__swiper__product-img__thumbnails">
      <div class="swiper swiper__product-img__thumbnails">
        <div class="swiper-wrapper">
          {foreach from=$product.images item=image}
            <div class="swiper-slide">
              <div class="thumbnail">
                <picture>
                  {if !empty($image.bySize.small_default.sources.avif)}
                    <source srcset="{$image.bySize.small_default.sources.avif}" type="image/avif">{/if}
                  {if !empty($image.bySize.small_default.sources.webp)}
                    <source srcset="{$image.bySize.small_default.sources.webp}" type="image/webp">{/if}
                  <img class="img-cover" src="{$image.bySize.small_default.url}"
                       alt="{if !empty($image.legend)}{$image.legend}{else}{$product.name}{/if}" />
                </picture>
              </div>
            </div>
          {/foreach}
        </div>
      </div>
      <div class="swiper-button-prev">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-move-left">
          <path d="M6 8L2 12L6 16" />
          <path d="M2 12H22" />
        </svg>
      </div>
      <div class="swiper-button-next">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-move-right">
          <path d="M18 8L22 12L18 16" />
          <path d="M2 12H22" />
        </svg>
      </div>
    </div>
  {/block}

  {block name='product_cover'}
    <div class="swiper swiper__product-img__cover">

      <img class="tag-web-price" src="/themes/classic/assets/img/tag-price.svg" loading="lazy" alt="">

      <div class="swiper-wrapper">
        {foreach from=$product.images item=image}
          <div class="swiper-slide">
            {*            <a href="{$image.bySize.large_default.url}" data-fancybox="product-gallery" rel="nofollow">*}
            <a href="{$image.bySize.large_default.url}" rel="nofollow">
              <picture>
                {if !empty($image.bySize.large_default.sources.avif)}
                  <source srcset=" {$image.bySize.large_default.sources.avif}" type="image/avif">{/if}
                {if !empty($image.bySize.large_default.sources.webp)}
                  <source srcset="{$image.bySize.large_default.sources.webp}" type="image/webp">{/if}
                <img class="img-cover rounded-4" src="{$image.bySize.large_default.url}"
                     alt="{if !empty($image.legend)}{$image.legend}{else}{$product.name}{/if}" />
              </picture>
            </a>
          </div>
        {/foreach}
      </div>
    </div>
  {/block}

  {hook h='displayAfterProductThumbs' product=$product}
</div>
