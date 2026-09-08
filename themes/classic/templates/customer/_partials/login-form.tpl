{block name='login_form'}

    {block name='login_form_errors'}
        {include file='_partials/form-errors.tpl' errors=$errors['']}
    {/block}

    <form id="login-form" action="{$action}" method="post">
        <input type="hidden" name="submitLogin" value="1">

        {if $pro.env == 0 && (!isset($show_title) || $show_title===false)}
            <div class="not-account-yet">
                <div class="img">
                    <picture>
                        <source srcset="/img/cms/image-connexion-particulier.webp" type="image/webp">
                        <img src="/img/cms/image-connexion-particulier.jpg" alt="connexion" />
                    </picture>
                </div>
                <div class="inside">
                    <h2 class="mb-4">{l s='Pas encore de compte ?' d='Shop.Theme.Customcustomer'}</h2>
                    <a href="{$urls.pages.register}" class="btn btn-secondary" id="inscription">
                        {l s='Créer un compte' d='Shop.Theme.Customcustomer'}
                    </a>
                </div>
            </div>
        {/if}


        <div class="inside-login-form my-4">
            {if $pro.env == 0 && (!isset($show_title) || $show_title===false)}
                {block name='page_title'}
                    <h1 class="h1 mb-4">{l s='Se connecter' d='Shop.Theme.Customcustomer'}</h1>
                {/block}
            {/if}

            {block name='login_form_fields'}
                {foreach from=$formFields item="field"}
                    {block name='form_field'}
                        {form_field field=$field}
                    {/block}
                {/foreach}
            {/block}
            {if $pro.env == 0}
                <div class="actions-container-login">
                    <button type="submit" class="btn btn-primary">
                        {l s='Se connecter' d='Shop.Theme.Actions'}
                    </button>
                    <div class="forgot-password">
                        <a href="{$urls.pages.password}" rel="nofollow">
                            {l s='Forgot your password?' d='Shop.Theme.Customeraccount'}
                        </a>
                    </div>
                    <p class="txt_ou_bien text-center">{l s='[1]Ou bien...[/1]' d='Shop.Theme.Customcustomer' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>']}</p>
                </div>
            {else}
                <div class="forgot-password">
                    <a href="{$urls.pages.password}" rel="nofollow">
                        {l s='Forgot your password?' d='Shop.Theme.Customeraccount'}
                    </a>
                </div>
            {/if}
        </div>

        {if $pro.env == 1}
            {include file="customer/_partials/_espacepro/login-form-espacepro.tpl"}
        {/if}

    </form>

{/block}

