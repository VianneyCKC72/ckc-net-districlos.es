{extends file='catalog/product.tpl'}

{block name='content'}
    {assign var="initial_pack" value="1"}

    {if (isset($packMlChoose) && $packMlChoose == 1) || (isset($packHeightChoose) && $packHeightChoose == 1 && isset($packHeightValues) && $packHeightValues|@count > 0)}
        <style>
            .qty {
                display: none !important;
            }
        </style>
    {/if}
    <style>
        .price-loader {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid #ccc;
            border-top-color: #333;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-left: 5px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <section id="main">
        <meta content="{$product.url}">
        {hook h='CETemplate' id=43}
        <div class="product-container js-product-container">
            <div class="row">
                <div class="col-lg-7">
                    {hook h='displayPromoProductDescription'}
                    {block name='page_content_container'}
                        {block name='page_content'}
                            {* {include file='catalog/_partials/product-flags.tpl'} *}
                            {hook h='displayCustomProductLabel' product=$product}
                            {block name='product_cover_thumbnails'}
                                {include file='catalog/_partials/product-cover-thumbnails-pack.tpl'}
                            {/block}
                            {hook h='displayProductBelowGallery' product=$product}
                        {/block}
                    {/block}
                </div>
                <div class="col-lg-5">

                    <div class="product-information">
                        {block name='page_header_container'}
                            {block name='page_header'}
                                <h1 class="product-title">{block name='page_title'}{$product.name}{/block}</h1>
                            {/block}
                        {/block}

                        {if !$pro.env}
                        <div class="rounded-4 bg-primary text-white px-4 py-2 d-flex align-items-center justify-content-start mb-3">
                            <div>
                                <p class="small mb-0 font-weight-bold mb-1">
                                    {l s='Produit garanti moins cher du web' d='Shop.Theme.Custommodules'}
                                </p>
                                <p class="thin mb-0">
                                    {l s='Vous trouvez moins cher ailleurs ? Nous vous remboursons la différence.' d='Shop.Theme.Custommodules'}
                                </p>
                            </div>
                            <img class="ml-2" src="/themes/classic/assets/img/tag-price.svg" loading="lazy" alt="">
                        </div>
                        {/if}
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
                                    {l s='En stock' d='Shop.Theme.Custommodules'}
                                </div>
                            {/if}

                            {if $product.reference_to_display}
                                <div class="small">{l s='REF' d='Shop.Theme.Custommodules'} <span
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
                                    {l s='Lire davantage' d='Shop.Theme.Custommodules'}</a>
                            </div>
                        {/block}

                        {if (isset($packMlChoose) && $packMlChoose) || (isset($packHeightChoose) && $packHeightChoose && isset($packHeightValues) && $packHeightValues|@count > 0)}
                            <style>
                                .ap5-ml-field .group-name {
                                    display: flex;
                                    align-items: center;
                                    gap: 8px;
                                    margin: 0 0 10px;
                                }

                                .ap5-ml-field {
                                    background: #E8F4F6;
                                    border: 0;
                                    border-radius: 4px;
                                    padding: 14px 16px;
                                }

                                .ap5-ml-help {
                                    position: relative;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: 20px;
                                    height: 20px;
                                    border-radius: 0;
                                    background: transparent;
                                    color: #026473;
                                    font-size: 0;
                                    font-weight: 400;
                                    line-height: 1;
                                    cursor: help;
                                }

                                .ap5-ml-help svg {
                                    display: block;
                                }

                                .ap5-ml-help-tooltip {
                                    position: absolute;
                                    left: 50%;
                                    top: 28px;
                                    transform: translateX(-50%);
                                    min-width: 190px;
                                    max-width: 260px;
                                    padding: 8px 10px;
                                    background: #FFFFFF;
                                    border: 1px solid #bdbdbd;
                                    color: #6f6f6f;
                                    font-size: 12px;
                                    font-weight: 400;
                                    line-height: 1.3;
                                    text-align: left;
                                    visibility: hidden;
                                    opacity: 0;
                                    z-index: 15;
                                    transition: opacity 0.2s ease;
                                }

                                .ap5-ml-help:hover .ap5-ml-help-tooltip,
                                .ap5-ml-help:focus .ap5-ml-help-tooltip {
                                    visibility: visible;
                                    opacity: 1;
                                }

                                .ap5-ml-field #quantity_ml,
                                .ap5-ml-field #quantity_hauteur,
                                .ap5-ml-field select#quantity_hauteur {
                                    display: block;
                                    width: 100%;
                                    background: transparent !important;
                                    border: 0 !important;
                                    border-bottom: 2px solid #026473 !important;
                                    border-radius: 0 !important;
                                    padding: 0 0 8px !important;
                                    margin: 0;
                                    height: auto;
                                    min-height: 0;
                                    line-height: 1.2;
                                    color: #222;
                                    box-shadow: none !important;
                                    outline: none;
                                }

                                .ap5-ml-field #quantity_ml::placeholder,
                                .ap5-ml-field #quantity_hauteur::placeholder {
                                    color: #8a8a8a !important;
                                    opacity: 1;
                                }

                                .ap5-ml-field #quantity_ml:focus,
                                .ap5-ml-field #quantity_hauteur:focus {
                                    border-bottom: 2px solid #026473 !important;
                                    box-shadow: none !important;
                                }

                                .ap5-ml-field #quantity_ml.ap5-ml-invalid,
                                .ap5-ml-field #quantity_hauteur.ap5-ml-invalid {
                                    color: #f33 !important;
                                    border-bottom: 2px solid #f33 !important;
                                }

                                .ap5-ml-error {
                                    display: none;
                                    margin-top: 6px;
                                    color: #f33;
                                    font-size: 13px;
                                    text-align: right;
                                }

                                .ap5-ml-error.ap5-ml-error-visible {
                                    display: block;
                                }

                                .ap5-dimensions-row {
                                    display: grid !important;
                                    grid-template-columns: 1fr;
                                    gap: 16px;
                                    margin-bottom: 1rem;
                                    align-items: stretch;
                                    min-width: 0;
                                }

                                .ap5-dimensions-row:has(.ap5-ml-field--length):has(.ap5-ml-field--height) {
                                    grid-template-columns: repeat(2, minmax(0, 1fr));
                                }

                                .ap5-dimensions-row .ap5-ml-field {
                                    margin-bottom: 0 !important;
                                    display: flex;
                                    flex-direction: column;
                                    min-width: 0;
                                    align-self: stretch;
                                }

                                .ap5-dimensions-row .ap5-ml-field .hiddenlabel {
                                    display: flex;
                                    flex-direction: column;
                                    flex: 0 0 auto;
                                }

                                .ap5-dimensions-row .ap5-ml-field .group-name {
                                    flex: 0 0 auto;
                                    min-height: 52px;
                                    margin-bottom: 10px;
                                    align-items: flex-start;
                                }

                                .ap5-dimensions-row:not(:has(.ap5-ml-field--height)) .ap5-ml-field--length .group-name,
                                .ap5-dimensions-row:not(:has(.ap5-ml-field--length)) .ap5-ml-field--height .group-name {
                                    min-height: 0;
                                }

                                .ap5-dimensions-row .select_wrap {
                                    flex: 0 0 auto;
                                }

                                .ap5-dimensions-row .clearfix::after {
                                    display: none;
                                }

                                .ap5-dimensions-row .ap5-ml-field #quantity_ml,
                                .ap5-dimensions-row .ap5-ml-field #quantity_hauteur,
                                .ap5-dimensions-row .ap5-ml-field select#quantity_hauteur {
                                    height: 28px !important;
                                    min-height: 28px !important;
                                    max-height: 28px !important;
                                    padding: 0 0 6px !important;
                                    line-height: 22px !important;
                                    box-sizing: border-box;
                                }

                                .ap5-dimensions-row .ap5-ml-field select#quantity_hauteur {
                                    -webkit-appearance: none;
                                    appearance: none;
                                    padding-right: 24px !important;
                                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23026473' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") !important;
                                    background-repeat: no-repeat !important;
                                    background-position: right center !important;
                                    background-size: 16px 16px !important;
                                }

                                .ap5-dimensions-row .ap5-ml-error {
                                    margin-bottom: 0;
                                }

                                @media (max-width: 767px) {
                                    .ap5-dimensions-row {
                                        grid-template-columns: 1fr;
                                    }
                                }
                            </style>
                            <div class="ap5-dimensions-row">
                            {if isset($packMlChoose) && $packMlChoose}
                            <div class="ap5-attributes ap5-ml-field ap5-ml-field--length">
                                <div class="clearfix hiddenlabel">
                                    <p class="font-weight-bold group-name">
                                        {l s='Choisissez la longueur en mètres linéaires' d='Shop.Theme.Custommodules'}
                                        <span class="ap5-ml-help" tabindex="0" aria-label="{l s='Aide sur le calcul des mètres linéaires' d='Shop.Theme.Custommodules'}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                            </svg>
                                            <span class="ap5-ml-help-tooltip">{l s='Calcul mètres linéaires : addition de toutes les longueurs en mètres' d='Shop.Theme.Custommodules'}</span>
                                        </span>
                                    </p>
                                    <div class="select_wrap">
                                        <div class="clearfix dis_flex">
                                                    <span class="input-group-btn-vertical dis_flex w-100">
                                                        <input type="number" id="quantity_ml" value="" class="form-control" min="1" step="1" placeholder="{l s='Renseignez votre longueur' d='Shop.Theme.Custommodules'}" required>
                                                    </span>
                                        </div>
                                    </div>
                                    <p id="quantity_ml_error" class="ap5-ml-error">{l s='Veuillez renseigner ce champ' d='Shop.Theme.Custommodules'}</p>
                                </div>
                            </div>
                            {/if}
                            {if isset($packHeightChoose) && $packHeightChoose && isset($packHeightValues) && $packHeightValues|@count > 0}
                            <div class="ap5-attributes ap5-ml-field ap5-ml-field--height">
                                <div class="clearfix hiddenlabel">
                                    <p class="font-weight-bold group-name">
                                        Sélectionnez la hauteur
                                    </p>
                                    <div class="select_wrap">
                                        <div class="clearfix dis_flex">
                                                    <span class="input-group-btn-vertical dis_flex w-100">
                                                        <select id="quantity_hauteur" class="form-control" required>
                                                            {foreach from=$packHeightValues item=heightItem name=heightLoop}
                                                                <option value="{$heightItem.value|escape:'html':'UTF-8'}"{if $smarty.foreach.heightLoop.first} selected="selected"{/if}>{AdvancedPack::formatPackHeightDisplay($heightItem.height)|escape:'html':'UTF-8'}</option>
                                                            {/foreach}
                                                        </select>
                                                    </span>
                                        </div>
                                    </div>
                                    <p id="quantity_hauteur_error" class="ap5-ml-error">Veuillez sélectionner une hauteur</p>
                                </div>
                            </div>
                            {/if}
                            {if isset($packHasMlDiscountTiers) && $packHasMlDiscountTiers && isset($packMlDiscountTiers) && $packMlDiscountTiers|@count > 0}
                            <div class="ap5-ml-degressive" role="region" aria-label="Prix dégressifs">
                                <div class="ap5-ml-degressive__cta">
                                    <strong><span class="ap5-ml-degressive__cta-line">Prix</span><span class="ap5-ml-degressive__cta-line">dégressifs&nbsp;!</span></strong>
                                </div>
                                <div class="ap5-ml-degressive__scroll">
                                    <table class="ap5-ml-degressive__table">
                                        <thead>
                                            <tr>
                                                <th scope="row">Nombre de mL</th>
                                                {foreach from=$packMlDiscountTiers item=tier}
                                                    <td>
                                                        {if $tier.to === null || $tier.to === ''}
                                                            +{$tier.from|string_format:"%g"} mL
                                                        {else}
                                                            {$tier.from|string_format:"%g"} à {$tier.to|string_format:"%g"} mL
                                                        {/if}
                                                    </td>
                                                {/foreach}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th scope="row">% de remise</th>
                                                {foreach from=$packMlDiscountTiers item=tier}
                                                    <td>{$tier.percent|string_format:"%g"}%</td>
                                                {/foreach}
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            {/if}
                            </div>
                        {/if}

                        {*
                          AP5 pack content (variants of pack items).
                          Ne pas dépendre de $groups (qui concerne les déclinaisons du PRODUIT pack),
                          sinon le bloc disparaît et ne revient qu'après une sauvegarde BO qui peut recréer des groupes.
                        *}
                        {include file='module:pm_advancedpack/views/templates/front/8/pack-product-list.tpl'}

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
                                    {if isset($packHeightChoose)}
                                        <input type="hidden" name="height_choose" id="height_choose" value="{$packHeightChoose}">
                                    {/if}
                                    <input type="hidden" name="add" value="1" />
                                    <input type="hidden" name="id_product_attribute" id="idCombination" value="{if !empty($packAttributesList)}{$packAttributesList|json_encode}{/if}" />
                                    <input type="hidden" name="is_pack" value="1" id="is_pack">
                                    <input type="hidden" name="id_customization" value="{$product.id_customization}"
                                           id="product_customization_id" class="js-product-customization-id">

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
                                        {include file='module:pm_advancedpack/views/templates/front/8/pack-price-container.tpl'}
                                    {/block}

                                    {* Start - Inté static *}
                                    <div class="d-flex gap-16 shipping-block-product-info">
                                        {if file_exists($smarty.const._PS_MODULE_DIR_|cat:'ckc_shipping_estimator/views/templates/front/product/shipping_estimator.tpl')}
                                            {include file="module:ckc_shipping_estimator/views/templates/front/product/shipping_estimator.tpl"}
                                        {/if}
                                        {assign var=ckc_id_product_attribute value=($product.id_product_attribute|default:0)|intval}
                                        {if $ckc_id_product_attribute <= 0}
                                            {assign var=ckc_id_product_attribute value=Product::getDefaultAttribute($product.id)|intval}
                                        {/if}
                                        {hook h="displayStockButton" product=['id_product' => $product.id|intval, 'id_product_attribute' => $ckc_id_product_attribute]}
                                    </div>
                                    {* End - Inté static *}


                                    {block name='product_additional_info'}

                                      {include file='catalog/_partials/product-additional-info.tpl'}

                                    {/block}
                                    {* Input to refresh product HTML removed, block kept for compatibility with themes *}



                                    {block name='product_refresh'}
                                    {/block}
                                </form>
                            {/block}
                        </div>
                        {if !$pro.env}
                            <div class="rounded-4 bg-primary text-white px-4 py-2 d-flex align-items-center justify-content-start mt-3 mb-3">
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
                        {/if}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12" id="product-tabs-section">
                    {block name='hook_display_reassurance'}
                        {hook h='displayReassurance'}
                    {/block}

                    {* Barre de navigation fixe mobile *}
                    <div class="mobile-product-nav-fixed d-md-none pack-nav" id="mobile-product-nav">
                        <div class="mobile-nav-container">
                            <ul class="mobile-nav-tabs" role="tablist">
                                <li class="mobile-nav-item">
                                    <a class="mobile-nav-link active js-product-nav-active" data-toggle="tab" href="#kit_content" role="tab" aria-controls="kit_content" aria-selected="true">
                                        {l s='Contenu du kit' d='Shop.Theme.Custommodules'}
                                    </a>
                                </li>
                                {if $product.description}
                                    <li class="mobile-nav-item">
                                        <a class="mobile-nav-link" data-toggle="tab" href="#description" role="tab" aria-controls="description">
                                            {l s='Description' d='Shop.Theme.Catalog'}
                                        </a>
                                    </li>
                                {/if}
                                <li class="mobile-nav-item">
                                    <a class="mobile-nav-link" data-toggle="tab" href="#product-details" role="tab" aria-controls="product-details">
                                        {l s='Product Details' d='Shop.Theme.Catalog'}
                                    </a>
                                </li>

                                {if $product.attachments}
                                    <li class="mobile-nav-item">
                                        <a class="mobile-nav-link" data-toggle="tab" href="#attachments" role="tab" aria-controls="attachments">
                                            {l s='Attachments' d='Shop.Theme.Catalog'}
                                        </a>
                                    </li>
                                {/if}

                                {* Onglet FAQ *}
                                {assign var="faq_module" value=Module::getInstanceByName('productfaq')}
                                {assign var="faq_has_content" value=$faq_module && $faq_module->getFaqsByProduct($product.id, true)}
                                {if $faq_has_content}
                                    <li class="mobile-nav-item">
                                        <a class="mobile-nav-link" data-toggle="tab" href="#product-faq" role="tab" aria-controls="product-faq">FAQ</a>
                                    </li>
                                {/if}
                            </ul>
                        </div>
                    </div>

                    {block name='product_tabs'}
                        <div class="tabs product-tabs" id="block-description-long">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active js-product-nav-active" href="#kit_content"
                                       data-toggle="tab" role="tab" aria-controls="kit_content" aria-selected="true">
                                        {l s='Contenu du kit' d='Shop.Theme.Custommodules'}
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

                                {* Onglet FAQ *}
                                {assign var="faq_module" value=Module::getInstanceByName('productfaq')}
                                {assign var="faq_has_content" value=$faq_module && $faq_module->getFaqsByProduct($product.id, true)}
                                {if $faq_has_content}
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#product-faq" role="tab"
                                           aria-controls="product-faq">FAQ</a>
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
                                    <div id="ap5-pack-product-container">
                                        {include file='module:pm_advancedpack/views/templates/front/8/pack-product-container.tpl'}
                                    </div>
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
                                                            <h3>{l s='Couleurs disponibles' d='Shop.Theme.Custommodules'}</h3>
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

                                {* Contenu de l'onglet FAQ *}
                                {if $faq_has_content}
                                    <div class="tab-pane fade in" id="product-faq" role="tabpanel">
                                        {include file="module:productfaq/views/templates/hook/product_faq.tpl"}
                                    </div>
                                {/if}

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

    {* Exposer la liste des composants du kit au JS (test ckc_stores_stock) *}
    <script>
        window.ckcPackList = [
        {foreach from=$productsPackUnique item=productPack name=ckc_pack_list}
            {
                key: "{$productPack.id_product|intval}:{($productPack.id_product_attribute|default:0)|intval}",
                id_product_pack: {($productPack.id_product_pack|default:0)|intval},
                id_product: {$productPack.id_product|intval},
                id_product_attribute: {($productPack.id_product_attribute|default:0)|intval},
                qty_in_pack: {$productPack.quantity|default:1|intval},
                name: "{$productPack.presentation.name|escape:'javascript'}"
            }{if !$smarty.foreach.ckc_pack_list.last},{/if}
        {/foreach}
        ];
    </script>


{/block}
