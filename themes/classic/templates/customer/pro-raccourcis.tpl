<div class="col-12">
    <h2 class="h2 mb-36">
        {l s='Mes raccourcis' d='Shop.Theme.Customcustomer'}
    </h2>
</div>
<div class="block-raccourcis">
    <div class="item">
        <div class="{if isset($sidebar)}sidebar-raccourcis col-6{else}col-md-3 col-xs-12 mb-5{/if}">
            {hook h='displayFavoriteStore' sidebar=$sidebar}
        </div>
        <div class="{if isset($sidebar)}sidebar-raccourcis col-6{else}col-md-3 col-xs-12 mb-5{/if}">
            <a href="{$urls.pages.identity}" title="" class="card__account">
                <img height="{if isset($sidebar)} 25 {else} 89 {/if}" width="auto"
                     src="themes/classic/assets/img/clef_molette@2x.png" alt="">
                <span class="title_card">{l s='Mes informations' d='Shop.Theme.Customcustomer'}</span>
            </a>
        </div>
    </div>
    <div class="item">
        <div class="{if isset($sidebar)}sidebar-raccourcis col-6{else}col-md-3 col-xs-12 mb-5{/if}">
            <a href="{$urls.pages.history}" title="" class="card__account">
                <img height="{if isset($sidebar)} 25 {else} 89 {/if}" width="auto"
                     src="themes/classic/assets/img/entrepot@2x.png" alt="">
                <span class="title_card">{l s='Mes commandes' d='Shop.Theme.Customcustomer'}</span>
            </a>
        </div>
        <div class="{if isset($sidebar)}sidebar-raccourcis col-6{else}col-md-3 col-xs-12 mb-5{/if}">
            <a href="{$urls.pages.history}" title="" class="card__account">
                <img height="{if isset($sidebar)} 25 {else} 99 {/if}" width="auto"
                     src="themes/classic/assets/img/chariste@2x.png" alt="">
                <span class="title_card">{l s='Mes factures' d='Shop.Theme.Customcustomer'}</span>
            </a>
        </div>
    </div>
</div>