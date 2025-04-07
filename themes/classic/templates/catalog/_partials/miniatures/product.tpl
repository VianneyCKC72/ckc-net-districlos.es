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
{block name='product_miniature_item'}

    <div
            class="js-product product {if (isset($smarty.cookies.show_list)) && ($page.page_name == 'category' ||  $page.page_name == 'search')} list_view col-12 {else}{if !empty($productClasses)} {$productClasses} {/if} {/if}">
        <article class="product-miniature js-product-miniature" data-id-product="{$product.id_product}"
                 data-id-product-attribute="{$product.id_product_attribute}" data-product-name="{$product.name}"
                 data-product-price="{$product.price}" data-product-reference="{$product.reference_to_display}"
                 data-product-category="{$product.category_name}">
            <div class="thumbnail-container">
                <div class="thumbnail-top">

                    {if !$product.from_europe}
                        <div class="pastille_fr">
                            <img src="/img/pastille_produit_france.svg" alt="Fabriqué en France" class="pastille_fr_icon">
                        </div>
                    {/if}

                    {block name='product_thumbnail'}
                        <a href="{$product.url}" class="thumbnail product-thumbnail hover-images">
                            <img class="tag-web-price tag-web-price-miniature"
                                 src="/themes/classic/assets/img/tag-price.svg" loading="lazy" alt="">

                            {* Première image (cover ou fallback) *}
                            <picture class="product-image first-image">
                                {if $product.cover}
                                    {if !empty($product.cover.bySize.home_default.sources.avif)}
                                        <source srcset="{$product.cover.bySize.home_default.sources.avif}" type="image/avif">
                                    {/if}
                                    {if !empty($product.cover.bySize.home_default.sources.webp)}
                                        <source srcset="{$product.cover.bySize.home_default.sources.webp}" type="image/webp">
                                    {/if}
                                    <img src="{$product.cover.bySize.home_default.url}"
                                         alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name|truncate:30:'...'}{/if}"
                                         loading="lazy" data-full-size-image-url="{$product.cover.large.url}"
                                         width="{$product.cover.bySize.home_default.width}"
                                         height="{$product.cover.bySize.home_default.height}"/>
                                {else}
                                    {if !empty($urls.no_picture_image.bySize.home_default.sources.avif)}
                                        <source srcset="{$urls.no_picture_image.bySize.home_default.sources.avif}" type="image/avif">
                                    {/if}
                                    {if !empty($urls.no_picture_image.bySize.home_default.sources.webp)}
                                        <source srcset="{$urls.no_picture_image.bySize.home_default.sources.webp}" type="image/webp">
                                    {/if}
                                    <img src="{$urls.no_picture_image.bySize.home_default.url}" loading="lazy"
                                         width="{$urls.no_picture_image.bySize.home_default.width}"
                                         height="{$urls.no_picture_image.bySize.home_default.height}"/>
                                {/if}
                            </picture>

                            {* Deuxième image (hover) si disponible *}
                            {if !empty($product.images[2])}
                                <picture class="product-image second-image">
                                    {if !empty($product.images[2].bySize.medium_default.sources.avif)}
                                        <source srcset="{$product.images[2].bySize.medium_default.sources.avif}" type="image/avif">
                                    {/if}
                                    {if !empty($product.images[2].bySize.medium_default.sources.webp)}
                                        <source srcset="{$product.images[2].bySize.medium_default.sources.webp}" type="image/webp">
                                    {/if}
                                    <img src="{$product.images[2].bySize.medium_default.url}"
                                         alt="{if !empty($product.images[2].legend)}{$product.images[2].legend}{else}{$product.name|truncate:30:'...'}{/if}"
                                         loading="lazy" data-full-size-image-url="{$product.images[2].large.url}"
                                         width="{$product.images[2].bySize.home_default.width}"
                                         height="{$product.images[2].bySize.home_default.height}"/>
                                </picture>
                            {else}
                                <picture class="product-image second-image">
                                    {if $product.cover}
                                        {if !empty($product.cover.bySize.home_default.sources.avif)}
                                            <source srcset="{$product.cover.bySize.home_default.sources.avif}" type="image/avif">
                                        {/if}
                                        {if !empty($product.cover.bySize.home_default.sources.webp)}
                                            <source srcset="{$product.cover.bySize.home_default.sources.webp}" type="image/webp">
                                        {/if}
                                        <img src="{$product.cover.bySize.home_default.url}"
                                             alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name|truncate:30:'...'}{/if}"
                                             loading="lazy" data-full-size-image-url="{$product.cover.large.url}"
                                             width="{$product.cover.bySize.home_default.width}"
                                             height="{$product.cover.bySize.home_default.height}"/>
                                    {else}
                                        {if !empty($urls.no_picture_image.bySize.home_default.sources.avif)}
                                            <source srcset="{$urls.no_picture_image.bySize.home_default.sources.avif}" type="image/avif">
                                        {/if}
                                        {if !empty($urls.no_picture_image.bySize.home_default.sources.webp)}
                                            <source srcset="{$urls.no_picture_image.bySize.home_default.sources.webp}" type="image/webp">
                                        {/if}
                                        <img src="{$urls.no_picture_image.bySize.home_default.url}" loading="lazy"
                                             width="{$urls.no_picture_image.bySize.home_default.width}"
                                             height="{$urls.no_picture_image.bySize.home_default.height}"/>
                                    {/if}
                                </picture>
                            {/if}
                        </a>
                    {/block}

                    {hook h='displayCustomProductLabel' product=$product}

                    {if $product.main_variants}
                        <div class="highlighted-informations">
                            {block name='product_variants'}
                                {if $product.main_variants}
                                    {include file='catalog/_partials/variant-links.tpl' variants=$product.main_variants}
                                {/if}
                            {/block}
                        </div>
                    {/if}
                </div>

                <div class="product-description">
                    <div class="product-info-custom-block-miniature">
                        {block name='product_name'}
                            {if $page.page_name == 'category'}
                                <h2 class="h3 product-title"><a href="{$product.url}"
                                                                content="{$product.url}">{$product.name}</a></h2>
                            {else}
                                <div class="h3 product-title"><a href="{$product.url}"
                                                                 content="{$product.url}">{$product.name}</a></div>
                            {/if}
                        {/block}

                        <div class="product-info-custom-block-miniature-price-stars">
                            {block name='product_price_and_shipping'}
                                {if $product.show_price}
                                    <div class="product-price-and-shipping flex_cln">
                                        {*                                    {hook h='displayProductPriceBlock' product=$product type="before_price"}*}
                                        <span class="price">
                                        {$product.price} {if $env_pro == 1}HT{else}TTC{/if}
                                    </span>

                                        {if $product.has_discount}
                                            {hook h='displayProductPriceBlock' product=$product type="old_price"}
                                            <span class="regular-price">{$product.regular_price}{if $env_pro == 1}HT{else}TTC{/if}</span>
                                        {/if}

                                        {*                                    {hook h='displayProductPriceBlock' product=$product type='unit_price'}*}

                                        {*                                    {hook h='displayProductPriceBlock' product=$product type='weight'}*}
                                    </div>
                                {/if}
                            {/block}

                            <div class="skeepers_product__stars" data-product-id="{$product.id}"></div>
                        </div>

                        {if (isset($smarty.cookies.show_list)) && ($page.page_name == 'category' ||  $page.page_name == 'search')}
                            <p class="product_descr">
                                {$product.description|truncate:200|strip_tags:0}
                            </p>
                        {/if}

                        {block name='product_reviews'}
                            {hook h='displayProductListReviews' product=$product}
                        {/block}
                    </div>
                    <a href="{$product.url}" class="btn btn-primary">Voir le produit</a>
                </div>

                {*                {include file='catalog/_partials/product-flags.tpl'}*}
            </div>
        </article>
    </div>
{/block}
