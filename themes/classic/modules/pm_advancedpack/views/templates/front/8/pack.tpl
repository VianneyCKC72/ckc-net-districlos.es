{extends file='catalog/product.tpl'}

{block name='content'}
    {if isset($packMlChoose) && $packMlChoose == 1}
        <style>
            .qty {
                display: none !important;
            }
        </style>
    {/if}
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
                    {hook h='displayPromoProductDescription'}
                    {* Start - Inté static *}
                    {*                    <div class="has-discount d-flex align-items-center">
                                            <div class="tag-discount mr-2">% SOLDES</div>
                                            <p class="small mb-0">100 € offerts par tranche de 1 000 € d’achat & 150 € offerts par tranche
                                                de 3 000 €
                                                d’achat
                                            </p>
                                        </div>*}
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
                                            class="font-weight-bold" id="product_reference">{$product.reference_to_display}</span></div>
                            {/if}
                            <a href="javascript:av_widget_click()" id="AV_button">
                                <div class="skeepers_product__stars" data-product-id="{$product.id}"></div>
                            </a>
                        </div>

                        {block name='product_description_short'}
                            <div id="product-description-short-{$product.id}" class="product-description mb-4">
                                {$product.description_short nofilter}
                                <a href="#description"
                                   class="lire-plus small font-weight-bold text-black d-flex align-items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                         fill="none"
                                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                         stroke-linejoin="round"
                                         class="lucide lucide-plus mr-2">
                                        <path d="M5 12h14"/>
                                        <path d="M12 5v14"/>
                                    </svg>
                                    Lire davantage</a>
                            </div>
                        {/block}


                        {if $product.is_customizable && count($product.customizations.fields)}

                            {block name='product_customization'}

                                {include file="catalog/_partials/product-customization.tpl" customizations=$product.customizations}

                            {/block}

                        {/if}

                        <div class="product-actions js-product-actions">

                            {block name='product_buy'}
                                <form class="ap5-buy-block{if $configuration.is_catalog && $product.quantity > 0} hidden{/if}{if !empty($from_quickview)} ap5-from-modal{/if}" action="{pm_advancedpack::getPackAddCartURL($product.id)}" method="post">
                                    <input type="hidden" name="token" value="{$static_token}">
                                    <input type="hidden" name="id_product" value="{$product.id}" id="product_page_product_id">
                                    {if isset($packAutoChoose)}
                                        <input type="hidden" name="auto_choose" id="auto_choose" value="{$packAutoChoose}">
                                    {/if}
                                    {if isset($packMlChoose)}
                                        <input type="hidden" name="ml_choose" id="ml_choose" value="{$packMlChoose}">
                                    {/if}
                                    <input type="hidden" name="add" value="1" />
                                    <input type="hidden" name="id_product_attribute" id="idCombination" value="{if !empty($packAttributesList)}{$packAttributesList|json_encode}{/if}" />
                                    <input type="hidden" name="is_pack" value="1" id="is_pack">
                                    <input type="hidden" name="id_customization" value="{$product.id_customization}"
                                           id="product_customization_id" class="js-product-customization-id">

                                    {if isset($groups) && $groups|count > 0}

                                        {block name='product_variants'}
                                            {include file='module:pm_advancedpack/views/templates/front/8/pack-product-list.tpl'}
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

                                    {block name='hook_display_accessories'}
                                        {hook h='displayMultiAccessoriesProduct'}
                                    {/block}

                                    {if isset($packMlChoose) && $packMlChoose}
                                        <div class="ap5-attributes">
                                            <div class="clearfix hiddenlabel">
                                                <p class="font-weight-bold group-name">Choisissez le nombre de ML</p>
                                                <div class="select_wrap">
                                                    <div class="clearfix dis_flex">
                                                    <span class="input-group-btn-vertical dis_flex">
                                                        <input type="number" name="quantity_ml" id="quantity_ml" value="1" class="form-control" min="1" step="1" style="display: block;">
                                                    </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    {/if}

                                    {block name='product_discounts'}

                                        {include file='catalog/_partials/product-discounts.tpl'}

                                    {/block}


                                    {block name='product_prices'}
                                        {include file='module:pm_advancedpack/views/templates/front/8/pack-price-container.tpl'}
                                    {/block}

                                    {* Start - Inté static *}
                                    <div class="d-flex flex-wrap gap-16 shipping-block-product-info">
                                        {if file_exists($smarty.const._PS_MODULE_DIR_|cat:'ckc_shipping_estimator/views/templates/front/product/shipping_estimator.tpl')}
                                            {include file="module:ckc_shipping_estimator/views/templates/front/product/shipping_estimator.tpl"}
                                        {/if}
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
                                <li class="nav-item">
                                    <a class="nav-link active js-product-nav-active" href="#kit_content"
                                       data-toggle="tab" role="tab" aria-controls="kit_content" aria-selected="true">
                                        Contenu du kit
                                    </a>
                                </li>
                                {if $product.description}
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#description" role="tab" aria-controls="description">
                                            {l s='Description' d='Shop.Theme.Catalog'}
                                        </a>
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
                                <div class="tab-pane active js-product-tab-active" id="kit_content" role="tabpanel">
                                    {foreach from=$productsPackUnique item=productPack name=productPack_list}
                                        <div class="row mb-5">
                                            <div class="col-md-2 position-relative d-flex">
                                                {assign var=productPackImageIds value="`$productPack.id_product`-`$productPack.image.id_image`"}
                                                <div style="padding: 20px;box-shadow: 0 10px 20px #0000000d;width: 100%;" class="d-flex">
                                                    <img src="{$pmlink->getImageLink($productPack.presentation.link_rewrite, $productPackImageIds, $imageFormatProductSlideshow)}" alt="{$productPack.image.legend}" style="width: auto;max-height: 190px;max-width: 100%;margin: auto;">
                                                </div>
                                                <div class="position-absolute w-100 d-flex" style="bottom: -16px !important;">
                                                    <span class="text-center m-auto" style="padding: 5px 20px;background-color: #79C144;width: 83px;color: #fff;font-weight: 600;font-size: 16px;">x{$productPack.quantity}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-10 content-kit-description">
                                                <h3 class="h5 text-uppercase fs-16">{$productPack.presentation.name}</h3>
                                                <div class="product-description">{$productPack.presentation.description_short nofilter}</div>
                                                <div class="mt-10">
                                                    <a class="btn btn-primary add_to_cart" href="{$productPack.presentation.link}">Voir le produit</a>
                                                </div>
                                                {block name='product_reviews'}
                                                    {hook h='displayProductListReviews' product=$productPack.presentation}
                                                {/block}
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>

                                <div class="tab-pane fade in" id="description" role="tabpanel">

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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var button = document.querySelector('.lire-plus');

            if (!button) return;

            button.addEventListener('click', function (e) {
                e.preventDefault();

                var tabPane = document.querySelector('#description');
                var productTabs = document.querySelector('.product-tabs');

                if (!tabPane || !productTabs) return;

                tabPane.classList.add('active', 'show');

                setTimeout(function () {
                    var position = productTabs.getBoundingClientRect().top + window.scrollY - 100;

                    window.scrollTo({
                        top: position,
                        behavior: 'smooth'
                    });

                }, 300);
            });
        });
    </script>


{/block}
