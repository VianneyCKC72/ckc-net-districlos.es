{extends file='catalog/product.tpl'}

{block name='content'}

    <div class="loader-container">
        <div class="loaderProduct"></div>
    </div>

    <div class="add_to_cart_bottom">
        <div class="container">
            <div class="dis_flex flex_cln product_title">
                <span class="h1 productpage_title">{block name='page_title'}{$product.name}{/block}</span>
                <div class="product-reviews-head">
                    {hook h='ExtraNetreviews'}
                </div>
            </div>

            <div class="product-add-to-cart clearfix js-product-add-to-cart dis_flex row">
                <div class="product-quantity product-quantity-pack">
                    <div class="price product_header_title js-product-prices">
                        {include file='module:pm_advancedpack/views/templates/front/1.7/pack-price-container.tpl'}
                    </div>
                    <div class="addProduct" {if (isset($packMlChoose) && $packMlChoose) || (isset($packHeightChoose) && $packHeightChoose && isset($packHeightValues) && $packHeightValues|@count > 0)} style="display:block" {/if}>
                        {if !$packMlChoose && !($packHeightChoose && isset($packHeightValues) && $packHeightValues|@count > 0)}
                            <div class="clearfix dis_flex" >
                                    <span class="input-group-btn-vertical dis_flex">
                                        <button class="btn btn-touchspin js-touchspin bootstrap-touchspin-down-bottom" type="button"><i class="material-icons touchspin-down"></i></button>
                                        <input type="text" name="qty" id="quantity_wanted_bottom" value="1" min="1" class="form-control" style="display: block;">
                                        <button class="btn btn-touchspin js-touchspin bootstrap-touchspin-up-bottom" type="button"><i class="material-icons touchspin-up"></i></button>
                                    </span>
                            </div>
                        {/if}
                        <div class="position-relative" {if $packMlChoose || ($packHeightChoose && isset($packHeightValues) && $packHeightValues|@count > 0)} style="width: 100%" {/if}>
                            <button class="btn btn-primary add-to-cart-bottom" data-button-action="add-to-cart" type="submit" {if $packMlChoose || ($packHeightChoose && isset($packHeightValues) && $packHeightValues|@count > 0)} style="padding: 11px !important;width: 100%;" {/if}>
                                {l s='Ajouter au panier' d='Shop.Theme.Custommodules'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <section id="main">
        <div class="row clearfix">

            {block name="breadcrumb_wrapper"}
                <div id="breadcrumb_wrapper" class="left-column right-column col-sm-4 col-md-6">

                    {block name='breadcrumb'}
                        {include file='_partials/breadcrumb.tpl'}
                    {/block}
                </div>
            {/block}
            {hook h='displayHeaderProduct'}
            <div class="product_header_title">
                <div class="dis_flex flex_cln">
                    <h1 class="productpage_title">{block name='page_title'}{$product.name}{/block}</h1>
                    <div class="dis_flex">
                        <div class="product-reviews-head">
                            {hook h='ExtraNetreviews'}
                        </div>
                        <div class="reference" id="reference_to_display">
                            {$product.reference_to_display}
                        </div>
                    </div>
                </div>
                <div class="js-product-prices">
                    {include file='module:pm_advancedpack/views/templates/front/1.7/pack-price-container.tpl'}
                </div>
            </div>

            {* Commented product reference on product page (Fleming 2023) *}

            {* {if isset($product.reference_to_display) && $product.reference_to_display neq ''}
              <div class="product-reference-head">
                <b>Référence</b>
                <span itemprop="sku" id="distri_ref_product_header" class="product-ref">{$product.reference_to_display}</span>
                <span class="reviews-separator">|</span>
              </div>
            {/if} *}

        </div>
        <div class="row">
            <div class="col-md-6 clearfix pl-0" id="distri_product_leftbar">

                {*Section images*}
                <div class="col-md-12 pro_image pl-0">
                    {block name='page_content_container'}
                        <section class="page-content" id="content">
                            <div class="product-leftside">
                                {block name='page_content'}
                                    {block name='product_flags'}
                                        <ul class="product-flags">
                                            {if $env_pro && $promo_pro}
                                                <li class="product-flag discount">
                                                    {$promo_pro.label}
                                                </li>
                                            {/if}
                                            {foreach from=$product.flags item=flag}
                                                {if $flag.type != "out_of_stock"}
                                                    <li class="product-flag {$flag.type}">
                                                        {if $flag.label == "Neuf"}
                                                            {l s='Nouveau' d='Shop.Theme.Custommodules'}
                                                        {else}
                                                            {$flag.label}
                                                        {/if}
                                                    </li>
                                                {/if}
                                            {/foreach}
                                        </ul>
                                    {/block}

                                    {block name='product_cover_thumbnails'}
                                        {include file='modules/ets_product_slideshow/views/templates/hook/catalog/product-cover-thumbnails.tpl'}
                                    {/block}
                                    <div class="scroll-box-arrows">
                                        <i class="material-icons left">&#xE314;</i>
                                        <i class="material-icons right">&#xE315;</i>
                                    </div>

                                {/block}
                        </section>
                    {/block}
                </div>
            </div>

            {*rightbar*}
            <div id="product_distri_rightbar" class="col-md-6 order-md-1 pr-0">
                <div id="productFixed">
                    {*Titres / reviews / ref *}
                    <div class="clearfix">
                        {block name='page_header_container'}
                            {block name='page_header'}

                            {/block}
                        {/block}
                        {capture name='displayProductListReviews'}{hook h='displayProductListReviews' product=$product}{/capture}
                        {if $smarty.capture.displayProductListReviews}
                            <div class="hook-reviews">
                                {hook h='displayProductListReviews' product=$product}
                            </div>
                        {/if}
                    </div>

                    {*Section Short descr*}
                    <div class="col-md-12 pro_image">
                        {block name='product_description_short'}
                            <div id="product-description-short-{$product.id}">{$product.description_short nofilter}</div>
                        {/block}
                    </div>

                    {* declinaisons / actions /hook *}
                    {if $product.is_customizable && count($product.customizations.fields)}
                        {block name='product_customization'}
                            {include file="catalog/_partials/product-customization.tpl" customizations=$product.customizations}
                        {/block}
                    {/if}

                    <div class="product-actions clearfix">


                        {block name='product_buy'}
                            <form action="{pm_advancedpack::getPackAddCartURL($product.id)}" method="post" id="add-to-cart-or-refresh" class="ap5-buy-block">
                                <input type="hidden" name="token" value="{$static_token}" />
                                <input type="hidden" name="id_product" value="{$product.id|intval}" id="product_page_product_id" />
                                <input type="hidden" name="add" value="1" />
                                <input type="hidden" name="id_product_attribute" id="idCombination" value="{if !empty($packAttributesList)}{$packAttributesList|json_encode}{/if}" />
                                <input type="hidden" name="auto_choose" id="auto_choose" value="{$packAutoChoose}">
                                <input type="hidden" name="ml_choose" id="ml_choose" value="{$packMlChoose}">
                                <input type="hidden" name="height_choose" id="height_choose" value="{$packHeightChoose}">
                                <input type="hidden" name="is_pack" value="1" id="is_pack">

                                {block name='product_discounts'}
                                    {include file='catalog/_partials/product-discounts.tpl'}
                                {/block}

                                {block name='ap5_product_list'}
                                    {* Product list of the pack *}
                                    {include file='module:pm_advancedpack/views/templates/front/1.7/pack-product-list.tpl'}
                                {/block}

                                {block name='product_pack'}
                                    {if $packItems}
                                        <section class="product-pack">
                                            <h3 class="h4">{l s='This pack contains' d='Shop.Theme.Catalog'}</h3>
                                            {foreach from=$packItems item="product_pack"}
                                                {block name='product_miniature'}
                                                    {include file='catalog/_partials/miniatures/pack-product.tpl' product=$product_pack}
                                                {/block}
                                            {/foreach}
                                        </section>
                                    {/if}
                                {/block}

                                {if $packMlChoose || ($packHeightChoose && isset($packHeightValues) && $packHeightValues|@count > 0)}
                                    <span class="bold">{l s='Dimensions' d='Shop.Theme.Custommodules'}</span>

                                    <style>
                                        .ap5-dimensions-row .group-name {
                                            display: flex;
                                            align-items: center;
                                            gap: 8px;
                                            margin: 0 0 10px;
                                        }

                                        .ap5-dimensions-row .ap5-ml-field-item {
                                            background: #E8F4F6;
                                            border: 0;
                                            border-radius: 4px;
                                            padding: 14px 16px;
                                        }

                                        .ap5-dimensions-row .ap5-ml-field-item input,
                                        .ap5-dimensions-row .ap5-ml-field-item select {
                                            background: transparent !important;
                                        }

                                        .ap5-ml-help {
                                            position: relative;
                                            display: inline-flex;
                                            align-items: center;
                                            justify-content: center;
                                            width: 20px;
                                            height: 20px;
                                            color: #026473;
                                            cursor: help;
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
                                            line-height: 1.3;
                                            visibility: hidden;
                                            opacity: 0;
                                            z-index: 15;
                                        }

                                        .ap5-ml-help:hover .ap5-ml-help-tooltip,
                                        .ap5-ml-help:focus .ap5-ml-help-tooltip {
                                            visibility: visible;
                                            opacity: 1;
                                        }

                                        .product-variants.ap5-dimensions-row {
                                            display: grid !important;
                                            grid-template-columns: 1fr;
                                            gap: 16px;
                                            margin-bottom: 1rem;
                                            align-items: stretch;
                                            min-width: 0;
                                            border-left: 0;
                                            padding: 0;
                                        }

                                        .product-variants.ap5-dimensions-row:has(.ap5-ml-field-item--length):has(.ap5-ml-field-item--height) {
                                            grid-template-columns: repeat(2, minmax(0, 1fr));
                                        }

                                        .ap5-dimensions-row .ap5-ml-field-item {
                                            display: flex;
                                            flex-direction: column;
                                            min-width: 0;
                                            align-self: stretch;
                                        }

                                        .ap5-dimensions-row .group-name {
                                            flex: 0 0 auto;
                                            min-height: 52px;
                                            margin-bottom: 10px;
                                            align-items: flex-start;
                                        }

                                        .ap5-dimensions-row:not(:has(.ap5-ml-field-item--height)) .ap5-ml-field-item--length .group-name,
                                        .ap5-dimensions-row:not(:has(.ap5-ml-field-item--length)) .ap5-ml-field-item--height .group-name {
                                            min-height: 0;
                                        }

                                        .ap5-dimensions-row .select_wrap {
                                            flex: 0 0 auto;
                                        }

                                        .ap5-dimensions-row .clearfix::after {
                                            display: none;
                                        }

                                        .ap5-dimensions-row .ap5-ml-field-item #quantity_ml,
                                        .ap5-dimensions-row .ap5-ml-field-item #quantity_hauteur,
                                        .ap5-dimensions-row .ap5-ml-field-item select#quantity_hauteur {
                                            display: block;
                                            width: 100%;
                                            height: 28px !important;
                                            min-height: 28px !important;
                                            max-height: 28px !important;
                                            padding: 0 0 6px !important;
                                            margin: 0;
                                            line-height: 22px !important;
                                            box-sizing: border-box;
                                            border: 0 !important;
                                            border-bottom: 2px solid #026473 !important;
                                            border-radius: 0 !important;
                                            box-shadow: none !important;
                                            outline: none;
                                            color: #222;
                                        }

                                        .ap5-dimensions-row .ap5-ml-field-item #quantity_ml::placeholder {
                                            color: #8a8a8a !important;
                                            opacity: 1;
                                        }

                                        .ap5-dimensions-row .ap5-ml-field-item #quantity_ml:focus,
                                        .ap5-dimensions-row .ap5-ml-field-item #quantity_hauteur:focus {
                                            border-bottom: 2px solid #026473 !important;
                                            box-shadow: none !important;
                                        }

                                        .ap5-dimensions-row .ap5-ml-field-item select#quantity_hauteur {
                                            -webkit-appearance: none;
                                            appearance: none;
                                            padding-right: 24px !important;
                                            background-color: #E8F4F6 !important;
                                            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23026473' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") !important;
                                            background-repeat: no-repeat !important;
                                            background-position: right center !important;
                                            background-size: 16px 16px !important;
                                        }

                                        .ap5-dimensions-row .ap5-ml-error {
                                            margin-bottom: 0;
                                        }

                                        @media (min-width: 768px) {
                                            .product-variants.ap5-dimensions-row:has(.ap5-ml-field-item--length):has(.ap5-ml-field-item--height) {
                                                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                                            }
                                        }

                                        @media (max-width: 767px) {
                                            .product-variants.ap5-dimensions-row {
                                                grid-template-columns: 1fr;
                                            }
                                        }

                                        .ap5-ml-error {
                                            display: none;
                                            margin-top: 6px;
                                            color: #f33;
                                            font-size: 13px;
                                        }

                                        .ap5-ml-error.ap5-ml-error-visible {
                                            display: block;
                                        }
                                    </style>

                                    <div class="product-variants ap5-attributes ap5-dimensions-row">
                                        {if $packMlChoose}
                                        <div class="clearfix hiddenlabel ap5-ml-field-item ap5-ml-field-item--length">
                                            <p class="group-name">
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
                                                        <input type="number" name="qty_bis" id="quantity_ml" value="" class="form-control" style="display: block;" min="1" step="1" placeholder="{l s='Renseignez votre longueur' d='Shop.Theme.Custommodules'}" required>
                                                    </span>
                                                </div>
                                            </div>
                                            <p id="quantity_ml_error" class="ap5-ml-error">{l s='Veuillez renseigner ce champ' d='Shop.Theme.Custommodules'}</p>
                                        </div>
                                        {/if}
                                        {if $packHeightChoose && isset($packHeightValues) && $packHeightValues|@count > 0}
                                        <div class="clearfix hiddenlabel ap5-ml-field-item ap5-ml-field-item--height">
                                            <p class="group-name">
                                                {l s='Sélectionnez la hauteur' d='Shop.Theme.Custommodules'}
                                            </p>
                                            <div class="select_wrap">
                                                <div class="clearfix dis_flex">
                                                    <span class="input-group-btn-vertical dis_flex w-100">
                                                        <select id="quantity_hauteur" class="form-control" style="display: block;" required>
                                                            {foreach from=$packHeightValues item=heightItem name=heightLoop}
                                                                <option value="{$heightItem.value|escape:'html':'UTF-8'}"{if $smarty.foreach.heightLoop.first} selected="selected"{/if}>{AdvancedPack::formatPackHeightDisplay($heightItem.height)|escape:'html':'UTF-8'}</option>
                                                            {/foreach}
                                                        </select>
                                                    </span>
                                                </div>
                                            </div>
                                            <p id="quantity_hauteur_error" class="ap5-ml-error">{l s='Veuillez sélectionner une hauteur' d='Shop.Theme.Custommodules'}</p>
                                        </div>
                                        {/if}
                                        {if isset($packHasMlDiscountTiers) && $packHasMlDiscountTiers && isset($packMlDiscountTiers) && $packMlDiscountTiers|@count > 0}
                                        <div class="ap5-ml-degressive" role="region" aria-label="{l s='Prix dégressifs' d='Shop.Theme.Custommodules'}">
                                            <div class="ap5-ml-degressive__cta">
                                                <strong>{l s='[1]Prix[/1][2]dégressifs ![/2]' d='Shop.Theme.Custommodules' sprintf=['[1]' => '<span class="ap5-ml-degressive__cta-line">', '[/1]' => '</span>', '[2]' => '<span class="ap5-ml-degressive__cta-line">', '[/2]' => '</span>']}</strong>
                                            </div>
                                            <div class="ap5-ml-degressive__scroll">
                                                <table class="ap5-ml-degressive__table">
                                                    <thead>
                                                        <tr>
                                                            <th scope="row">{l s='Nombre de mL' d='Shop.Theme.Custommodules'}</th>
                                                            {foreach from=$packMlDiscountTiers item=tier}
                                                                {capture assign='tier_from'}{$tier.from|string_format:"%g"}{/capture}
                                                                <td>
                                                                    {if $tier.to === null || $tier.to === ''}
                                                                        {l s='+%from% mL' d='Shop.Theme.Custommodules' sprintf=['%from%' => $tier_from]}
                                                                    {else}
                                                                        {capture assign='tier_to'}{$tier.to|string_format:"%g"}{/capture}
                                                                        {l s='%from% à %to% mL' d='Shop.Theme.Custommodules' sprintf=['%from%' => $tier_from, '%to%' => $tier_to]}
                                                                    {/if}
                                                                </td>
                                                            {/foreach}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row">{l s='% de remise' d='Shop.Theme.Custommodules'}</th>
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

                                {block name='product_add_to_cart'}
                                    {include file='catalog/_partials/product-add-to-cart.tpl'}
                                {/block}

                                {block name='product_select_shop'}
                                    {include file='catalog/_partials/product-select-shop.tpl'}
                                {/block}



                                {* moved below image
                                     {block name='product_additional_info'}
                                       {include file='catalog/_partials/product-additional-info.tpl'}
                                     {/block}
                                     *}

                                {* Input to refresh product HTML removed, block kept for compatibility with themes *}
                                {block name='product_refresh'}{/block}
                            </form>
                        {/block}
                    </div>
                </div>
            </div>
            {block name='product_select_shop_modal'}
                {include file='catalog/_partials/product-select-shop-modal.tpl'}
            {/block}
            {block name='product_delivery_modal'}
                {include file='catalog/_partials/product-delivery-modal.tpl'}
            {/block}
            {block name='product_additional_info'}
                {include file='catalog/_partials/product-additional-info.tpl'}
            {/block}
        </div>
    </section>
    </div>
    </div>
