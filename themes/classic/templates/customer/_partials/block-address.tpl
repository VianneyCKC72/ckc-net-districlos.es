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
{block name='address_block_item'}
  {if isset($default_address_id)}
    {if $address.id == $default_address_id}
      <article id="address-{$address.id}" class="address default-address-custom" data-id-address="{$address.id}">
        <div class="address-body">
          <div class="head-addresses">
            <h4>{$address.alias}</h4>
            <span class="badge badge-green">{l s='adresse par défaut' d='Shop.Theme.Customcustomer'}</span>
          </div>
          <address>
            {if $address.firstname || $address.lastname}
              <span class="label-name">{$address.firstname} {$address.lastname}</span><br>
            {/if}

            {if (isset($pro.env) && $pro.env == 1) && $address.company}
              <span class="label-company"><strong>{$address.company}</strong></span><br>
            {/if}
            <br>
            {if (isset($pro.env) && $pro.env == 1) && $address.vat_number}
              <span class="label-vat">{$address.vat_number}</span><br>
            {/if}

            {if $address.address1}
              <span class="label-address1">{$address.address1}</span><br>
            {/if}

            {if $address.address2}
              <span class="label-address2">{$address.address2}</span><br>
            {/if}

            {if $address.postcode || $address.city}
              <span class="label-postcode">{$address.postcode} {$address.city}</span><br>
            {/if}

            {if $address.country}
              <span class="label-country">{$address.country}</span><br>
            {/if}

            {if $address.phone}
              <span class="label-phone">{$address.phone}</span><br>
            {/if}
          </address>
          {* Display the extra field values added in an address from using hook 'additionalCustomerAddressFields' *}
          {hook h='displayAdditionalCustomerAddressFields' address=$address}
        </div>

        {block name='address_block_item_actions'}
          <div class="address-footer">
            <a href="{url entity=address id=$address.id}" data-link-action="edit-address">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
              <span>{l s='modifier' d='Shop.Theme.Customcustomer'}</span>
            </a>
            <a href="{url entity=address id=$address.id params=['delete' => 1, 'token' => $token]}" data-link-action="delete-address">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
              <span>{l s='supprimer' d='Shop.Theme.Customcustomer'}</span>
            </a>
          </div>
        {/block}
      </article>
    {else}
      <article id="address-{$address.id}" class="address" data-id-address="{$address.id}">
        <div class="address-body">
          <div class="head-addresses">
            <h4>{$address.alias}</h4>
          </div>
          <address>
            {if $address.firstname || $address.lastname}
              <span class="label-name">{$address.firstname} {$address.lastname}</span><br>
            {/if}

            {if (isset($pro.env) && $pro.env == 1) && $address.company}
              <span class="label-company"><strong>{$address.company}</strong></span><br>
            {/if}
            <br>
            {if (isset($pro.env) && $pro.env == 1) && $address.vat_number}
              <span class="label-vat">{$address.vat_number}</span><br>
            {/if}

            {if $address.address1}
              <span class="label-address1">{$address.address1}</span><br>
            {/if}

            {if $address.address2}
              <span class="label-address2">{$address.address2}</span><br>
            {/if}

            {if $address.postcode || $address.city}
              <span class="label-postcode">{$address.postcode} {$address.city}</span><br>
            {/if}

            {if $address.country}
              <span class="label-country">{$address.country}</span><br>
            {/if}

            {if $address.phone}
              <span class="label-phone">{$address.phone}</span><br>
            {/if}
          </address>
          {* Display the extra field values added in an address from using hook 'additionalCustomerAddressFields' *}
          {hook h='displayAdditionalCustomerAddressFields' address=$address}
        </div>

        {block name='address_block_item_actions'}
          <div class="address-footer">
            <div>
              <span class="badge badge-empty-green">{l s='définir par défaut' d='Shop.Theme.Customcustomer'}</span>
              <div type="hidden" id="addressId" style="display: none">{$address.id}</div>
            </div>
            <a href="{url entity=address id=$address.id}" data-link-action="edit-address">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
              <span>{l s='modifier' d='Shop.Theme.Customcustomer'}</span>
            </a>
            <a href="{url entity=address id=$address.id params=['delete' => 1, 'token' => $token]}" data-link-action="delete-address">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
              <span>{l s='supprimer' d='Shop.Theme.Customcustomer'}</span>
            </a>
          </div>
        {/block}
      </article>
    {/if}
  {else}
    <article id="address-{$address.id}" class="address" data-id-address="{$address.id}">
      <div class="address-body">
        <div class="head-addresses">
          <h4>{$address.alias}</h4>
        </div>
        <address>
          {if $address.firstname || $address.lastname}
            <span class="label-name">{$address.firstname} {$address.lastname}</span><br>
          {/if}

          {if (isset($pro.env) && $pro.env == 1) && $address.company}
            <span class="label-company"><strong>{$address.company}</strong></span><br>
          {/if}
          <br>
          {if (isset($pro.env) && $pro.env == 1) && $address.vat_number}
            <span class="label-vat">{$address.vat_number}</span><br>
          {/if}

          {if $address.address1}
            <span class="label-address1">{$address.address1}</span><br>
          {/if}

          {if $address.address2}
            <span class="label-address2">{$address.address2}</span><br>
          {/if}

          {if $address.postcode || $address.city}
            <span class="label-postcode">{$address.postcode} {$address.city}</span><br>
          {/if}

          {if $address.country}
            <span class="label-country">{$address.country}</span><br>
          {/if}

          {if $address.phone}
            <span class="label-phone">{$address.phone}</span><br>
          {/if}
        </address>
        {* Display the extra field values added in an address from using hook 'additionalCustomerAddressFields' *}
        {hook h='displayAdditionalCustomerAddressFields' address=$address}
      </div>

      {block name='address_block_item_actions'}
        <div class="address-footer">
          <div>
            <span class="badge badge-empty-green">{l s='définir par défaut' d='Shop.Theme.Customcustomer'}</span>
            <div type="hidden" id="addressId" style="display: none">{$address.id}</div>
          </div>
          <a href="{url entity=address id=$address.id}" data-link-action="edit-address">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
            <span>{l s='modifier' d='Shop.Theme.Customcustomer'}</span>
          </a>
          <a href="{url entity=address id=$address.id params=['delete' => 1, 'token' => $token]}" data-link-action="delete-address">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
            <span>{l s='supprimer' d='Shop.Theme.Customcustomer'}</span>
          </a>
        </div>
      {/block}
    </article>
  {/if}
{/block}
