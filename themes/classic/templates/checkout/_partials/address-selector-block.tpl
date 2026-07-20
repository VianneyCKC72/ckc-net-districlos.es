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
{block name='address_selector_blocks'}
  {foreach $addresses as $address}
    <article class="js-address-item address-item{if $address.id == $selected} selected{/if}"
             id="{$name|classname}-address-{$address.id}">
      <label class="address-item__card radio-block">
        <span class="custom-radio">
          <input type="radio" name="{$name}" value="{$address.id}" {if $address.id == $selected}checked{/if}>
          <span></span>
        </span>
        <span class="address-item__body">
          <span class="address-alias">{$address.alias}</span>
          <span class="address">{$address.formatted nofilter}</span>
        </span>
      </label>
      {if $interactive}
        <footer class="address-footer">
          <a class="edit-address" data-link-action="edit-address"
             href="{url entity='order' params=['id_address' => $address.id, 'editAddress' => $type, 'token' => $token]}">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="lucide lucide-pencil" aria-hidden="true">
              <path
                      d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
              <path d="m15 5 4 4" />
            </svg>
            modifier
          </a>
          <a class="delete-address" data-link-action="delete-address"
             href="{url entity='order' params=['id_address' => $address.id, 'deleteAddress' => true, 'token' => $token]}">
            <svg width="9" height="14" viewBox="0 0 9 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path
                      d="M3.375 0.5L2.8125 1.15H0.5625C0.225 1.15 0 1.41 0 1.8C0 2.19 0.225 2.45 0.5625 2.45H1.6875H7.3125H8.4375C8.775 2.45 9 2.19 9 1.8C9 1.41 8.775 1.15 8.4375 1.15H6.1875L5.625 0.5H3.375ZM0.5625 3.75V12.2C0.5625 12.915 1.06875 13.5 1.6875 13.5H7.3125C7.93125 13.5 8.4375 12.915 8.4375 12.2V3.75H0.5625ZM2.8125 5.05C3.15 5.05 3.375 5.31 3.375 5.7V11.55C3.375 11.94 3.15 12.2 2.8125 12.2C2.475 12.2 2.25 11.94 2.25 11.55V5.7C2.25 5.31 2.475 5.05 2.8125 5.05ZM6.1875 5.05C6.525 5.05 6.75 5.31 6.75 5.7V11.55C6.75 11.94 6.525 12.2 6.1875 12.2C5.85 12.2 5.625 11.94 5.625 11.55V5.7C5.625 5.31 5.85 5.05 6.1875 5.05Z"
                      fill="currentColor"></path>
            </svg>
            supprimer
          </a>
        </footer>
      {/if}
    </article>
  {/foreach}
{/block}
