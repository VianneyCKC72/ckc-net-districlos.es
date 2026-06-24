<div class="magasin-entete">
    <span><strong>{l s='Trouver mon magasin' d='Shop.Theme.Customstores'}</strong></span>
    <span>{l s='Pour connaître nos stocks et les délais de livraison, enregistrez votre magasin favori.' d='Shop.Theme.Customstores'}</span>
</div>

<div id="search_widget_magasin" class="search_widget_magasin">
    <form action="{$link->getPageLink('stores')}" method="get" class="store-search-form">
        <input type="text" name="search_query" placeholder="{l s='Ville, région, code postal...' d='Shop.Theme.Customstores'}" class="store-search-input" />
        <button class="recherche" type="submit">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 class="loupe-black lucide lucide-search-icon lucide-search">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
        </button>
    </form>

</div>

<div class="row nosmagasins">
    {include file='magasin.tpl'}
    {include file='magasin.tpl'}
    {include file='magasin.tpl'}
    {include file='magasin.tpl'}
    {include file='magasin.tpl'}
    {include file='magasin.tpl'}
    {include file='magasin.tpl'}
    {include file='magasin.tpl'}
    {include file='magasin.tpl'}

</div>