<div class="col-md-3">
    {assign var="currentUrl" value="https://"|cat:$smarty.server.HTTP_HOST|cat:$smarty.server.REQUEST_URI}
    <div class="row links">
        <a class="col-12" id="identity-link" href="{$urls.pages.identity}">
    <span class="link-item {if $currentUrl == $urls.pages.identity } active {/if}">
      {l s='Information' d='Shop.Theme.Customeraccount'}
    </span>
        </a>


        {if $customer.addresses|count}
            <a class="col-12" id="addresses-link" href="{$urls.pages.addresses}">
      <span class="link-item {if $currentUrl == $urls.pages.addresses } active {/if}">
        {l s='Addresses' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {else}
            <a class="col-12" id="address-link" href="{$urls.pages.address}">
      <span class="link-item {if $currentUrl == $urls.pages.address } active {/if}">
        {l s='Add first address' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {/if}

        {if !$configuration.is_catalog}
            <a class="col-12" id="history-link" href="{$urls.pages.history}">
      <span class="link-item {if $currentUrl == $urls.pages.history } active {/if}">
        Mes commandes
      </span>
            </a>
        {/if}

        {if !$configuration.is_catalog}
            <a class="col-12" id="order-slips-link" href="{$urls.pages.order_slip}">
      <span class="link-item {if $currentUrl == $urls.pages.order_slip } active {/if}">
        {l s='Credit slips' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {/if}

        {if $configuration.voucher_enabled && !$configuration.is_catalog}
            <a class="col-12" id="discounts-link" href="{$urls.pages.discount}">
      <span class="link-item {if $currentUrl == $urls.pages.discount } active {/if}">
        {l s='Vouchers' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {/if}

        {if $configuration.return_enabled && !$configuration.is_catalog}
            <a class="col-12" id="returns-link" href="{$urls.pages.order_follow}">
      <span class="link-item {if $currentUrl == $urls.pages.order_follow } active {/if}">
        {l s='Merchandise returns' d='Shop.Theme.Customeraccount'}
      </span>
            </a>
        {/if}

        <a class="col-12" id="returns-link" href="/module/psgdpr/gdpr">
      <span class="link-item {if $currentUrl == 'https://mosaic.ckc-net.com/module/psgdpr/gdpr' } active {/if}">
        Mes données personnelles
      </span>
        </a>

        <a class="col-12" id="returns-link"
           href="{$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'}">
    <span class="link-item link-item {if $currentUrl == {$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'} } active {/if}">
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