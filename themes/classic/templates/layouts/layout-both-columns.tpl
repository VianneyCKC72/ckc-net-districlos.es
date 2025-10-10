{include file='_partials/helpers.tpl'}

<!doctype html>
<html lang="{$language.locale}">

<head>
    {block name='head'}
        {include file='_partials/head.tpl'}
    {/block}
</head>



<body id="{$page.page_name}" class="{$page.body_classes|classnames}
             {if ($page.page_name == 'authentication' && $pro.env) || $pro.env} espace_pro{/if}
             {if $page.page_name == 'registration' && $pro.env} registration-pro{/if}
             {$pro.suffix}">

    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T3HJWLD" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>

    {block name='hook_after_body_opening_tag'}
        {hook h='displayAfterBodyOpeningTag'}
    {/block}

    <main>
        {block name='product_activation'}
            {include file='catalog/_partials/product-activation.tpl'}
        {/block}

        <header id="header">
            {block name='header'}
                {include file="_partials/header{$pro.suffix}.tpl"}
            {/block}
        </header>

        <section id="wrapper">
            {block name='notifications'}
                {include file='_partials/notifications.tpl'}
            {/block}

            {hook h="displayWrapperTop"}
            <div class="container">
                {block name='breadcrumb'}
                    {include file='_partials/breadcrumb.tpl'}
                {/block}

                {if $page.page_name == 'index' && !empty($pro) && $pro.env && !$pro.user.is_logged}
                    {include file='module:ckc_espacepro/views/templates/hook/pro-home-login.tpl'
                                                        login_action=$link->getModuleLink('ckc_espacepro','login')
                                                        back=$urls.current_url}
                {/if}

                <div class="row">
                    {block name="left_column"}
                        <div id="left-column" class="col-xs-12 col-md-4 col-lg-3">
                            {if $page.page_name == 'product'}
                                {hook h='displayLeftColumnProduct' product=$product category=$category}
                            {else}
                                {hook h="displayLeftColumn"}
                            {/if}
                        </div>
                    {/block}

                    {block name="content_wrapper"}
                        <div id="content-wrapper" class="js-content-wrapper left-column right-column col-md-4 col-lg-3">
                            {hook h="displayContentWrapperTop"}
                            {block name="content"}
                                <p>Hello world! This is HTML5 Boilerplate.</p>
                            {/block}
                            {hook h="displayContentWrapperBottom"}
                        </div>
                    {/block}

                    {block name="right_column"}
                        <div id="right-column" class="col-xs-12 col-md-4 col-lg-3">
                            {if $page.page_name == 'product'}
                                {hook h='displayRightColumnProduct'}
                            {else}
                                {hook h="displayRightColumn"}
                            {/if}
                        </div>
                    {/block}
                </div>
            </div>
            {hook h="displayWrapperBottom"}
        </section>

        <div class="container">
            {block name='hook_footer_before'}
                {hook h='displayFooterBefore'}
            {/block}
        </div>

        <footer id="footer" class="js-footer">
            {block name="footer"}
                {include file="_partials/footer.tpl"}
            {/block}
        </footer>
    </main>

    {block name='javascript_bottom'}
        {include file="_partials/password-policy-template.tpl"}
        {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
        <script async type="text/javascript" src="/themes/classic/assets/js/lib/crypto-js.min.js"></script>
        <script async type="text/javascript" src="/themes/classic/assets/js/lib/jquery.inview.min.js"></script>
        <script async src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js"></script>
        <script async type="text/javascript" src="/themes/classic/assets/js/tags.js"></script>
        <script async type="module" src="/themes/classic/assets/js/lib/lite-vimeo.min.js"></script>
    {/block}

    {block name='hook_before_body_closing_tag'}
        {hook h='displayBeforeBodyClosingTag'}
    {/block}
</body>

</html>