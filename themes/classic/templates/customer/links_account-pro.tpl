{* // mes devis *}
<a  class="pro-items-links" href="{$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'}">
    {l s='Mes demandes de devis' d='Shop.Theme.Customcustomer'}
</a>

{* // mon historique d'achat *}
{if !$configuration.is_catalog}
    <a  class="pro-items-links"href="{$urls.pages.history}">
        {l s='Mon historique d\'achat' d='Shop.Theme.Customcustomer'}
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
        {l s='Mon adresse' d='Shop.Theme.Customcustomer'}
    </a>
{/if}
{* // Club Pro & avantages *}
<a class="pro-items-links" href="{$urls.pages.discount}">
    <span class="pro-items-links__star" aria-hidden="true">★</span>
    {l s='Club Pro & avantages' d='Shop.Theme.Customcustomer'}
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
<a class="pro-items-links" id="returns-link" href="{$link->getModuleLink('psgdpr', 'gdpr')|escape:'html':'UTF-8'}">
    {l s='Mes données personnelles' d='Shop.Theme.Customcustomer'}
</a>
{* // mes notifications *}
<a  class="pro-items-links" href="#">
    {l s='Mes notifications' d='Shop.Theme.Customcustomer'}
</a>
<a  class="pro-items-links" href="{$urls.actions.logout}">
   {l s='Déconnexion' d='Shop.Theme.Customcustomer'}
</a>