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
{* Sélecteur de langue – même disposition que les .ico du header (icône au-dessus, label en dessous). *}
<div id="_desktop_language_selector" class="ckc-lang ico">
  <div class="language-selector-wrapper">
    <span id="language-selector-label" class="ckc-lang__label sr-only">{l s='Language:' d='Shop.Theme.Global'}</span>
    <div class="language-selector dropdown js-dropdown">
      <button data-toggle="dropdown" class="ckc-lang__toggle btn-unstyle hidden-sm-down" aria-haspopup="true" aria-expanded="false" aria-label="{l s='Language dropdown' d='Shop.Theme.Global'}">
        <span class="ckc-lang__icon" aria-hidden="true">
          <svg class="ckc-lang__globe" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        </span>
        <span class="ckc-lang__label-row">
          <span class="ckc-lang__current">{$current_language.iso_code|upper}</span>
          <svg class="ckc-lang__chevron" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </span>
      </button>
      <ul class="dropdown-menu ckc-lang__menu hidden-sm-down" aria-labelledby="language-selector-label">
        {foreach from=$languages item=language}
          <li {if $language.id_lang == $current_language.id_lang}class="current"{/if}>
            <a href="{url entity='language' id=$language.id_lang}" class="dropdown-item ckc-lang__item" data-iso-code="{$language.iso_code}">{$language.iso_code|upper}</a>
          </li>
        {/foreach}
      </ul>
      <select class="link ckc-lang__select hidden-md-up" aria-labelledby="language-selector-label">
        {foreach from=$languages item=language}
          <option value="{url entity='language' id=$language.id_lang}"{if $language.id_lang == $current_language.id_lang} selected="selected"{/if} data-iso-code="{$language.iso_code}">
            {$language.iso_code|upper}
          </option>
        {/foreach}
      </select>
    </div>
  </div>
</div>

<style>
  .ckc-lang { --ckc-lang-accent: #15616d; }
  .ckc-lang .language-selector-wrapper,
  .ckc-lang .language-selector { position: relative; }

  .ckc-lang__toggle {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    gap: 3px;
    min-width: 0;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    color: #000;
    font-size: 10px;
    font-weight: 700;
    line-height: 15px;
    letter-spacing: 0;
    text-align: center;
    text-transform: uppercase;
    cursor: pointer;
    transition: opacity .15s ease;
  }
  .ckc-lang__toggle:hover { opacity: .75; }
  .ckc-lang__toggle:focus-visible {
    outline: 2px solid var(--ckc-lang-accent);
    outline-offset: 2px;
    border-radius: 4px;
  }

  .ckc-lang__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 21px;
    height: 21px;
    flex: 0 0 auto;
  }

  .ckc-lang__globe {
    display: block;
    width: 21px;
    height: 21px;
  }

  .ckc-lang__label-row {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    min-height: 15px;
    line-height: 15px;
  }

  .ckc-lang__current {
    line-height: 15px;
  }

  .ckc-lang__chevron {
    flex: 0 0 auto;
    width: 10px;
    height: 10px;
    margin-top: 1px;
    opacity: .4;
    transition: transform .2s ease, opacity .15s ease;
  }
  .ckc-lang__toggle:hover .ckc-lang__chevron { opacity: .65; }
  .ckc-lang__toggle[aria-expanded="true"] .ckc-lang__chevron {
    transform: rotate(180deg);
    opacity: .65;
  }

  .ckc-lang__menu.dropdown-menu {
    left: 50%;
    right: auto;
    min-width: 72px;
    margin-top: 6px;
    padding: 4px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 10px 28px rgba(17, 24, 39, .12);
    transform: translateX(-50%);
  }
  .ckc-lang__menu li { list-style: none; }

  .ckc-lang__item.dropdown-item {
    display: block;
    padding: 8px 12px;
    border-radius: 7px;
    color: #1a1a1a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0;
    text-align: center;
    text-transform: uppercase;
    white-space: nowrap;
    text-decoration: none;
    transition: background-color .12s ease, color .12s ease;
  }
  .ckc-lang__item.dropdown-item:hover { background-color: #f3f4f6; }

  .ckc-lang__menu li.current .ckc-lang__item {
    color: var(--ckc-lang-accent);
    background-color: rgba(21, 97, 109, .08);
  }

  .ckc-lang__select {
    padding: 6px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    color: #1a1a1a;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
  }

  .ckc-lang__label.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  @media only screen and (max-width: 1620px) {
    .ckc-lang__toggle { font-size: 9px; gap: 4px; }
  }
  @media only screen and (max-width: 1420px) {
    .ckc-lang__toggle { font-size: 8px; }
  }
</style>
