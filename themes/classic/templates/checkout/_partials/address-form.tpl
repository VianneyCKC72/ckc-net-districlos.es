{extends file='customer/_partials/address-form.tpl'}

{block name='form_field'}
  {if $field.name eq "alias" and $customer.is_guest}
    {* we don't ask for alias here if customer is not registered *}
  {else}
    {$smarty.block.parent}
  {/if}
{/block}

{block name="address_form_url"}
  <form method="POST" action="{url entity='order' params=['id_address' => $id_address]}" data-id-address="{$id_address}"
    data-refresh-url="{url entity='order' params=['ajax' => 1, 'action' => 'addressForm']}">
  {/block}

  {block name='form_fields' append}
    <input type="hidden" name="saveAddress" value="{$type}">
    {if $type === "delivery"}
      <div class="form-group row">
        <div class="col-md-9 col-md-offset-3">
          <span class="custom-checkbox">
            <label for="use_same_address">
              <input name="use_same_address" id="use_same_address" type="checkbox" value="1" {if $use_same_address} checked
                {/if}>
              <span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-check checkbox-checked">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              </span>
              {l s='Use this address for invoice too' d='Shop.Theme.Checkout'}
            </label>
          </span>
        </div>
      </div>
    {/if}
  {/block}

  {block name='form_buttons'}
    {if !$form_has_continue_button}
      <button type="submit" class="btn btn-primary">{l s='Save' d='Shop.Theme.Actions'}</button>
      <a class="js-cancel-address cancel-address"
        href="{url entity='order' params=['cancelAddress' => {$type}]}">{l s='Cancel' d='Shop.Theme.Actions'}</a>
    {else}
      <form>
        {if $customer.addresses|count > 0}
          <a class="js-cancel-address cancel-address btn btn-outline-primary"
            href="{url entity='order' params=['cancelAddress' => {$type}]}">{l s='Cancel' d='Shop.Theme.Actions'}</a>
        {/if}
        <button type="submit" class="continue btn btn-primary" name="confirm-addresses" value="1">
          {l s='Continue' d='Shop.Theme.Actions'}
        </button>
      </form>
    {/if}
{/block}