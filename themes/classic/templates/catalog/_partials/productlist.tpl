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

{* Bannière listing catégorie — campagne Black2weeks *}
{assign var=_cat_id value=0}
{assign var=category_listing_banner_file value=''}
{if $page.page_name == 'category'}
    {assign var=_cat_id value=Context::getContext()->controller->getCategory()->id}
{/if}
{if isset($language.iso_code) && $language.iso_code == 'en'}
    {assign var=_is_en value=1}
{else}
    {assign var=_is_en value=0}
{/if}
{* Clôture alu + composite *}
{if in_array($_cat_id, [215, 356, 216, 438, 439, 441])}
    {if $_is_en}
        {assign var=category_listing_banner_file value='black2weeks-categorie-alu-composite-en.webp'}
    {else}
        {assign var=category_listing_banner_file value='black2weeks-categorie-alu-composite.webp'}
    {/if}
{* Clôture / grillage rigide *}
{elseif in_array($_cat_id, [3, 93, 94, 96, 95, 97, 153, 92, 324])}
    {if $_is_en}
        {assign var=category_listing_banner_file value='black2weeks-categorie-rigide-en.webp'}
    {else}
        {assign var=category_listing_banner_file value='black2weeks-categorie-rigide.webp'}
    {/if}
{* Portail de jardin (Garden) *}
{elseif in_array($_cat_id, [114])}
    {if $_is_en}
        {assign var=category_listing_banner_file value='black2weeks-categorie-garden-en.webp'}
    {else}
        {assign var=category_listing_banner_file value='black2weeks-categorie-garden.webp'}
    {/if}
{* Packs portail + portillon alu — Portail battant / coulissant (alu) *}
{elseif in_array($_cat_id, [169, 240, 278, 339, 337, 243, 279, 323])}
    {if $_is_en}
        {assign var=category_listing_banner_file value='black2weeks-categorie-packs-portail-en.webp'}
    {else}
        {assign var=category_listing_banner_file value='black2weeks-categorie-packs-portail.webp'}
    {/if}
{/if}

<div class="products{if !empty($cssClass)} {$cssClass}{/if} {if (isset($smarty.cookies.show_list)) && ($page.page_name == 'category' ||  $page.page_name == 'search')} list_view {/if}">
    {foreach from=$products item="product" key="position" name=productsList}
        {include file="catalog/_partials/miniatures/product.tpl" product=$product position=$position productClasses=$productClasses}

        {* Bannière Black2weeks (mobile + desktop) — uniquement sur catégories mappées *}
        {if !$pro.env && $category_listing_banner_file && $page.page_name == 'category' && isset($listing.pagination.items_shown_from) && $listing.pagination.items_shown_from == 1 && !isset($smarty.cookies.show_list)}
            {if $smarty.foreach.productsList.iteration == 1}
                <div class="category-listing-banner category-listing-banner--mobile col-6 col-sm-6 d-md-none">
                    <div class="category-listing-banner__inner">
                        <a href="{$link->getCMSLink(677)|escape:'html':'UTF-8'}">
                            <img class="img-fluid category-listing-banner__img" src="{$urls.theme_assets}img/{$category_listing_banner_file}" alt="{l s='BLACK2WEEKS' d='Shop.Theme.Custommodules'}" loading="lazy" width="400" height="711">
                        </a>
                    </div>
                </div>
            {/if}
            {* Desktop : après le 3e produit, ou après le dernier s'il y en a moins de 3 (ex. cat. 114) *}
            {if $smarty.foreach.productsList.iteration == 3 || ($smarty.foreach.productsList.last && $smarty.foreach.productsList.total < 3)}
                <div class="category-listing-banner category-listing-banner--desktop d-none d-lg-block col-lg-3 col-xl-3">
                    <div class="category-listing-banner__inner">
                        <a href="{$link->getCMSLink(677)|escape:'html':'UTF-8'}">
                            <img class="img-fluid category-listing-banner__img" src="{$urls.theme_assets}img/{$category_listing_banner_file}" alt="{l s='BLACK2WEEKS' d='Shop.Theme.Custommodules'}" loading="lazy" width="400" height="711">
                        </a>
                    </div>
                </div>
            {/if}
        {/if}
    {/foreach}
</div>
