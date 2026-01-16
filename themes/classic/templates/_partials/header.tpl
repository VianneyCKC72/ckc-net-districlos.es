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
                </a>
            </div>

            {block name='header_nav'}
                <nav class="header-nav d-flex align-items-center">
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
