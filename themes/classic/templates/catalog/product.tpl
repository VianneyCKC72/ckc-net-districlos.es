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
{extends file=$layout}

{block name='head' append}
    <meta property="og:type" content="product">
    {if $product.cover}
        <meta property="og:image" content="{$product.cover.large.url}">
    {/if}

    {if $product.show_price}
        <meta property="product:pretax_price:amount" content="{$product.price_tax_exc}">
        <meta property="product:pretax_price:currency" content="{$currency.iso_code}">
        <meta property="product:price:amount" content="{$product.price_amount}">
        <meta property="product:price:currency" content="{$currency.iso_code}">
    {/if}
    {if isset($product.weight) && ($product.weight != 0)}
        <meta property="product:weight:value" content="{$product.weight}">
        <meta property="product:weight:units" content="{$product.weight_unit}">
    {/if}
{/block}

{block name='head_microdata_special'}
    {include file='_partials/microdata/product-jsonld.tpl'}
{/block}

{block name='content'}
    <section id="main">
        <meta content="{$product.url}">

        <div class="product-container js-product-container">
            <div class="row mb-5">
                <div class="col-12">
                    {block name='page_header_container'}
                        {block name='page_header'}
                            <h1 class="h2">{block name='page_title'}{$product.name}{/block}</h1>
                        {/block}
                    {/block}

                    {* Start - Inté static *}
                    <div class="has-discount d-flex align-items-center">
                        <div class="tag-discount mr-2">% SOLDES</div>
                        <p class="small mb-0">100 € offerts par tranche de 1 000 € d’achat & 150 € offerts par tranche
                            de 3 000 €
                            d’achat
                        </p>
                    </div>
                    {* End - Inté static *}
                </div>
            </div>
            <div class="row">
                <div class="col-lg-7">
                    {block name='page_content_container'}
                        {block name='page_content'}
                            {* {include file='catalog/_partials/product-flags.tpl'} *}
                            {hook h='displayCustomProductLabel' product=$product}
                            {block name='product_cover_thumbnails'}
                                {include file='catalog/_partials/product-cover-thumbnails.tpl'}
                            {/block}
                        {/block}
                    {/block}
                </div>
                <div class="col-lg-5">

                    <div class="product-information">

                        <div class="rounded-4 bg-primary text-white px-4 py-2 d-flex align-items-center justify-content-start mb-3">
                            <div>
                                <p class="small mb-0 font-weight-bold mb-1">
                                    Produit garanti moins cher du web
                                </p>
                                <p class="thin mb-0">
                                    Vous trouvez moins cher ailleurs ? Nous vous remboursons la différence.
                                </p>
                            </div>
                            <img class="ml-2" src="/themes/classic/assets/img/tag-price.svg" loading="lazy" alt="">
                        </div>
                        <div class="d-flex align-items-center mb-3 gap-16 flex-wrap">
                            {if $product.quantity > 0}
                                <div class="small text-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                         fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round"
                                         class="lucide lucide-check mr-2">
                                        <path d="M20 6 9 17l-5-5"/>
                                    </svg>
                                    En stock
                                </div>
                            {/if}

                            {if $product.reference_to_display}
                                <div class="small">REF <span
                                            class="font-weight-bold">{$product.reference_to_display}</span></div>
                            {/if}
                            <a href="javascript:av_widget_click()" id="AV_button">
                            <div class="skeepers_product__stars" data-product-id="{$product.id}"></div>
                            </a>
                        </div>

                        {block name='product_description_short'}
                            <div id="product-description-short-{$product.id}" class="product-description mb-4">
                                {$product.description_short nofilter}
                                <a href="#description"
                                   class="small font-weight-bold text-black d-flex align-items-center mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                         fill="none"
                                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                         stroke-linejoin="round"
                                         class="lucide lucide-plus mr-2">
                                        <path d="M5 12h14"/>
                                        <path d="M12 5v14"/>
                                    </svg>
                                    Lire d'avantage</a>
                            </div>
                        {/block}


                        {if $product.is_customizable && count($product.customizations.fields)}

                            {block name='product_customization'}

                                {include file="catalog/_partials/product-customization.tpl" customizations=$product.customizations}

                            {/block}

                        {/if}

                        <div class="product-actions js-product-actions">

                            {block name='product_buy'}
                                <form action="{$urls.pages.cart}" method="post" id="add-to-cart-or-refresh">
                                    <input type="hidden" name="token" value="{$static_token}">
                                    <input type="hidden" name="id_product" value="{$product.id}"
                                           id="product_page_product_id">
                                    <input type="hidden" name="id_customization" value="{$product.id_customization}"
                                           id="product_customization_id" class="js-product-customization-id">

                                    {if isset($groups) && $groups|count > 0}

                                        {block name='product_variants'}

                                            {include file='catalog/_partials/product-variants.tpl'}

                                        {/block}

                                    {/if}


                                    {block name='product_pack'}

                                        {if $packItems}
                                            <section class="product-pack">
                                                <p class="h4">

                                                    {l s='This pack contains' d='Shop.Theme.Catalog'}</p>

                                                {foreach from=$packItems item="product_pack"}

                                                    {block name='product_miniature'}

                                                        {include file='catalog/_partials/miniatures/pack-product.tpl' product=$product_pack showPackProductsPrice=$product.show_price}

                                                    {/block}

                                                {/foreach}
                                            </section>
                                        {/if}

                                    {/block}


                                    {block name='product_discounts'}

                                        {include file='catalog/_partials/product-discounts.tpl'}

                                    {/block}


                                    {block name='product_prices'}

                                        {include file='catalog/_partials/product-prices.tpl'}

                                    {/block}


                                    {block name='product_add_to_cart'}

                                        {include file='catalog/_partials/product-add-to-cart.tpl'}

                                    {/block}

                                    {* Start - Inté static *}
                                    <div class="d-flex justify-content-between flex-wrap gap-16 mt-4">
                                        {if file_exists($smarty.const._PS_MODULE_DIR_|cat:'ckc_shipping_estimator/views/templates/front/product/shipping_estimator.tpl')}
                                            {include file="module:ckc_shipping_estimator/views/templates/front/product/shipping_estimator.tpl"}
                                        {/if}
                                        {hook h="displayStockButton" product=$product}
                                    </div>
                                    {* End - Inté static *}

                                    {*

                                    {block name='product_additional_info'}

                                      {include file='catalog/_partials/product-additional-info.tpl'}

                                    {/block} *}
                                    {* Input to refresh product HTML removed, block kept for compatibility with themes *}



                                    {block name='product_refresh'}
                                    {/block}
                                </form>
                            {/block}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    {block name='hook_display_reassurance'}
                        {hook h='displayReassurance'}
                    {/block}

                    {block name='product_tabs'}
                    <div class="tabs product-tabs">
                        <ul class="nav nav-tabs" role="tablist">

                            {if $product.description}
                                <li class="nav-item">
                                    <a class="nav-link

                      {if $product.description} active js-product-nav-active

                      {/if}" data-toggle="tab" href="#description" role="tab" aria-controls="description"

                                            {if $product.description} aria-selected="true"
                                            {/if}>

                                        {l s='Description' d='Shop.Theme.Catalog'}</a>
                                </li>
                            {/if}
                            <li class="nav-item">
                                <a class="nav-link

                    {if !$product.description} active js-product-nav-active

                    {/if}" data-toggle="tab" href="#product-details" role="tab" aria-controls="product-details"

                                        {if !$product.description} aria-selected="true"
                                        {/if}>

                                    {l s='Product Details' d='Shop.Theme.Catalog'}</a>
                            </li>

                            {if $product.attachments}
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#attachments" role="tab"
                                       aria-controls="attachments">

                                        {l s='Attachments' d='Shop.Theme.Catalog'}</a>
                                </li>
                            {/if}

                            {foreach from=$product.extraContent item=extra key=extraKey}
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#extra-{$extraKey}" role="tab"
                                       aria-controls="extra-{$extraKey}">{$extra.title}</a>
                                </li>
                            {/foreach}
                        </ul>

                        <div class="tab-content" id="tab-content">
                            <div class="tab-pane fade in  {if $product.description} active js-product-tab-active

                    {/if}" id="description" role="tabpanel">

                                {block name='product_description'}
                                    <div class="product-description">{$product.description nofilter}</div>
                                {/block}

                                {if isset($groups) && $groups|count > 0}

                                    {block name='product_variants'}
                                        <div class="my-5">

                                            {foreach from=$groups key=id_attribute_group item=group}

                                                {if !empty($group.attributes)}

                                                    {if $group.group_type == 'color'}
                                                        <h3>Couleurs disponibles</h3>
                                                        <div class="color-variants">

                                                            {foreach from=$group.attributes key=id_attribute item=group_attribute}
                                                                <div class="color-variant">
                                                                    <div class="color"
                                                                         style="background-color: {$group_attribute.html_color_code}"></div>
                                                                    <p>{$group_attribute.name}</p>
                                                                </div>
                                                            {/foreach}
                                                        </div>
                                                    {/if}

                                                {/if}

                                            {/foreach}
                                        </div>
                                    {/block}

                                {/if}

                                <div>
                                    <h3>Pour aller plus loin ...</h3>
                                    <p class="text-secondary font-weight-bold d-flex align-items-center mb-0">
                                        <svg class="mr-2" width="14" height="13" viewBox="0 0 14 13" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                    d="M2.40625 0L1.70312 0.687427L2.75 1.71093L3.45312 1.0235L2.40625 0ZM11.5938 0L10.5469 1.0235L11.25 1.71093L12.2969 0.687427L11.5938 0ZM7 0.305523C6.83594 0.307432 6.66797 0.31698 6.5 0.336075C6.49414 0.336075 6.49023 0.336075 6.48438 0.336075C4.45703 0.563308 2.84375 2.16539 2.5625 4.13984C2.33789 5.72855 3.00781 7.16833 4.125 8.09636C4.60352 8.49736 4.89844 9.03202 5 9.57814V12.5112H6.14062C6.31445 12.8033 6.63086 13 7 13C7.36914 13 7.68555 12.8033 7.85938 12.5112H9V10.5558H9.04688V9.97532C9.04688 9.25925 9.42773 8.53555 10.0469 7.97415C10.875 7.16451 11.5 6.01307 11.5 4.68978C11.5 2.27424 9.4668 0.282609 7 0.305523ZM7 1.2832C8.93164 1.25646 10.5 2.80317 10.5 4.68978C10.5 5.71328 10.0156 6.61457 9.34375 7.27145L9.35938 7.28672C8.6875 7.89968 8.25781 8.70167 8.14062 9.57814H5.95312C5.84375 8.73987 5.47461 7.93023 4.78125 7.34783C3.89844 6.61266 3.37109 5.50896 3.54688 4.26204C3.76562 2.72106 5.04297 1.4837 6.60938 1.31375C6.74023 1.29847 6.87109 1.28511 7 1.2832ZM0 4.68978V5.66745H1.5V4.68978H0ZM12.5 4.68978V5.66745H14V4.68978H12.5ZM2.75 8.6463L1.70312 9.6698L2.40625 10.3572L3.45312 9.33372L2.75 8.6463ZM11.25 8.6463L10.5469 9.33372L11.5938 10.3572L12.2969 9.6698L11.25 8.6463ZM6 10.5558H8V11.5335H6V10.5558Z"
                                                    fill="#7CBA49"/>
                                        </svg>
                                        Conseils d'experts
                                    </p>
                                    <p class="mb-0">L'installation d'une occultation sur une clôture rigide sur platines
                                        est déconseillé.
                                    </p>
                                    <p class="text-secondary font-weight-bold d-flex align-items-center mb-0">
                                        <svg class="mr-2" width="10"
                                             height="13" viewBox="0 0 10 13" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                    d="M5 0L4.56597 0.496183C4.56597 0.496183 3.45486 1.77179 2.32639 3.35751C1.76215 4.1514 1.18056 5.01765 0.746528 5.85496C0.3125 6.69227 0 7.49443 0 8.23664C0 10.854 2.2526 13 5 13C7.7474 13 10 10.854 10 8.23664C10 7.49443 9.6875 6.69227 9.25347 5.85496C8.81944 5.01765 8.23785 4.1514 7.67361 3.35751C6.54514 1.77179 5.43403 0.496183 5.43403 0.496183L5 0ZM5 1.70356C5.31033 2.0695 5.89193 2.71867 6.77083 3.95293C7.31771 4.71994 7.84722 5.56552 8.24653 6.33461C8.64583 7.10369 8.88889 7.81489 8.88889 8.23664C8.88889 10.2772 7.14193 11.9415 5 11.9415C2.85807 11.9415 1.11111 10.2772 1.11111 8.23664C1.11111 7.81489 1.35417 7.10369 1.75347 6.33461C2.15278 5.56552 2.68229 4.71994 3.22917 3.95293C4.10807 2.71867 4.68967 2.0695 5 1.70356ZM2.22222 8.23664C2.22222 9.69004 3.47439 10.883 5 10.883V9.82443C4.07986 9.82443 3.33333 9.11323 3.33333 8.23664H2.22222Z"
                                                    fill="#7CBA49"/>
                                        </svg>
                                        Entretien
                                    </p>
                                    <p>Nécessite peu d'entretien ! Nettoyez votre clôture à l'eau tiède et savonneuse 1
                                        fois par an maximum.
                                    </p>
                                </div>
                            </div>

                            {block name='product_details'}
                                {include file='catalog/_partials/product-details.tpl'}
                            {/block}

                            {block name='product_attachments'}
                                {if $product.attachments}
                                    <div class="tab-pane fade in" id="attachments" role="tabpanel">
                                        <section class="product-attachments">
                                            <p class="h5 text-uppercase">{l s='Download' d='Shop.Theme.Actions'}</p>
                                            <div class="product-attachments-list mt-3">
                                                {foreach from=$product.attachments item=attachment}
                                                    <a class="attachment"
                                                       href="{url entity='attachment' params=['id_attachment' => $attachment.id_attachment]}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                             viewBox="0 0 24 24" fill="none"
                                                             stroke="currentColor" stroke-width="2"
                                                             stroke-linecap="round" stroke-linejoin="round"
                                                             class="lucide lucide-file-down">
                                                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                                            <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                                            <path d="M12 18v-6"/>
                                                            <path d="m9 15 3 3 3-3"/>
                                                        </svg>
                                                        <div class="text-black">
                                                            <p>{$attachment.description}</p>
                                                            <p class="small">{l s='Download' d='Shop.Theme.Actions'}
                                                                ({$attachment.file_size_formatted})</p>
                                                        </div>
                                                    </a>
                                                {/foreach}
                                            </div>
                                        </section>
                                    </div>
                                {/if}
                            {/block}

                            <div class="tab-pane fade in" id="netreviews_reviews_tab" role="tabpanel">
                                <section class="netreviews_reviews_tab d-flex">

                                    <div class="skeepers_product__reviews"
                                         data-product-id="{$product.id}"
                                         data-locale="fr_FR"
                                         data-price="{$product.price}"
                                         data-currency="{$currency.iso_code}"
                                         data-name="{$product.title}"
                                         data-url="{$urls.current_url}"
                                         data-image-url="{$urls.no_picture_image.bySize.large_default.url}"></div>

                                </section>
                            </div>

                            {foreach from=$product.extraContent item=extra key=extraKey}
                            <div class="tab-pane fade in {$extra.attr.class}" id="extra-{$extraKey}" role="tabpanel"
                            {foreach $extra.attr as $key => $val} {$key}="{$val}" {/foreach}>
                            {$extra.content nofilter}
                        </div>
                        {/foreach}
                    </div>
                    {/block}
                </div>
            </div>
        </div>

        {block name='product_accessories'}
            {if $accessories}
                <section class="product-accessories clearfix">
                    <p class="h5 text-uppercase">{l s='You might also like' d='Shop.Theme.Catalog'}</p>
                    <div class="products row">
                        {foreach from=$accessories item="product_accessory" key="position"}
                            {block name='product_miniature'}
                                {include file='catalog/_partials/miniatures/product.tpl' product=$product_accessory position=$position productClasses="col-xs-6 col-sm-4 col-lg-3 col-xl-3"}
                            {/block}
                        {/foreach}
                    </div>
                </section>
            {/if}
        {/block}

        {block name='product_footer'}
            {hook h='displayFooterProduct' product=$product category=$category}
        {/block}

    </section>
{/block}
