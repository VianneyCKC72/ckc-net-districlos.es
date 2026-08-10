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

<div class="images-container product-images-grid mb-5 mb-lg-0">
  {block name='product_cover'}
    <div class="product-images-grid__track">
      {foreach from=$product.images key=key item=image}
        <div class="product-images-grid__item{if $key == 0} product-images-grid__item--first{/if}">
          {if $key == 0}
            {if !$product.from_europe}
              <div class="pastille_fr">
                <img src="/img/pastille_produit_france.svg" alt="Fabriqué en France" class="pastille_fr_icon pastille-fr-icon-page-produit">
              </div>
            {/if}
            <img class="tag-web-price" src="/themes/classic/assets/img/tag-price.svg" loading="lazy" alt="">
          {/if}
          <a href="{$image.bySize.large_default.url}" class="js-product-image-zoom" rel="nofollow">
            <picture>
              {if !empty($image.bySize.large_default.sources.avif)}
                <source srcset="{$image.bySize.large_default.sources.avif}" type="image/avif">{/if}
              {if !empty($image.bySize.large_default.sources.webp)}
                <source srcset="{$image.bySize.large_default.sources.webp}" type="image/webp">{/if}
              <img
                class="img-cover rounded-4"
                src="{$image.bySize.large_default.url}"
                alt="{if !empty($image.legend)}{$image.legend}{else}{$product.name}{/if}"
                {if $key != 0}loading="lazy"{/if}
              />
            </picture>
          </a>
        </div>
      {/foreach}
    </div>
    {hook h='displayAfterProductThumbs' product=$product}
  {/block}
</div>
