<div class="col-12">
    <h2 class="h2 mb-36">
        Mes raccourcis
    </h2>
</div>
<div class="{if isset($sidebar)}sidebar-raccourcis col-6{else}col-md-3 col-xs-12 mb-5{/if}">
    <a href="#" title="" class="card__account">
        <img height="{if isset($sidebar)} 25 {else} 74 {/if}" width="auto" src="themes/classic/assets/img/camion@2x.png" alt="">
        <span class="title_card">Mon magasin</span>
    </a>
</div>
<div class="{if isset($sidebar)}sidebar-raccourcis col-6{else}col-md-3 col-xs-12 mb-5{/if}">
    <a href="{$urls.pages.identity}" title="" class="card__account">
        <img height="{if isset($sidebar)} 25 {else} 89 {/if}" width="auto" src="themes/classic/assets/img/clef_molette@2x.png" alt="">
        <span class="title_card">Mes informations</span>
    </a>
</div>
<div class="{if isset($sidebar)}sidebar-raccourcis col-6{else}col-md-3 col-xs-12 mb-5{/if}">
    <a href="{$urls.pages.history}" title="" class="card__account">
        <img height="{if isset($sidebar)} 25 {else} 89 {/if}" width="auto" src="themes/classic/assets/img/entrepot@2x.png" alt="">
        <span class="title_card">Mes commandes</span>
    </a>
</div>
<div class="{if isset($sidebar)}sidebar-raccourcis col-6{else}col-md-3 col-xs-12 mb-5{/if}">
    <a href="{$urls.pages.history}" title="" class="card__account">
        <img height="{if isset($sidebar)} 25 {else} 99 {/if}" width="auto" src="themes/classic/assets/img/chariste@2x.png" alt="">
        <span class="title_card">Mes factures</span>
    </a>
</div>