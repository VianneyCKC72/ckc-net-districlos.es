<script>
    if (typeof prestashop !== 'undefined') {
        prestashop.customer = prestashop.customer;
        if (prestashop.customer && prestashop.customer.is_logged) {
            {if $customer.id}
            prestashop.customer.id = {$customer.id};
            {/if}
            prestashop.customer.newClient = {$newClient};
        }
    }
</script>


{block name='header_top'}


    <div class="header-top">
        <div class="row-header d-flex align-items-center">

            <!-- Logo Desktop (Visible seulement à partir de md) -->
            <div class="d-none d-xl-block">
                {if ($page.page_name == 'authentication' || $page.page_name == 'registration') && $pro.env == 1}
                    <a href="/">
                        <img class="desktop_only" width="180" src="/img/DISTRICLOS_pro_logo.png" alt="{$shop.name} Pro" />
                    </a>
                {elseif $shop.logo_details}
                    {renderLogo}
                {/if}
            </div>


            <!-- Bouton menu (Visible uniquement sur Desktop) -->
            <div class="menu-button d-none d-md-block">
                {include file='_partials/btn-menu.tpl'}
            </div>

            <!-- Logo Mobile (Visible seulement sur xs et sm) -->
            <div class="d-block d-xl-none">
                <a href="{$urls.base_url}" class="d-flex">
                    <img class="logo img-fluid" src="/img/logo-mobile.svg" alt="Districlos" width="42" height="42" loading="lazy">
                    <div style="width: 1px; height: 50px; background-color: #ccc;margin-left: 10px;" class="desktop_only"></div>
                    <img  class="desktop_only" src="/img/Logo_Groupama_FDJ.svg" alt="Groupama-FDJ" style="margin-left: 10px;width: 180px;height: 47px;" />
                </a>
            </div>

            {block name='header_nav'}
                <nav class="header-nav d-flex align-items-center">
                    <div class="displayMenu">
                        <a class="link-danger" href="/400-destockage">
                            {*                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>*}
                            {*<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"></path></svg>
                            DESTOCKAGE</a>*}
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bell-fill" viewBox="0 0 16 16">  <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2m.995-14.901a1 1 0 1 0-1.99 0A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901"/></svg>
                            Black2Weeks</a>

                    </div>

                    <div class="displayTop">
                        {hook h='displayTop'}
                        <a href="{$link->getModuleLink('ckc_configurator', 'configurator')|escape:'htmlall':'UTF-8'}" class="btn btn-primary btn-configurateur">
                            <div class="text-btn">
                                <span class="small-text">
                                    Configurez<br>
                                </span>
                                <span class="medium-text">votre clôture</span>
                            </div>
                            <img width="37px" height="35px" alt="cloture" src="/img/menu-mobile-fence.svg">
                        </a>
                        <div class="block-question block-question-header">
                            <img loading="lazy" src="/img/menu-mobile-phone.svg" width="22" height="20" class="ico-question" alt="des question?">
                            <div>
                                Des questions ?
                                <a href="tel:0422531034"><strong>04 22 53 10 34</strong></a>
                            </div>
                        </div>
                    </div>

                    <div class="displayNav2">
                        {hook h='displayNav2'}
                    </div>
                </nav>
            {/block}

            <!-- Bouton menu (Visible uniquement sur Mobile) -->
            <div class="menu-button d-md-none">
                {include file='_partials/btn-menu.tpl'}
            </div>
        </div>

        <div id="mobile_top_menu_wrapper" class="row hidden-md-up" style="display:none;">
            <div class="js-top-menu mobile" id="_mobile_top_menu"></div>
            <div class="js-top-menu-bottom">
                <div id="_mobile_currency_selector"></div>
                <div id="_mobile_language_selector"></div>
                <div id="_mobile_contact_link"></div>
            </div>
        </div>
    </div>

    {hook h='displayNavFullWidth'}
{/block}