<div class="prix_juste">
    <div class="background_beige">
        {if !$from_europe}
            <div class="div_grid_beige">
                <div class="image-container"><img src="/themes/auto_store/assets/img/prix_juste/drapeaufr.webp" alt="{l s='drapeau de la France' d='Shop.Theme.Custommodules'}" /></div>
                <div class="text-container"><span class="first_span">{l s='Produits fabriqués en France' d='Shop.Theme.Custommodules'}</span> <span class="second_span">{l s='Faciles à monter et garantis 10 ans' d='Shop.Theme.Custommodules'}</span></div>
            </div>
        {else}
            <div class="div_grid_beige">
                <div class="image-container"><img src="/themes/auto_store/assets/img/prix_juste/drapeaueu.webp" alt="{l s='drapeau de l\'Europe' d='Shop.Theme.Custommodules'}" style="width:66px;" /></div>
                <div class="text-container"><span class="first_span">{l s='Produits fabriqués en Europe' d='Shop.Theme.Custommodules'}</span> <span class="second_span">{l s='Faciles à monter et garantis 10 ans' d='Shop.Theme.Custommodules'}</span></div>
            </div>
        {/if}
        <div class="div_grid_beige">
            <div class="image-container"><img src="/themes/auto_store/assets/img/prix_juste/france_pin.webp" alt="{l s='Pin sur la carte de france' d='Shop.Theme.Custommodules'}" /></div>
            <div class="text-container"><span class="first_span">{l s='16 magasins près de chez vous' d='Shop.Theme.Custommodules'}</span> <span class="second_span">{l s='Des experts disponibles dans toute la France' d='Shop.Theme.Custommodules'}</span></div>
        </div>
        <div class="div_grid_beige">
            <div class="image-container"><img src="/themes/auto_store/assets/img/prix_juste/carte.webp" alt="{l s='carte banquaire' d='Shop.Theme.Custommodules'}" /></div>
            <div class="text-container"><span class="first_span">{l s='Paiement en plusieurs fois gratuit' d='Shop.Theme.Custommodules'}</span> <span class="second_span">{l s='3x 4x sans frais et jusqu\'à 48x ' d='Shop.Theme.Custommodules'}</span></div>
        </div>
        <div class="div_grid_beige">
            <div class="image-container"><img src="/themes/auto_store/assets/img/prix_juste/camion.webp" alt="{l s='camion' d='Shop.Theme.Custommodules'}" /></div>
            <div class="text-container"><span class="first_span">{l s='Livraison avec nos camions' d='Shop.Theme.Custommodules'}</span> <span class="second_span">{l s='Prise de rendez-vous pour la livraison ' d='Shop.Theme.Custommodules'}</span></div>
        </div>
        <div class="div_grid_beige refund">
            <div class="image-container"><img src="/themes/auto_store/assets/img/prix_juste/etiquette2.webp" alt="{l s='etiquette' d='Shop.Theme.Custommodules'}" /></div>
            <div class="text-container"><span class="first_span">{l s='Garantie les moins chers' d='Shop.Theme.Custommodules'}</span> <span class="second_span appar">{l s='On vous rembourse la différence !' d='Shop.Theme.Custommodules'}</span></div>
        </div>
    </div>
