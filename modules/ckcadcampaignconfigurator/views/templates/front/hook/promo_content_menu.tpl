{if isset($menuPromos)}
    {foreach $menuPromos as $menuPromo}
        {if isset($menuPromo['imgMenuPath'])}
            <a href="{$menuPromo['menuLink']}">
                <img src="{$modulePath}{$menuPromo['imgMenuPath']}" alt="Image mise en avant" loading="lazy">
            </a>
        {/if}
    {/foreach}
{/if}
