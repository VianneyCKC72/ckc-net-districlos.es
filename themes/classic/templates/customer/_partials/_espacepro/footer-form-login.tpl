{block name='login_form_footer'}
    <footer class="form-footer text-sm-center clearfix">
        <input type="hidden" name="submitLogin" value="1">
        {block name='form_buttons'}
            <div class="cont-all-btn-login">
                <button id="submit-login" class="btn btn-primary btn-primary-pro" data-link-action="sign-in"
                        type="submit"
                        class="form-control-submit">
                    {l s='Se connecter' d='Shop.Theme.Customcustomer'}
                </button>

                <div class="cont-btn-submit">
                    <span class="green"><strong>{l s='100€ offert !' d='Shop.Theme.Customcustomer'}</strong></span>
                    <a href="{$urls.pages.register}&ep=1" class="btn btn-secondary"
                       id="inscription">{l s='Créer un compte' d='Shop.Theme.Customcustomer'}</a>
                </div>
            </div>
        {/block}
    </footer>
{/block}