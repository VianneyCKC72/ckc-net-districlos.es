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
            <div class="row">
                <div class="col-lg-7">
                    {block name='page_header_container'}
                        {block name='page_header'}
                            <h1 class="h2 mb-5">{block name='page_title'}{$product.name}{/block}</h1>
                        {/block}
                    {/block}
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
                                            class="font-weight-bold" id="product_reference">{$product.reference_to_display}</span></div>
                            {/if}
                            <a href="javascript:av_widget_click()" id="AV_button">
                                <div class="skeepers_product__stars" data-product-id="{$product.id}"></div>
                            </a>
                        </div>

                        {block name='product_description_short'}
                            <div id="product-description-short-{$product.id}" class="product-description mb-4">
                                {$product.description_short nofilter}
{*                                <a href="#block-description-long"*}
{*                                   class="lire-plus small font-weight-bold text-black d-flex align-items-center">*}
{*                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"*}
{*                                         fill="none"*}
{*                                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round"*}
{*                                         stroke-linejoin="round"*}
{*                                         class="lucide lucide-plus mr-2">*}
{*                                        <path d="M5 12h14"/>*}
{*                                        <path d="M12 5v14"/>*}
{*                                    </svg>*}
{*                                    Lire davantage</a>*}
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

                                    {block name='product_variants'}

                                        {include file='catalog/_partials/product-variants.tpl'}

                                    {/block}

                                    {block name='product_additional_info'}

                                        {include file='catalog/_partials/product-additional-info.tpl'}

                                    {/block}


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

                                   {block name='hook_display_accessories'}
                                       {hook h='displayMultiAccessoriesProduct'}
                                   {/block}

                                    {block name='product_discounts'}

                                        {include file='catalog/_partials/product-discounts.tpl'}

                                    {/block}


                                    {block name='product_prices'}

                                        {include file='catalog/_partials/product-prices.tpl'}

                                    {/block}

                                    <div class="d-flex flex-wrap gap-16 shipping-block-product-info">
                                        {if file_exists($smarty.const._PS_MODULE_DIR_|cat:'ckc_shipping_estimator/views/templates/front/product/shipping_estimator.tpl')}
                                            {include file="module:ckc_shipping_estimator/views/templates/front/product/shipping_estimator.tpl"}
                                        {/if}
                                        {hook h="displayStockButton" product=$product}
                                    </div>
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
                        <div class="tabs product-tabs" id="block-description-long">
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
{*                                        <hr>*}
{*                                        {$product.description}*}
{*                                        <hr>*}
                                        <div class="product-description">
                                            {$product.description nofilter}
                                        </div>
                                    {/block}

                                    {if isset($groups) && $groups|count > 0}

                                        {block name='product_variants'}
                                            <div class="my-5">
                                                {assign var="color_find" value=0}

                                                {foreach from=$groups key=id_attribute_group item=group}

                                                    {if !empty($group.attributes)}

                                                        {if $group.group_type == 'color' && !$color_find}
                                                            {assign var="color_find" value=1}

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

                                    {hook h='displayCkcProductExtraAdvice' product=$product}
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
                    <h2 class="h2">
                        Produits associés
                    </h2>
                    <div class="products row mt-5">
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
