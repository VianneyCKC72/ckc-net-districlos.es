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
      {l s='Information' d='Shop.Theme.Customeraccount'}
    </span>
        </a>


        {if $customer.addresses|count}
            <a class="col-12 {if $currentUrl == $urls.pages.addresses } active {/if}" id="addresses-link" href="{$urls.pages.addresses}">
      <span class="link-item">
        {l s='Addresses' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {else}
            <a class="col-12 {if $currentUrl == $urls.pages.address } active {/if}" id="address-link" href="{$urls.pages.address}">
      <span class="link-item">
        {l s='Add first address' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {/if}

        {if !$configuration.is_catalog}
            <a class="col-12 {if $currentUrl == $urls.pages.history } active {/if}" id="history-link" href="{$urls.pages.history}">
      <span class="link-item">
        Mes commandes
      </span>
            </a>
        {/if}

        {if !$configuration.is_catalog}
            <a class="col-12 {if $currentUrl == $urls.pages.order_slip } active {/if}" id="order-slips-link" href="{$urls.pages.order_slip}">
      <span class="link-item">
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
        Mes données personnelles
      </span>
        </a>

        <a class="col-12  {if $currentUrl == {$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'} } active {/if}" id="returns-link"
           href="{$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'}">
    <span class="link-item link-item">
      Mes demandes de devis
    </span>
        </a>
        <a class="col-12" id="returns-link" href="/?mylogout=">
  <span class="link-item">
    Se déconnecter
  </span>
        </a>

    </div>
</div>