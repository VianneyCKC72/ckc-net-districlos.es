<div class="magasin-entete">
    <span><strong>Trouver mon magasin</strong></span>
    <span>Pour connaître nos stocks et les délais de livraison, enregistrez votre magasin favori.</span>
</div>

<div id="search_widget_magasin" class="search_widget_magasin store-search-form">
    <form action="{$modulePath}" method="get" class="store-search-form" onsubmit="return false;">
        <button type="button" class="geolocalisation" style="margin-left: 10px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-locate-fixed-icon lucide-locate-fixed"><line x1="2" x2="5" y1="12" y2="12"/><line x1="19" x2="22" y1="12" y2="12"/><line x1="12" x2="12" y1="2" y2="5"/><line x1="12" x2="12" y1="19" y2="22"/><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
        <input type="text" name="search_query" placeholder="Ville, région, code postal..."
               class="store-search-input"/>

        <button type="button" class="recherche" onclick="rechercherMagasins()">
            <img class="loupe-black" src="/themes/classic/assets/img/magasins/loupe-black.png" width="16px"
                 height="16px" alt="recherche">
        </button>
    </form>

    <div id="results"></div>
</div>

<div class="row nosmagasins">
    {foreach from=$elements item=magasin}
        {include file="module:ckcstorespresentation/views/templates/front/magasin.tpl"}
    {/foreach}
</div>
