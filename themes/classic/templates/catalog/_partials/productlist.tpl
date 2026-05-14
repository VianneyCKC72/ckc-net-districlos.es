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

<div class="products{if !empty($cssClass)} {$cssClass}{/if} {if (isset($smarty.cookies.show_list)) && ($page.page_name == 'category' ||  $page.page_name == 'search')} list_view {/if}">
    {foreach from=$products item="product" key="position" name=productsList}
        {include file="catalog/_partials/miniatures/product.tpl" product=$product position=$position productClasses=$productClasses}

        {* Bannière catégorie : mobile = après le 1er produit (< md, 2 colonnes) ; desktop = après le 3e (>= lg, 4 colonnes). Première page grille uniquement. *}
        {if $page.page_name == 'category' && isset($listing.pagination.items_shown_from) && $listing.pagination.items_shown_from == 1 && !isset($smarty.cookies.show_list)}
            {if $smarty.foreach.productsList.iteration == 1}
                <div class="category-listing-banner category-listing-banner--mobile col-6 col-sm-6 d-md-none">
                    <div class="category-listing-banner__inner">
                        <img class="img-fluid category-listing-banner__img" src="{$urls.theme_assets}img/category-listing-promo.jpg" alt="" loading="lazy" width="400" height="400">
                    </div>
                </div>
            {/if}
            {if $smarty.foreach.productsList.iteration == 3}
                <div class="category-listing-banner category-listing-banner--desktop d-none d-lg-block col-lg-3 col-xl-3">
                    <div class="category-listing-banner__inner">
                        <img class="img-fluid category-listing-banner__img" src="{$urls.theme_assets}img/category-listing-promo.jpg" alt="" loading="lazy" width="400" height="400">
                    </div>
                </div>
            {/if}
        {/if}
    {/foreach}
</div>