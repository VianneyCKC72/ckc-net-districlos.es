<div class="banner__newsletter">
    <div class="col-text">
        <h2 class="h2">Newsletter</h2>
        <p>Inscrivez-vous à la newsletter Districlos pour rester informés de nos nouveaux produits, bons plans et faire
            le
            plein d’inspirations !</p>
        <form class="form-newsletter" action="{$urls.current_url}#blockEmailSubscription_{$hookName}" method="post">
            <div class="input-newsletter">
                <input
                        name="email"
                        class="email"
                        type="email"
                        value="{$value}"
                        aria-labelledby="block-newsletter-label"
                        required
                >
                <button class="btn btn-primary-light d-none d-md-block" name="submitNewsletter">Je m'inscris</button>
            </div>

            <label class="input-checkbox">
                J’accepte de recevoir d’autres communications de Districlos. Vous pourrez vous
                désabonner d’un simple clic !
                <input type="checkbox">
                <span class="checkmark"></span>
                {hook h='displayNewsletterRegistration'}
                {if isset($id_module)}
                    {hook h='displayGDPRConsent' id_module=$id_module}
                {/if}
            </label>

        </form>
    </div>
    <div class="col-img">
        <img class="img-cover" src="/themes/classic/assets/img/photo-portail-newsletter.jpg" alt=""/>
    </div>
</div>