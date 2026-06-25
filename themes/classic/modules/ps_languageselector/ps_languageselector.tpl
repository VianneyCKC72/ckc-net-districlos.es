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
{* Sélecteur de langue – mise en forme custom (sans drapeaux). Les classes
   fonctionnelles PrestaShop (dropdown / js-dropdown / btn-unstyle / dropdown-menu)
   sont conservées pour le JS d'ouverture du thème. *}
<div id="_desktop_language_selector" class="ckc-lang">
  <div class="language-selector-wrapper">
    <span id="language-selector-label" class="ckc-lang__label hidden-md-up">{l s='Language:' d='Shop.Theme.Global'}</span>
    <div class="language-selector dropdown js-dropdown">
      <button data-toggle="dropdown" class="ckc-lang__toggle btn-unstyle hidden-sm-down" aria-haspopup="true" aria-expanded="false" aria-label="{l s='Language dropdown' d='Shop.Theme.Global'}">
        <span class="ckc-lang__current">{$current_language.name_simple}</span>
        <svg class="ckc-lang__chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <ul class="dropdown-menu ckc-lang__menu hidden-sm-down" aria-labelledby="language-selector-label">
        {foreach from=$languages item=language}
          <li {if $language.id_lang == $current_language.id_lang}class="current"{/if}>
            <a href="{url entity='language' id=$language.id_lang}" class="dropdown-item ckc-lang__item" data-iso-code="{$language.iso_code}">{$language.name_simple}</a>
          </li>
        {/foreach}
      </ul>
      <select class="link ckc-lang__select hidden-md-up" aria-labelledby="language-selector-label">
        {foreach from=$languages item=language}
          <option value="{url entity='language' id=$language.id_lang}"{if $language.id_lang == $current_language.id_lang} selected="selected"{/if} data-iso-code="{$language.iso_code}">
            {$language.name_simple}
          </option>
        {/foreach}
      </select>
    </div>
  </div>
</div>

<style>
  .ckc-lang { --ckc-lang-accent: #15616d; }
  .ckc-lang .language-selector { position: relative; }

  .ckc-lang__toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #1a1a1a;
    font-size: 14px;
    font-weight: 600;
    line-height: 1;
    cursor: pointer;
    transition: background-color .15s ease, border-color .15s ease;
  }
  .ckc-lang__toggle:hover { background-color: #f3f4f6; }
  .ckc-lang__toggle:focus-visible { outline: 2px solid var(--ckc-lang-accent); outline-offset: 2px; }

  .ckc-lang__chevron { flex: 0 0 auto; transition: transform .2s ease; }
  .ckc-lang__toggle[aria-expanded="true"] .ckc-lang__chevron { transform: rotate(180deg); }

  .ckc-lang__menu.dropdown-menu {
    min-width: 168px;
    margin-top: 8px;
    padding: 6px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 10px 28px rgba(17, 24, 39, .12);
  }
  .ckc-lang__menu li { list-style: none; }

  .ckc-lang__item.dropdown-item {
    display: block;
    padding: 9px 12px;
    border-radius: 8px;
    color: #1a1a1a;
    font-size: 14px;
    white-space: nowrap;
    text-decoration: none;
    transition: background-color .12s ease, color .12s ease;
  }
  .ckc-lang__item.dropdown-item:hover { background-color: #f3f4f6; }

  .ckc-lang__menu li.current .ckc-lang__item {
    font-weight: 700;
    color: var(--ckc-lang-accent);
    background-color: rgba(21, 97, 109, .08);
  }

  .ckc-lang__select {
    padding: 6px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    color: #1a1a1a;
    font-size: 14px;
    font-weight: 600;
  }
</style>
