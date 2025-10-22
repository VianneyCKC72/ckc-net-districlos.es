{* // mes devis *}
<a  class="pro-items-links" href="{$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'}">
    Mes demandes de devis
</a>

{* // mon historique d'achat *}
{if !$configuration.is_catalog}
    <a  class="pro-items-links"href="{$urls.pages.history}">
        Mon historique d'achat
    </a>
{/if}
{* // mes listes de projets *}
{*<a  class="pro-items-links" href="#">*}
{*    Mes listes de projets*}
{*</a>*}

{* // mes addresses *}
{if $customer.addresses|count}
    <a  class="pro-items-links"href="{$urls.pages.addresses}">
        {l s='Addresses' d='Shop.Theme.Customeraccount'}
    </a>
{else}
    <a  class="pro-items-links" href="{$urls.pages.address}">
        Mon adresse
    </a>
{/if}
{* // mes avantages *}
<a  class="pro-items-links" href="{$urls.pages.discount}">
    Mes avantages
</a>

{if !$configuration.is_catalog}
    <a class="pro-items-links" id="order-slips-link" href="{$urls.pages.order_slip}">
        {l s='Credit slips' d='Shop.Theme.Customeraccount'}
    </a>
{/if}
{* // mes moyens de paiement *}
{*<a  class="pro-items-links" href="#">*}
{*    Mes moyens de paiement*}
{*</a>*}
{* // mes realisations *}
    <a class="pro-items-links"
       href="{$link->getModuleLink('ckc_realisation','myrealisation')|escape:'html'}">
        {l s='Mes réalisations' mod='ckc_realisation'}
    </a>

{* GRPD *}
<a class="pro-items-links" id="returns-link" href="/module/psgdpr/gdpr">
    Mes données personnelles
</a>
{* // mes notifications *}
<a  class="pro-items-links" href="#">
    Mes notifications
</a>
<a  class="pro-items-links" href="/?mylogout=">
   Déconnexion
</a>