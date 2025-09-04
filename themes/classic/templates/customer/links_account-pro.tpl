{* // mes devis *}
<a  href="{$link->getModuleLink('opartdevis', 'listquotation')|escape:'htmlall':'UTF-8'}">
Mes demandes de devis
</a>

{* // mon historique d'achat *}
{if !$configuration.is_catalog}
    <a  href="{$urls.pages.history}">
        Mon historique d'achat
    </a>
{/if}
{* // mes listes de projets *}
<a  href="#">
    Mes listes de projets
</a>

{* // mes addresses *}
{if $customer.addresses|count}
    <a  href="{$urls.pages.addresses}">
        {l s='Addresses' d='Shop.Theme.Customeraccount'}
    </a>
{else}
    <a   href="{$urls.pages.address}">
        Mon adresse
    </a>
{/if}
{* // mes avantages *}
<a  href="#">
    Mes avantages
</a>
{* // mes moyens de paiement *}
<a  href="#">
    Mes moyens de paiement
</a>
{* // mes realisations *}
<a  href="#">
    Mes réalisations
</a>
{* // mes notifications *}
<a  href="#">
    Mes notifications
</a>