</div>
<div class="container">
    <div>
        <div>
            <section>
                <div class="row mt-40">
                    <div class="col-md-12">
                        {*Section Onglets accordéon*}
                        <div class="col-md-12 pro_onglet" style="margin-top: 1rem; padding: 0;">

                            <section class="product-tabcontent main-tabs">
                                {block name='product_tabs'}
                                    <div class="tabs">
                                        <ul class="nav flex_wrap dis_flex header">
                                            <li class="nav-item position-relative active">
                                                <a class="nav-link" href="#kit_content"
                                                   role="tab">
                                                    {l s='Contenu du kit' d='Shop.Theme.Custommodules'}
                                                    <span class="arrow_tab"> > </span>
                                                </a>
                                            </li>
                                            {if $product.description}
                                                <li class="nav-item position-relative">
                                                    <a class="nav-link" href="#description"
                                                       role="tab">
                                                        {l s='Description' d='Shop.Theme.Catalog'}
                                                        <span class="arrow_tab"> > </span>
                                                    </a>
                                                </li>
                                            {/if}
                                            {* Détail produit *}
                                            <li class="nav-item position-relative">
                                                <a class="nav-link" href="#product-details">{l s='Product Details' d='Shop.Theme.Catalog'}
                                                    <span class="arrow_tab"> > </span>
                                                </a>
                                            </li>
                                            {if $product.attachments}
                                                <li class="nav-item position-relative">
                                                    <a class="nav-link" href="#attachments">{l s='Attachments' d='Shop.Theme.Catalog'} <span class="arrow_tab"> > </span></a>
                                                </li>
                                            {/if}
                                            {if isset($have_accessories) && $have_accessories}
                                                <li class="nav-item position-relative">
                                                    <a class="nav-link" href="#accessories">{l s='Accessoires' d='Shop.Theme.Custommodules'}
                                                        <span class="arrow_tab"> > </span>
                                                    </a>
                                                </li>
                                            {/if}
                                            {foreach from=$product.extraContent item=extra key=extraKey}
                                                {if $extra.title == 'Avis Vérifiés'}
                                                    <li class="nav-item position-relative" data-tab="false">
                                                        <a class="nav-link" href="#extra-{$extraKey}"
                                                           aria-controls="extra-{$extraKey}">{l s='Avis' d='Shop.Theme.Custommodules'} <span class="arrow_tab"> > </span></a>
                                                    </li>
                                                {elseif $extra.title != ''}
                                                    <li class="nav-item position-relative">
                                                        <a class="nav-link" href="#extra-{$extraKey}"
                                                           aria-controls="extra-{$extraKey}">{$extra.title} <span class="arrow_tab"> > </span></a>
                                                    </li>
                                                {/if}
                                            {/foreach}
                                        </ul>
                                    </div>
                                {/block}
                            </section>
                        </div>

                        <div class="col-md-12 pro_onglet mt-50 pl-0 pr-0">

                            <section class="product-tabcontent">
                                {block name='product_tabs'}
                                    <div class="tabs">
                                        <ul class="nav nav-tabs flex_wrap p_btwn">
                                            <li class="nav-item">
                                                <div class="tab-pane active" id="kit_content" role="tabpanel">
                                                    {foreach from=$productsPackUnique item=productPack name=productPack_list}
                                                        <div class="row">
                                                            <div class="col-md-2 pos_relative dis_flex">
                                                                {assign var=productPackImageIds value="`$productPack.id_product`-`$productPack.image.id_image`"}
                                                                <div style="padding: 20px;box-shadow: 0 10px 20px #0000000d;width: 100%;" class="dis_flex">
                                                                    <img src="{$pmlink->getImageLink($productPack.presentation.link_rewrite, $productPackImageIds, $imageFormatProductSlideshow)}" alt="{$productPack.image.legend}" style="width: auto;max-height: 190px;max-width: 100%;margin: auto;">
                                                                </div>
                                                                <div class="pos_absolute w-100 dis_flex" style="bottom: -16px !important;">
                                                                    <span class="text-center m-auto" style="padding: 5px 20px;background-color: #79C144;width: 83px;color: #fff;font-weight: 600;font-size: 16px;">x{$productPack.quantity}</span>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-10 content-kit-description">
                                                                <h3 class="h5 text-uppercase fs-16">{$productPack.presentation.name}</h3>
                                                                <div class="product-description">{$productPack.presentation.description_short nofilter}</div>
                                                                <div class="mt-10">
                                                                    <a class="btn btn-primary add_to_cart" href="{$productPack.presentation.link}">{l s='Voir le produit' d='Shop.Theme.Custommodules'}</a>
                                                                </div>
                                                                {block name='product_reviews'}
                                                                    {hook h='displayProductListReviews' product=$productPack.presentation}
                                                                {/block}
                                                            </div>
                                                        </div>
                                                    {/foreach}
                                                </div>
                                            </li>
                                            {if $product.description}
                                                <li class="nav-item">
                                                    <div class="tab-pane" id="description" role="tabpanel">
                                                        {block name='product_description'}
                                                            <div class="product-description">{$product.description nofilter}</div>
                                                        {/block}
                                                    </div>
                                                </li>
                                            {/if}
                                            {* Détail produit *}
                                            <li class="nav-item no_marg pr-0 pl-0">
                                                {block name='product_details'}
                                                    {include file='catalog/_partials/product-details.tpl'}
                                                {/block}

                                                {if $product.attachments}
                                                    {block name='product_attachments'}
                                                        {if $product.attachments}
                                                            <div class="tab-pane fade in" id="attachments" role="tabpanel">
                                                                <section class="product-attachments">
                                                                    {foreach from=$product.attachments item=attachment}
                                                                        <a href="{url entity='attachment' params=['id_attachment' => $attachment.id_attachment]}">
                                                                            <h3 class="h5 text-uppercase">
                                                                                <img src="/themes/auto_store/assets/img/icn_download.svg" width="100" alt="icone téléchargement pièces jointes districlos" class="unveil-loaded icnDL no_lazy_load">
                                                                            </h3>
                                                                            <div class="attachment">
                                                                                {l s='Download' d='Shop.Theme.Actions'}<br> ({$attachment.file_size_formatted})
                                                                                <h4>{$attachment.description}</h4>
                                                                            </div>
                                                                        </a>
                                                                    {/foreach}
                                                                </section>
                                                            </div>
                                                        {/if}
                                                    {/block}
                                                {/if}
                                            </li>
                                            {if isset($have_accessories) && $have_accessories}
                                                <li class="nav-item">
                                                    <div class="tab-pane fade in" id="accessories" role="tabpanel">
                                                        {hook h='displayRightColumnProduct' product=$product}
                                                    </div>
                                                </li>
                                            {/if}
                                            {foreach from=$product.extraContent item=extra key=extraKey}
                                                {if $extra.title == 'Avis Vérifiés'}
                                                    <li>
                                                        <div class="tab-content" id="tab-content" {if $extraKey == 0} style="padding: 0;"{/if}>

                                                            {foreach from=$product.extraContent item=extra key=extraKey}
                                                            <div class="tab-pane fade in show {$extra.attr.class}" id="extra-{$extraKey}" role="tabpanel"
                                                            {foreach $extra.attr as $key => $val} {$key}="{$val}" {/foreach}>
                                                            {$extra.content nofilter}
                                                        </div>
                                                        {/foreach}
                                                    </li>
                                                {else}
                                                    <li class="nav-item">
                                                        <div class="tab-content" id="tab-content" {if $extraKey == 0} style="padding: 0;"{/if}>

                                                            {foreach from=$product.extraContent item=extra key=extraKey}
                                                            <div class="tab-pane fade in {$extra.attr.class}" id="extra-{$extraKey}" role="tabpanel"
                                                            {foreach $extra.attr as $key => $val} {$key}="{$val}" {/foreach}>
                                                            {$extra.content nofilter}
                                                        </div>
                                                        {/foreach}
                                                    </li>
                                                {/if}
                                            {/foreach}
                                        </ul>
                                    </div>
                                {/block}
                            </section>
                        </div>
                    </div>
                </div>

                {block name='product_accessories'}
                    {if $accessories}
                        {assign var='sliderFor' value=5}
                        <!-- Define Number of product for SLIDER -->
                        {assign var='productCount' value=count($accessories)}

                        <section class="product-accessories clearfix">
                            <div id="spe_title_bottom">
                                <h2 class="h1 products-section-title text-uppercase test">
                                    {*{l s='You might also like' d='Shop.Theme.Catalog'}*}{l s='Dans la même catégorie' d='Shop.Theme.Custommodules'}
                                </h2>
                            </div>
                            <div id="spe_res">
                                <div class="products">
                                    {if $productCount >= $sliderFor}
                                        <div class="customNavigation">
                                            <a class="btn prev accessories_prev">&nbsp;</a>
                                            <a class="btn next accessories_next">&nbsp;</a>
                                        </div>
                                    {/if}
                                    {if $productCount >= $sliderFor}
                                    <ul id="accessories-carousel" class="tm-carousel product_list">
                                        {else}
                                        <ul id="accessories-grid" class="accessories_grid product_list grid gridcount">
                                            {/if}
                                            {foreach from=$accessories item="product_accessory"}
                                                {block name='product_miniature'}
                                                    <li
                                                            class="{if $productCount >= $sliderFor}item{else}product_item col-xs-12 col-sm-6 col-md-4 col-lg-3{/if}">
                                                        {include file='catalog/_partials/miniatures/product.tpl' product=$product_accessory}
                                                    </li>
                                                {/block}
                                            {/foreach}
                                        </ul>

                                </div>
                            </div>

                        </section>
                    {/if}
                {/block}

                {block name='product_footer'}
                    {hook h='displayFooterProduct' product=$product category=$category}
                {/block}

                {block name='product_images_modal'}
                    {include file='catalog/_partials/product-images-modal.tpl'}
                {/block}

                {block name='page_footer_container'}
                    <footer class="page-footer">
                        {block name='page_footer'}
                            <!-- Footer content -->
                        {/block}
                    </footer>
                {/block}
            </section>
        </div>

