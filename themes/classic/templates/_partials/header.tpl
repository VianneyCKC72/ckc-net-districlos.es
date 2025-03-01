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
                        <a class="link-danger" href="#">Destockage</a>
                    </div>

                    <div class="displayTop">
                        {hook h='displayTop'}
                        <a href="#" class="btn btn-primary btn-configurateur">
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
