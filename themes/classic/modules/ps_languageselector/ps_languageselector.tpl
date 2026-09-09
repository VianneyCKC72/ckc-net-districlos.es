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
{* Visible uniquement en preview (?ckc=1). Pas de liens EN exposés hors preview. *}
{if isset($ckc_lang_preview) && $ckc_lang_preview}
{* Sélecteur de langue – globe + code ISO (FR / EN). Classes fonctionnelles
   PrestaShop (dropdown / js-dropdown / btn-unstyle / dropdown-menu) conservées. *}
<div id="_desktop_language_selector" class="ckc-lang">
  <div class="language-selector-wrapper">
    <span id="language-selector-label" class="ckc-lang__label sr-only">{l s='Language:' d='Shop.Theme.Global'}</span>
    <div class="language-selector dropdown js-dropdown">
      <button data-toggle="dropdown" class="ckc-lang__toggle btn-unstyle hidden-sm-down" aria-haspopup="true" aria-expanded="false" aria-label="{l s='Language dropdown' d='Shop.Theme.Global'}">
        <svg class="ckc-lang__globe" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        <span class="ckc-lang__current">{$current_language.iso_code|upper}</span>
        <svg class="ckc-lang__chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <ul class="dropdown-menu ckc-lang__menu hidden-sm-down" aria-labelledby="language-selector-label">
        {foreach from=$languages item=language}
          {capture assign=lang_href}{url entity='language' id=$language.id_lang}{/capture}
          <li {if $language.id_lang == $current_language.id_lang}class="current"{/if}>
            <a href="{$lang_href}{if $lang_href|strstr:'?'}&{else}?{/if}ckc=1" class="dropdown-item ckc-lang__item" data-iso-code="{$language.iso_code}" rel="nofollow">{$language.iso_code|upper}</a>
          </li>
        {/foreach}
      </ul>
      <select class="link ckc-lang__select hidden-md-up" aria-labelledby="language-selector-label">
        {foreach from=$languages item=language}
          {capture assign=lang_href}{url entity='language' id=$language.id_lang}{/capture}
          <option value="{$lang_href}{if $lang_href|strstr:'?'}&{else}?{/if}ckc=1"{if $language.id_lang == $current_language.id_lang} selected="selected"{/if} data-iso-code="{$language.iso_code}">
            {$language.iso_code|upper}
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
    gap: 5px;
    padding: 4px 6px;
    border: 1px solid transparent;
    border-radius: 6px;
    background: transparent;
    color: #1a1a1a;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    line-height: 1;
    text-transform: uppercase;
    cursor: pointer;
    transition: background-color .15s ease, border-color .15s ease, color .15s ease;
  }
  .ckc-lang__toggle:hover {
    background-color: #f3f4f6;
    color: #111;
  }
  .ckc-lang__toggle:focus-visible {
    outline: 2px solid var(--ckc-lang-accent);
    outline-offset: 2px;
  }

  .ckc-lang__globe {
    flex: 0 0 auto;
    width: 20px;
    height: 20px;
    display: block;
  }

  .ckc-lang__current {
    line-height: 1;
  }

  .ckc-lang__chevron {
    flex: 0 0 auto;
    width: 12px;
    height: 12px;
    opacity: .45;
    transition: transform .2s ease, opacity .15s ease;
  }
  .ckc-lang__toggle:hover .ckc-lang__chevron { opacity: .7; }
  .ckc-lang__toggle[aria-expanded="true"] .ckc-lang__chevron {
    transform: rotate(180deg);
    opacity: .7;
  }

  .ckc-lang__menu.dropdown-menu {
    min-width: 72px;
    margin-top: 8px;
    padding: 4px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 10px 28px rgba(17, 24, 39, .12);
  }
  .ckc-lang__menu li { list-style: none; }

  .ckc-lang__item.dropdown-item {
    display: block;
    padding: 8px 12px;
    border-radius: 7px;
    color: #1a1a1a;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
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
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
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
</style>
{/if}