{*    <section id="main" itemscope itemtype="https://schema.org/Product">*}
{*        <meta itemprop="url" content="{$product.url}">*}

{*        *}{* Pack title *}
{*        {block name='ap5_pack_title'}*}
{*            <div class="row">*}
{*                <div class="col-xs-12 col-12">*}
{*                    {block name='page_header_container'}*}
{*                        {block name='page_header'}*}
{*                            <h1 class="h1" itemprop="name">{block name='page_title'}{$product.name}{/block}</h1>*}
{*                            {if $product.description_short}*}
{*                                <div class="text-xs-justify text-justify" itemprop="description">{$product.description_short nofilter}</div>*}
{*                            {/if}*}
{*                        {/block}*}
{*                    {/block}*}
{*                </div>*}
{*            </div>*}
{*        {/block}*}

{*        <div class="row">*}

{*            {block name='ap5_product_list'}*}
{*                *}{* Product list of the pack *}
{*                {include file='module:pm_advancedpack/views/templates/front/1.7/pack-product-list.tpl'}*}
{*            {/block}*}

{*            {block name='ap5_buy_block'}*}
{*                *}{* Buy block *}
{*                {include file='module:pm_advancedpack/views/templates/front/1.7/pack-price-container.tpl'}*}
{*            {/block}*}
{*        </div>*}

