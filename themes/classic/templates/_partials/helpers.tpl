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

{function renderLogo}
  <a href="{$urls.pages.index}" class="d-flex logos_container">
    {if isset($language.iso_code) && $language.iso_code == 'en'}
      <img
        class="logo img-fluid"
        src="/img/logo_multilingue.png"
        alt="{$shop.name}"
        loading="lazy"
        {if isset($shop.logo_details.width) && $shop.logo_details.width}width="{$shop.logo_details.width}"{/if}
        {if isset($shop.logo_details.height) && $shop.logo_details.height}height="{$shop.logo_details.height}"{/if}>
    {else}
      <img
        class="logo img-fluid"
        src="{$shop.logo_details.src}"
        alt="{$shop.name}"
        loading="lazy"
        width="{$shop.logo_details.width}"
        height="{$shop.logo_details.height}">
    {/if}
  </a>
{/function}
