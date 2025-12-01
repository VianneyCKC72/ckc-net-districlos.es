{block name='header_top'}
    <div class="header-top">
        <div class="row-header d-flex align-items-center">


            <!-- Logo Desktop (Visible seulement à partir de md) -->
            <div class="flex-grow-0 flex-shrink-0 d-none d-md-block">
                <a href="{$pro.url_domain}">
                    <img class="" width="180" src="/themes/classic/assets/img/logo-pro.png" alt="{$shop.name} Pro" />
                </a>
            </div>


            <!-- Bouton menu (Visible uniquement sur Desktop) -->
            <div class="menu-button d-none d-md-block flex-grow-0 flex-shrink-0">
                {include file='_partials/btn-menu.tpl'}
            </div>

            <!-- Logo Mobile (Visible seulement sur xs et sm) -->
            <div class="d-block d-xl-none flex-grow-0 flex-shrink-0">
                <a href="{$urls.base_url}" class="d-flex">
                    <img class="logo img-fluid" src="/img/logo-mobile-pro.svg" alt="Districlos Pro" width="42" height="42"
                         loading="lazy">
                    <div style="width: 1px; height: 50px; background-color: #ccc;margin-left: 10px;" class="desktop_only">
                    </div>
                    {*                    <img class="desktop_only" src="/img/Logo_Groupama_FDJ.svg" alt="Groupama-FDJ"
                                            style="margin-left: 10px;width: 180px;height: 47px;" />*}
                </a>
            </div>

            {block name='header_nav'}
                <nav class="header-nav d-flex align-items-center">
                    <div class="displayMenu">
                        <a class="link-danger" href="/content/660-les-offres-au-volume">Les offres au volume</a>
                    </div>

                    <div class="displayTop">
                        {hook h='displayTop'}
                        <a href="/avantages-pro" class="btn btn-primary d-none d-md-block">
                            <div class="fs-6 font-weight-bold">
                                Mes avantages
                            </div>
                        </a>
                        <div class="block-question block-question-header">
                            <img loading="lazy" src="/img/menu-mobile-phone.svg" width="22" height="20" class="ico-question"
                                 alt="des question?">
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