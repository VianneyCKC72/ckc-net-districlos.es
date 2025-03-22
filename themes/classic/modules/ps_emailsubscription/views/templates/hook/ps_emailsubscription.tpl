{if $page.page_name == 'category'}
    <div class="col-xs-12 only-category-block">
        {hook h='CETemplate' id=14}
    </div>
{/if}

{if $page.page_name == 'index'}
    <p style="margin-top: 70px; margin-bottom: 70px;">
        <iframe width="100%" height="100%" id="AV_widget_iframe" title="Avis client" frameborder="0"
                src="//cl.avis-verifies.com/fr/cache/0/8/4/084cf537-ed41-0a34-15e7-e45715e81af8/widget4/084cf537-ed41-0a34-15e7-e45715e81af8horizontal_index.html">
        </iframe>
    </p>
{/if}

{if $page.page_name != 'checkout'}
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
{/if}
