<a href="{Context::getContext()->link->getCMSLink(359)}" title="Accéder à la page du magasin favori" class="card__account">
    <img height="{if isset($sidebar)} 25 {else} 74 {/if}" width="auto" src="themes/classic/assets/img/camion@2x.png" alt="">
    <span class="title_card">{if $favoriteStoreId == 11 } Magasins <br> <small>Aucun magasin favori</small> {else} Mon magasin {/if}</span>
</a>