{if $page.page_name == 'category'}
    <div class="col-xs-12 only-category-block">
        {if Context::getContext()->shop->id != 2}
            {hook h='CETemplate' id=14}
        {else}
            {hook h='CETemplate' id=40}
        {/if}
    </div>
{/if}

{*{if $page.page_name == 'index'}*}
{*    <p style="margin-top: 70px; margin-bottom: 70px;">*}
{*        <iframe width="100%" height="100%" id="AV_widget_iframe" title="Avis client" frameborder="0"*}
{*                src="//cl.avis-verifies.com/fr/cache/0/8/4/084cf537-ed41-0a34-15e7-e45715e81af8/widget4/084cf537-ed41-0a34-15e7-e45715e81af8horizontal_index.html">*}
{*        </iframe>*}
{*    </p>*}
{*{/if}*}

{if $page.page_name == 'module-opartdevis-simplequotation'}
    <div style="margin-bottom: 50px;">
        {hook h='CETemplate' id=15}
    </div>
{/if}

{if Context::getContext()->shop->id != 2 AND $page.page_name != 'checkout' AND $page.page_name != 'identity' AND $page.page_name != 'authentication' AND $page.page_name != 'registration'}
    <div class="banner__newsletter">
        <div class="col-text">
            {if $msg}
                <p class="notification newsletter-message {if $nw_error}notification-error{else}notification-success{/if}">{$msg}</p>
            {/if}
            <div class="h2">Newsletter</div>
            <p>Inscrivez-vous à la newsletter Districlos pour rester informés de nos nouveaux produits, bons plans et faire
                le
                plein d’inspirations !</p>
            <form class="form-newsletter" action="{$urls.current_url}#blockEmailSubscription_{$hookName}" method="post">
                <input type="hidden" name="blockHookName" value="{$hookName}" />

                <div class="input-newsletter">
                    <input
                            name="email"
                            class="email"
                            type="email"
                            value="{$value}"
                            aria-label="Email"
                            required
                    >
                    <button class="btn btn-primary-light d-md-block" name="submitNewsletter">Je m'inscris</button>
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
                <input type="hidden" name="action" value="0" />
            </form>
        </div>
        <div class="col-img">
            <img class="img-cover" src="/img/photo-portail-newsletter.png" alt="Banière newsletter" loading="lazy"/>
        </div>
    </div>
{elseif Context::getContext()->shop->id == 2 AND $page.page_name != 'index' AND $page.page_name != 'checkout' AND $page.page_name != 'identity' AND $page.page_name != 'authentication' AND $page.page_name != 'registration'}
    <div class="banner__newsletter">
        <div class="col-text">
            {if $msg}
                <p class="notification newsletter-message {if $nw_error}notification-error{else}notification-success{/if}">{$msg}</p>
            {/if}
            <div class="h2">Newsletter</div>
            <p>Inscrivez-vous à la newsletter Districlos pour rester informés de nos nouveaux produits, bons plans et faire
                le
                plein d’inspirations !</p>
            <form class="form-newsletter" action="{$urls.current_url}#blockEmailSubscription_{$hookName}" method="post">
                <input type="hidden" name="blockHookName" value="{$hookName}" />

                <div class="input-newsletter">
                    <input
                            name="email"
                            class="email"
                            type="email"
                            value="{$value}"
                            aria-label="Email"
                            required
                    >
                    <button class="btn btn-primary-light d-md-block" name="submitNewsletter">Je m'inscris</button>
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
                <input type="hidden" name="action" value="0" />
            </form>
        </div>
        <div class="col-img">
            <img class="img-cover" src="/img/photo-portail-newsletter.png" alt="Banière newsletter" loading="lazy"/>
        </div>
    </div>
    {if $page.page_name == 'category' OR $page.page_name == 'product'}
        <div>
            {hook h='CETemplate' id=39}
        </div>
    {/if}
{elseif $page.page_name == 'checkout'}
    <div>
        {hook h='CETemplate' id=15}
    </div>
{/if}
