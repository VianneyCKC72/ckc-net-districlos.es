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

{capture assign="productClasses"}{if !empty($productClass)}{$productClass}{else}col-xs-12 col-sm-6 col-lg-3 col-xl-3{/if}{/capture}

{* ─────────────────────────────────────────────────────────────────────────────
   Bannière listing catégorie
   • Avant le 24/06/2025 00h01 : comportement existant (jours fous / promo générique)
   • À partir du 24/06/2025 00h01 : visuels soldes par famille de catégories
   ───────────────────────────────────────────────────────────────────────────── *}
{if $page.page_name == 'category'}

    {assign var=_cat_id   value=Context::getContext()->controller->getCategory()->id}
    {assign var=_now_ts   value=$smarty.now}
    {assign var=_soldes_ts value=1750719660}{* timestamp UTC de 2025-06-19 00:01:00 (Europe/Paris = UTC+2, donc UTC 22h01 le 23/06) *}

    {if $_now_ts >= $_soldes_ts}

        {* ── SOLDES : visuels par famille de catégories ── *}
        {if in_array($_cat_id, [215, 356, 216])}
            {assign var=category_listing_banner_file value='promo_category_cloture_alu.png'}

        {elseif in_array($_cat_id, [438, 439, 441])}
            {assign var=category_listing_banner_file value='promo_category_cloture_composite.png'}

        {elseif in_array($_cat_id, [34, 133, 6])}
            {assign var=category_listing_banner_file value='promo_category_lattes_pvc.png'}

        {elseif in_array($_cat_id, [241, 243, 279, 293])}
            {assign var=category_listing_banner_file value='promo_category_portails_alu.png'}

        {elseif in_array($_cat_id, [245, 368, 367, 4])}
            {assign var=category_listing_banner_file value='promo_category_moteurs.png'}

        {elseif in_array($_cat_id, [337, 323, 130, 169, 171, 280, 294])}
            {assign var=category_listing_banner_file value='promo_category_pack_portailportillon_alu.png'}

        {elseif in_array($_cat_id, [3, 93, 94, 96, 95, 97, 153, 92, 324])}
            {assign var=category_listing_banner_file value='promo_category_rigide.jpg'}

        {else}
            {* Catégorie sans visuel soldes spécifique → fallback générique *}
            {assign var=category_listing_banner_file value='category-listing-promo.jpg'}
        {/if}

    {else}

        {* ── AVANT SOLDES : comportement d'origine inchangé ── *}
        {if in_array($_cat_id, [169, 240, 243, 339, 337, 323, 171, 239, 241, 366, 4])}
            {assign var=category_listing_banner_file value='category-joursfous.jpg'}
        {else}
            {assign var=category_listing_banner_file value='category-listing-promo.jpg'}
        {/if}

    {/if}

{else}
    {assign var=category_listing_banner_file value='category-listing-promo.jpg'}
{/if}
{* ───────────────────────────────────────────────────────────────────────────── *}

<div class="products{if !empty($cssClass)} {$cssClass}{/if} {if (isset($smarty.cookies.show_list)) && ($page.page_name == 'category' ||  $page.page_name == 'search')} list_view {/if}">
    {foreach from=$products item="product" key="position" name=productsList}
        {include file="catalog/_partials/miniatures/product.tpl" product=$product position=$position productClasses=$productClasses}

        {* Bannière catégorie : mobile = après le 1er produit (< md, 2 colonnes) ; desktop = après le 3e (>= lg, 4 colonnes). Première page grille uniquement. Masquée en espace pro. *}
        {if !$pro.env && $page.page_name == 'category' && isset($listing.pagination.items_shown_from) && $listing.pagination.items_shown_from == 1 && !isset($smarty.cookies.show_list)}
            {if $smarty.foreach.productsList.iteration == 1}
                <div class="category-listing-banner category-listing-banner--mobile col-6 col-sm-6 d-md-none">
                    <div class="category-listing-banner__inner">
                        <img class="img-fluid category-listing-banner__img" src="{$urls.theme_assets}img/{$category_listing_banner_file}" alt="" loading="lazy" width="400" height="711">
                    </div>
                </div>
            {/if}
            {if $smarty.foreach.productsList.iteration == 3}
                <div class="category-listing-banner category-listing-banner--desktop d-none d-lg-block col-lg-3 col-xl-3">
                    <div class="category-listing-banner__inner">
                        <img class="img-fluid category-listing-banner__img" src="{$urls.theme_assets}img/{$category_listing_banner_file}" alt="" loading="lazy" width="400" height="711">
                    </div>
                </div>
            {/if}
        {/if}
    {/foreach}
</div>