{*        {block name='ap5_pack_description'}*}
{*            *}{* Pack description *}
{*            {if $product.description}*}
{*                <div class="row">*}
{*                    <div id="ap5-pack-description-block" class="card">*}
{*                        <div class="card-header">*}
{*                            <h3 class="page-product-heading">{l s='Pack description' mod='pm_advancedpack'}</h3>*}
{*                        </div>*}
{*                        <div class="card-block">*}
{*                            <div class="rte">{$product.description nofilter}</div>*}
{*                        </div>*}
{*                    </div>*}
{*                </div>*}
{*            {/if}*}
{*        {/block}*}

{*        {block name='ap5_tabs_header'}*}
{*            *}{* Product tabs *}
{*            {if $packShowProductsFeatures || $packShowProductsShortDescription || $packShowProductsLongDescription}*}
{*                <div class="row">*}
{*                    <div id="ap5-pack-content-block" class="card">*}
{*                        <div class="card-header">*}
{*                            <h3 class="page-product-heading">{l s='Pack content' mod='pm_advancedpack'}</h3>*}
{*                        </div>*}
{*                        <div class="card-block">*}
{*                            {include file='module:pm_advancedpack/views/templates/front/1.7/pack-product-list-tabs.tpl'}*}
{*                        </div>*}
{*                    </div>*}
{*                </div>*}
{*            {/if}*}
{*        {/block}*}

