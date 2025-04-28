{*{if $customer.isLogged()}*}
{*    {assign var="favorite_store" value=FavoriteStore::getFavoriteStore($customer->id)}*}
{*    {if $favorite_store}*}
{*        <p>Votre magasin favori : <strong>{$favorite_store}</strong></p>*}
{*        <button id="remove-favorite" data-id="{$favorite_store}">Supprimer</button>*}
{*    {else}*}
{*        <p>Aucun magasin favori sélectionné.</p>*}
{*    {/if}*}
{*{else}*}
{*    <p>Connectez-vous pour enregistrer votre magasin favori.</p>*}
{*{/if}*}
