{if $page.page_name == 'index' && !empty($pro) && $pro.env}
    {assign var='ce_home_en' value=(isset($language.iso_code) && $language.iso_code|lower|substr:0:2 == 'en')}
    {if !$pro.user.is_logged}
        {include file='module:ckc_espacepro/views/templates/hook/pro-home-login.tpl'
            login_action=$link->getModuleLink('ckc_espacepro','login')
            back=$urls.current_url}
        {if $ce_home_en}
            {hook h='CETemplate' id=63}
        {else}
            {hook h='CETemplate' id=37}
        {/if}
    {else}
        {if $ce_home_en}
            {hook h='CETemplate' id=62}
        {else}
            {hook h='CETemplate' id=36}
        {/if}
    {/if}
{/if}
