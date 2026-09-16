{if $page.page_name == 'index' && !empty($pro) && $pro.env}
    <!-- ckc-pro-home iso={$pro.lang_iso|default:'?'} en={$pro.is_en|intval} logged={$pro.user.is_logged|intval} -->
    {if !$pro.user.is_logged}
        {include file='module:ckc_espacepro/views/templates/hook/pro-home-login.tpl'
            login_action=$link->getModuleLink('ckc_espacepro','login')
            back=$urls.current_url}
        {if $pro.is_en}
            {hook h='CETemplate' id=63}
        {else}
            {hook h='CETemplate' id=37}
        {/if}
    {else}
        {if $pro.is_en}
            {hook h='CETemplate' id=62}
        {else}
            {hook h='CETemplate' id=36}
        {/if}
    {/if}
{/if}
