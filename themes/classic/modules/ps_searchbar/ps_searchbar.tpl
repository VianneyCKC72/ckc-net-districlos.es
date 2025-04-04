{**
 * 2007-2020 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
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
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}

<!-- Bouton Loupe (Affiché seulement sur mobile < 767px) -->
<div id="search_icon" class="d-block d-md-none">
    <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search">
        <circle cx="11" cy="11" r="8"/>
        <path d="m21 21-4.3-4.3"/>
    </svg>
</div>

<!-- Barre de Recherche (Visible en permanence sur desktop, cachée sur mobile sauf ouverture) -->
<div id="search_widget" class="search-widgets" data-search-controller-url="{$search_controller_url}">
    <form method="get" action="{$search_controller_url}">
        <input type="hidden" name="controller" value="search">
        <input type="text" name="s" value="{$search_string}" placeholder="Je recherche..."
               aria-label="{l s='Search' d='Shop.Theme.Catalog'}">
        <!-- Bouton de fermeture pour mobile uniquement -->
        <i class="material-icons clear d-md-none" aria-hidden="true" id="close_search">clear</i>
    </form>
</div>

<!-- Overlay pour bloquer le scroll sur mobile uniquement -->
<div id="overlay" class="overlay d-none d-md-none"></div>
