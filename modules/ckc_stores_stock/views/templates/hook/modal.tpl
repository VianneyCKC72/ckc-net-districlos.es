<div id="modalSelectShop" class="ckc-shop-drawer" aria-hidden="true">
  <div class="ckc-shop-drawer__overlay" data-ckc-shop-drawer-close="1"></div>

  <aside class="ckc-shop-drawer__panel" role="dialog" aria-modal="true" aria-label="Choisissez votre magasin">
    <div class="ckc-shop-drawer__header">
      <div class="ckc-shop-drawer__header-top">
        <h5 class="ckc-shop-drawer__title">Choisissez votre magasin</h5>
        <button type="button" class="ckc-shop-drawer__close" data-ckc-shop-drawer-close="1" aria-label="Fermer">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="panel ckc-shop-drawer__cp-panel">
        <div class="search-cp ckc-cp-search ckc-cp-search--row">
          <span class="ckc-cp-search__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.3-4.3"></path>
            </svg>
          </span>
          <input id="cp" name="cp" placeholder="Votre code postal" type="text" class="ui-autocomplete-input inputSelectShop" autocomplete="off" data-1p-ignore>
        </div>
        <span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
      </div>
    </div>

    <div class="ckc-shop-drawer__body">
      <span class="ckc-shop-drawer__stores-title">Liste des magasins</span>
      <p id="maCommune"></p>
      <div class="shops">
        {foreach $stores as $store}
          <div id="{$store.code}" class="card m-3 agenceShop ckc-shop-card" data-zipcodes="{$store.zip_codes}">
            <div class="store-item-container clearfix">
              <div class="col-md-12">
                <div class="h3 card-title ckc-shop-card__title">{$store.name}</div>
                {if $store.id == 18}
                  <p class="ckc-shop-card__hint">Ce produit est disponible sur notre plateforme logistique, pour une meilleure disponibilité.</p>
                {elseif $store.id == 19}
                  <p class="ckc-shop-card__hint">Point relais à Avrainville.</br>Retrait possible en moyenne en 5 jours ouvrés.</p>
                {else}
                  <p class="ckc-shop-card__hint">Retrait disponible{if $store.ville} à {$store.ville}{/if}</p>
                {/if}
                <p class="ckc-modal-product-stock ckc-shop-card__stock" id="modalProductStock-{$store.id}"><svg fill="#79c046" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="ckc-shop-card__stock-icon"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg> {if $store.stock > 0 && $store.seuil && !$isKit}{$store.stock} en stock{elseif $store.stock <= 0 || !$store.seuil}Arrivage en cours{else}En stock{/if}</p>
                <address class="ckc-shop-card__address" id="address{$store.id}">
                  {if $store.address}
                    {$store.address nofilter}
                  {/if}
                </address>
              </div>
              <div class="col-md-12 store-description ckc-shop-card__actions">
                {if $store.id != 18}
                  <div>
                    <button class="btn btn-primary btn-primary-select-shop mb-2 my-auto setStore shopId-{$store.id} selected-{$store.selected} ckc-shop-card__btn" type="button">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#fff" style="margin: 0 10px;height: 11px;">
                        <path d="M225.8 468.2l-2.5-2.3L48.1 303.2C17.4 274.7 0 234.7 0 192.8l0-3.3c0-70.4 50-130.8 119.2-144C158.6 37.9 198.9 47 231 69.6c9 6.4 17.4 13.8 25 22.3c4.2-4.8 8.7-9.2 13.5-13.3c3.7-3.2 7.5-6.2 11.5-9c0 0 0 0 0 0C313.1 47 353.4 37.9 392.8 45.4C462 58.6 512 119.1 512 189.5l0 3.3c0 41.9-17.4 81.9-48.1 110.4L288.7 465.9l-2.5 2.3c-8.2 7.6-19 11.9-30.2 11.9s-22-4.2-30.2-11.9zM239.1 145c-.4-.3-.7-.7-1-1.1l-17.8-20-.1-.1s0 0 0 0c-23.1-25.9-58-37.7-92-31.2C81.6 101.5 48 142.1 48 189.5l0 3.3c0 28.5 11.9 55.8 32.8 75.2L256 430.7 431.2 268c20.9-19.4 32.8-46.7 32.8-75.2l0-3.3c0-47.3-33.6-88-80.1-96.9c-34-6.5-69 5.4-92 31.2c0 0 0 0-.1 .1s0 0-.1 .1l-17.8 20c-.3 .4-.7 .7-1 1.1c-4.5 4.5-10.6 7-16.9 7s-12.4-2.5-16.9-7z"/>
                      </svg>
                      Définir comme favoris
                    </button>
                    <span class="yourShop btn btn-secondary ckc-shop-card__btn ckc-shop-card__btn--hidden">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#fff" style="margin: 0 10px;height: 11px;">
                        <path d="M225.8 468.2l-2.5-2.3L48.1 303.2C17.4 274.7 0 234.7 0 192.8l0-3.3c0-70.4 50-130.8 119.2-144C158.6 37.9 198.9 47 231 69.6c9 6.4 17.4 13.8 25 22.3c4.2-4.8 8.7-9.2 13.5-13.3c3.7-3.2 7.5-6.2 11.5-9c0 0 0 0 0 0C313.1 47 353.4 37.9 392.8 45.4C462 58.6 512 119.1 512 189.5l0 3.3c0 41.9-17.4 81.9-48.1 110.4L288.7 465.9l-2.5 2.3c-8.2 7.6-19 11.9-30.2 11.9s-22-4.2-30.2-11.9zM239.1 145c-.4-.3-.7-.7-1-1.1l-17.8-20-.1-.1s0 0 0 0c-23.1-25.9-58-37.7-92-31.2C81.6 101.5 48 142.1 48 189.5l0 3.3c0 28.5 11.9 55.8 32.8 75.2L256 430.7 431.2 268c20.9-19.4 32.8-46.7 32.8-75.2l0-3.3c0-47.3-33.6-88-80.1-96.9c-34-6.5-69 5.4-92 31.2c0 0 0 0-.1 .1s0 0-.1 .1l-17.8 20c-.3 .4-.7 .7-1 1.1c-4.5 4.5-10.6 7-16.9 7s-12.4-2.5-16.9-7z"/>
                      </svg>
                      Favoris
                    </span>
                  </div>
                  <div>
                    {if $store.address != false}
                      <button class="btn btn-primary btn-coordonnes mb-2 my-auto ckc-shop-card__btn" type="button" data-address="{$store.id}">
                        COORDONNEES
                      </button>
                    {/if}
                  </div>
                {/if}
              </div>
            </div>
          </div>
        {/foreach}
      </div>
    </div>
  </aside>
</div>
