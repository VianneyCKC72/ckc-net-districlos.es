{block name='login_form'}

    {block name='login_form_errors'}
        {include file='_partials/form-errors.tpl' errors=$errors['']}
    {/block}

    <form id="login-form" action="{$action}" method="post">
        <input type="hidden" name="submitLogin" value="1">
        {if $espace_pro != true && (!isset($show_title) || $show_title)}
            <div class="not-account-yet">
                <div class="img">
                    <img src="https://picsum.photos/{rand(300, 600)}/{rand(300, 600)}" alt="" />
                </div>
                <div class="inside">
                    <h2 class="mb-4">Pas encore de compte ?</h2>
                    <a href="{$urls.pages.register}" class="btn btn-secondary" id="inscription">
                        Créer un compte
                    </a>
                </div>
            </div>
        {/if}

        <div class="inside-login-form my-4">
            {if $espace_pro != 1 && !isset($show_title) || $show_title}
                {block name='page_title'}
                    <h1 class="h1 mb-4">Se connecter</h1>
                {/block}
            {/if}

            {block name='login_form_fields'}
                {foreach from=$formFields item="field"}
                    {block name='form_field'}
                        {form_field field=$field}
                    {/block}
                {/foreach}
            {/block}

            <div class="actions-container-login">
                <button type="submit" class="btn btn-primary">
                    {l s='Se connecter' d='Shop.Theme.Actions'}
                </button>
                <div class="forgot-password">
                    <a href="{$urls.pages.password}" rel="nofollow">
                        {l s='Forgot your password?' d='Shop.Theme.Customeraccount'}
                    </a>
                </div>
            </div>
        </div>

        {if $espace_pro == 1}
            {include file="customer/_partials/_espacepro/login-form-espacepro.tpl"}
        {/if}

    </form>

{/block}

