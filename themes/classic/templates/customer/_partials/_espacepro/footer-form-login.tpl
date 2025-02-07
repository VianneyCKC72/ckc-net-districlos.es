{block name='login_form_footer'}
    <footer class="form-footer text-sm-center clearfix">
        <input type="hidden" name="submitLogin" value="1">
        {block name='form_buttons'}
            <div class="cont-all-btn-login">
                <button id="submit-login" class="btn btn-primary btn-primary-pro" data-link-action="sign-in"
                        type="submit"
                        class="form-control-submit">
                    Se connecter
                </button>

                <div class="cont-btn-submit">
                    <span class="green"><strong>100€ offert !</strong></span>
                    <a href="{$urls.pages.register}&ep=1" class="btn btn-secondary"
                       id="inscription">Créer un compte</a>
                </div>
            </div>
        {/block}
    </footer>
{/block}