{*        {block name='ap5_product_attachments'}*}
{*            {if $product.attachments}*}
{*                <div class="row">*}
{*                    <div id="ap5-pack-download-block" class="card">*}
{*                        <div class="card-header">*}
{*                            <h3 class="page-product-heading">{l s='Download' d='Shop.Theme.Actions'}</h3>*}
{*                        </div>*}
{*                        <div class="card-block">*}
{*                            {foreach from=$product.attachments item=attachment}*}
{*                                <div class="attachment">*}
{*                                    <h4>*}
{*                                        <a href="{url entity='attachment' params=['id_attachment' => $attachment.id_attachment]}">*}
{*                                            {$attachment.name}*}
{*                                        </a>*}
{*                                    </h4>*}
{*                                    <p>{$attachment.description}</p>*}
{*                                    <a href="{url entity='attachment' params=['id_attachment' => $attachment.id_attachment]}">*}
{*                                        {l s='Download' d='Shop.Theme.Actions'} ({$attachment.file_size_formatted})*}
{*                                    </a>*}
{*                                </div>*}
{*                            {/foreach}*}
{*                        </div>*}
{*                    </div>*}
{*                </div>*}
{*            {/if}*}
{*        {/block}*}

{*        {block name='ap5_accessories'}*}
{*            {block name='product_accessories'}*}
{*                {if $accessories}*}
{*                    <section class="product-accessories clearfix">*}
{*                        <h3 class="h5 text-uppercase">{l s='You might also like' d='Shop.Theme.Catalog'}</h3>*}
{*                        <div class="products">*}
{*                            {foreach from=$accessories item="product_accessory"}*}
{*                                {block name='product_miniature'}*}
{*                                    {include file='catalog/_partials/miniatures/product.tpl' product=$product_accessory}*}
{*                                {/block}*}
{*                            {/foreach}*}
{*                        </div>*}
{*                    </section>*}
{*                {/if}*}
{*            {/block}*}
{*        {/block}*}

{*        {block name='ap5_footer'}*}
{*            {block name='product_footer'}*}
{*                {hook h='displayFooterProduct' product=$product category=$category}*}
{*            {/block}*}
{*        {/block}*}

{*        {block name='ap5_images'}*}
{*            {block name='product_images_modal'}*}
{*                {include file='catalog/_partials/product-images-modal.tpl'}*}
{*            {/block}*}
{*        {/block}*}

{*        {block name='ap5_footer_container'}*}
{*            {block name='page_footer_container'}*}
{*                <footer class="page-footer">*}
{*                    {block name='page_footer'}*}
{*                        <!-- Footer content -->*}
{*                    {/block}*}
{*                </footer>*}
{*            {/block}*}
{*        {/block}*}
{*    </section>*}

{/block}
