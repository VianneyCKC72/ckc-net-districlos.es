{block name='login_form_footer'}
    <footer class="form-footer text-sm-center clearfix">
        <input type="hidden" name="submitLogin" value="1">
        {block name='form_buttons'}
            {if $espace_pro == 1}<div class="cont-all-btn-login">{/if}
            <button id="submit-login" class="btn btn-primary btn-primary-pro" data-link-action="sign-in" type="submit"
                    class="form-control-submit">
                Se connecter
            </button>

            <div class="cont-btn-submit">
                <span class="green"><strong>100€ offert !</strong></span>
                <a href="/authentification?create_account=1&ep=1" class="btn btn-secondary"
                   id="inscription">Créer un compte</a>
            </div>
            </div>

        {/block}
    </footer>
{/block}