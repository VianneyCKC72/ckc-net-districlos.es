{block name='header_top'}
    <div class="header-top">
        <div class="row-header d-flex align-items-center justify-content-between">

            <!-- Logo Desktop (Visible seulement à partir de md) -->
            <div class="d-none d-md-block">
                {if $shop.logo_details}
                    {renderLogo}
                {/if}
            </div>

            <!-- Bouton menu (Visible uniquement sur Desktop) -->
            <div class="menu-button d-none d-md-block">
                {include file='_partials/btn-menu.tpl'}
            </div>

            <!-- Logo Mobile (Visible seulement sur xs et sm) -->
            <div class="d-block d-md-none">
                <img class="logo img-fluid" src="/img/logo-mobile.png" alt="Districlos" width="42" height="42">
            </div>

            {block name='header_nav'}
                <nav class="header-nav d-flex align-items-center">
                    <div class="displayMenu">
                        <a class="link-danger" href="/365-premiers-prix">
{*                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>*}
                            Premiers prix</a>
                    </div>

                    <div class="displayTop">
                        {hook h='displayTop'}
                        <a href="/configurateur" class="btn btn-primary btn-configurateur">
                            <div class="text-btn">
                                <span class="small-text">
                                    Configurez<br>
                                </span>
                                <span class="medium-text">votre clôture</span>
                            </div>
                            <img width="29px" height="26px" alt="cloture" src="/img/ico-cloture.png">
                        </a>
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
