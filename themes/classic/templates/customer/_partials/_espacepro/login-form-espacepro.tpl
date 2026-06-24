{block name='login_form_footer'}
    <footer class="form-footer text-sm-center clearfix">
        <input type="hidden" name="submitLogin" value="1">
        {block name='form_buttons'}
            {if $pro.env == 1}<div class="cont-all-btn-login">{/if}
            <button id="submit-login" class="btn btn-primary btn-primary-pro" data-link-action="sign-in" type="submit"
                    class="form-control-submit">
                {l s='Se connecter' d='Shop.Theme.Customcustomer'}
            </button>

            <div class="cont-btn-submit">
                <span class="green">{l s='[1]100€ offert ![/1]' sprintf=['[1]' => '<strong>', '[/1]' => '</strong>'] d='Shop.Theme.Customcustomer'}</span>
                <a href="/inscription?create_account=1&ep=1" class="btn btn-secondary"
                   id="inscription">{l s='Créer un compte' d='Shop.Theme.Customcustomer'}</a>
            </div>
            </div>

        {/block}
    </footer>
{/block}