<div class="col-md-3">
    {assign var="currentUrl" value="https://"|cat:$smarty.server.HTTP_HOST|cat:$smarty.server.REQUEST_URI}
    {if $page.page_name != 'my-account'}
        <div id="nav_account">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
            <div>
                <span>Menu</span>
            </div>
        </div>
    {/if}

    <div class="row links  {if  $page.page_name != 'my-account'} hide_mobile{/if}">

        <a class="col-12 {if $currentUrl == $urls.pages.identity } active {/if}" id="identity-link" href="{$urls.pages.identity}">
    <span class="link-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>

      {l s='Information' d='Shop.Theme.Customeraccount'}
    </span>
        </a>


        {if $customer.addresses|count}
            <a class="col-12 {if $currentUrl == $urls.pages.addresses } active {/if}" id="addresses-link" href="{$urls.pages.addresses}">
                <span class="link-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-user"><path d="M15 13a3 3 0 1 0-6 0"/><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/><circle cx="12" cy="8" r="2"/></svg>
                    {l s='Addresses' d='Shop.Theme.Customeraccount'}
                </span>
            </a>
        {else}
            <a class="col-12 {if $currentUrl == $urls.pages.address } active {/if}" id="address-link" href="{$urls.pages.address}">
                <span class="link-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-user"><path d="M15 13a3 3 0 1 0-6 0"/><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/><circle cx="12" cy="8" r="2"/></svg>
                    Mon adresse
                </span>
            </a>
        {/if}

        {if !$configuration.is_catalog}
            <a class="col-12 {if $currentUrl == $urls.pages.history } active {/if}" id="history-link" href="{$urls.pages.history}">
      <span class="link-item">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>

        Mes commandes
      </span>
            </a>
        {/if}

        {if !$configuration.is_catalog}
            <a class="col-12 {if $currentUrl == $urls.pages.order_slip } active {/if}" id="order-slips-link" href="{$urls.pages.order_slip}">
      <span class="link-item">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-receipt-text"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/></svg>

        {l s='Credit slips' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {/if}

        {if $configuration.voucher_enabled && !$configuration.is_catalog}
            <a class="col-12 {if $currentUrl == $urls.pages.discount } active {/if}" id="discounts-link" href="{$urls.pages.discount}">
      <span class="link-item">
        {l s='Vouchers' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {/if}

        {if $configuration.return_enabled && !$configuration.is_catalog}
            <a class="col-12 {if $currentUrl == $urls.pages.order_follow } active {/if}" id="returns-link" href="{$urls.pages.order_follow}">
      <span class="link-item">
        {l s='Merchandise returns' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {/if}

        <a class="col-12 {if $currentUrl == 'https://districlos.ckc-net.com/module/psgdpr/gdpr' }active {/if}" id="returns-link" href="/module/psgdpr/gdpr">
      <span class="link-item">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-user"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M6.376 18.91a6 6 0 0 1 11.249.003"/><circle cx="12" cy="11" r="4"/></svg>
        Mes données personnelles
      </span>
        </a>

        <a class="col-12  {if $currentUrl == {$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'} } active {/if}" id="returns-link"
           href="{$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'}">
    <span class="link-item link-item">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-laptop-minimal-check"><path d="M2 20h20"/><path d="m9 10 2 2 4-4"/><rect x="3" y="4" width="18" height="12" rx="2"/></svg>
      Mes demandes de devis
    </span>
        </a>
        <a class="col-12" id="returns-link" href="/?mylogout=">
  <span class="link-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
    Se déconnecter
  </span>
        </a>

    </div>
</div>