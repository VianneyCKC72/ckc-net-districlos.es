{if $pro.suffix == '-pro'}
    <div class="col-md-3 col-sm-12">
        <div id="nav_account">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="4" x2="20" y1="12" y2="12"></line><line x1="4" x2="20" y1="6" y2="6"></line><line x1="4" x2="20" y1="18" y2="18"></line></svg>
                <span>Menu</span>
            </div>
        </div>
        <div class="row links hide_mobile">
            {include file="customer/pro-raccourcis.tpl" sidebar=true}

            <h2 class="h2 mb-36 mt-5">
                Mes autres options
            </h2>
            {include file='customer/links_account-pro.tpl'}
        </div>

    </div>
{else}
    {include file='customer/links_account.tpl'}
{/if}