{if isset($menuPromos)}
    {foreach $menuPromos as $menuPromo}
        {if isset($menuPromo['imgMenuPath'])}
            <a href="{$menuPromo['menuLink']}">
                <img src="{$modulePath}{$menuPromo['imgMenuPath']}" alt="">
            </a>
        {/if}
    {/foreach}
{/if}