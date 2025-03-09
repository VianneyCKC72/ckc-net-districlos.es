<div class="magasin-entete">
    <span><strong>Trouver mon magasin</strong></span>
    <span>Pour connaître nos stocks et les délais de livraison, enregistrez votre magasin favori.</span>
</div>

<div id="search_widget_magasin" class="search_widget_magasin">
    <form action="{$link->getPageLink('stores')}" method="get" class="store-search-form">
        <input type="text" name="search_query" placeholder="Ville, région, code postal..." class="store-search-input" />
        <button class="recherche">
            <img class="loupe-black" src="/themes/classic/assets/img/magasins/loupe-black.png" width="16px" height="16px" alt="recherche">
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