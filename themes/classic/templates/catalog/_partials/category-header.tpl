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
<div id="js-product-list-header">
    {if $listing.pagination.items_shown_from == 1 && Context::getContext()->shop->id == 1}
        <div class="block-category card-block">
            {hook h='CETemplate' id=43}
            <div class="head-top-category">
                <h1 class="h1">{$category.name}</h1>
                <div class="has-discount d-flex align-items-center" style="flex-direction: column;gap: 5px;align-items: flex-start !important;">
                    {if $page.page_name == "category"}
                        {hook h='displayPromoCategoryDescription'}
                    {/if}
                    {*                    <div class="tag-discount mr-2">% SOLDES</div>
                                        <p class="small mb-0">100 € offerts par tranche de 1 000 € d’achat &amp; 150 € offerts par tranche
                                            de 3 000 €
                                            d’achat
                                        </p>*}
                </div>
            </div>
            <div class="block-category-inner">
                <div id="category-description">
                    <div class="category-description" id="description-content">
                        {if $category.description}
                            {$category.description nofilter}
                        {/if}
                    </div>
                    <button class="read-more-btn btn btn-primary" id="toggle-btn">{l s='En savoir plus' d='Shop.Theme.Customcatalog'}</button>
                    {include file='catalog/_partials/link-sub-desc.tpl'}
                </div>
            </div>

        </div>
    {/if}
    {if ($category.id == 3 OR $category.id == 2 OR $category.id == 128) && Context::getContext()->shop->id == 2}
        {hook h="displayCustomFilterSelector"}
    {/if}
</div>
