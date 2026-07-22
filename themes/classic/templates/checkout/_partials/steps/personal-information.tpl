{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  {hook h='displayPersonalInformationTop' customer=$customer}

  {if $customer.is_logged && !$customer.is_guest}

    <div class="personal-info-logged">
      <div class="personal-info-logged__identity">
        <p class="identity">
          {* [1][/1] is for a HTML tag. *}
          {l s='Connected as [1]%firstname% %lastname%[/1].'
                        d='Shop.Theme.Customeraccount'
                        sprintf=[
                          '[1]' => "<a href='{$urls.pages.identity}'>",
          '[/1]' => "</a>",
          '%firstname%' => $customer.firstname,
          '%lastname%' => $customer.lastname
          ]
          }
        </p>
        <p class="identity-logout">
          {* [1][/1] is for a HTML tag. *}
          {l
                        s='Not you? [1]Log out[/1]'
                        d='Shop.Theme.Customeraccount'
                        sprintf=[
                        '[1]' => "<a href='{$urls.actions.logout}'>",
          '[/1]' => "</a>"
          ]
          }
        </p>
        {if !isset($empty_cart_on_logout) || $empty_cart_on_logout}
          <p><small>{l s='If you sign out now, your cart will be emptied.' d='Shop.Theme.Checkout'}</small></p>
        {/if}
      </div>

      <form class="personal-info-logged__action" method="GET" action="{$urls.pages.order}">
        <button class="continue btn btn-primary" name="controller" type="submit" value="order">
          Choisir mon adresse
        </button>
      </form>
    </div>

  {else}
    <div class="account-switch texte-left">
      {if $show_login_form}
        <p>
          Vous avez déjà un compte ?
          <a href="#checkout-guest-form" data-toggle="tab" role="tab" aria-controls="checkout-guest-form" aria-selected="false">
            Connectez-vous →
          </a>
        </p>
      {else}
        <p>
          Pas encore de compte ?
          <a href="#checkout-login-form" data-toggle="tab" role="tab" aria-controls="checkout-login-form" aria-selected="false">
            Créez un compte →
          </a>
        </p>
      {/if}
    </div>

    <div class="tab-content">
      <div class="tab-pane {if !$show_login_form}active{/if}" id="checkout-guest-form" role="tabpanel"
        {if $show_login_form}aria-hidden="true" {/if}>
        {render file='checkout/_partials/customer-form.tpl' ui=$register_form guest_allowed=$guest_allowed show_title=false}
      </div>
      <div class="tab-pane {if $show_login_form}active{/if}" id="checkout-login-form" role="tabpanel"
        {if !$show_login_form}aria-hidden="true" {/if}>
        {render file='checkout/_partials/login-form.tpl' ui=$login_form show_title=false}
      </div>
    </div>

  {/if}
{/block}
