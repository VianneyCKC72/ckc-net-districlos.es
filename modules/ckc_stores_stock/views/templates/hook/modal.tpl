<div class="container demo">
    <div class="modal" tabindex="-1" role="dialog" id="modalSelectShop" style="z-index: 999999;">
        <div class="modal-dialog modal-dialog-select-shop" role="document" style="max-width: 30rem;">
            <div class="modal-content">
                <div class="modal-header" style="display: flex;
      justify-content: space-between; border-bottom:unset;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="z-index: 9999;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="min-height: 100vh;">
                    <h5 style="text-align: center;margin: 10px 0;">Choisissez votre magasin</h5>
                    <div class="panel" style="margin-bottom: 2rem;">
                        <div class="search-cp" style="display: flex;">
                            <input id="cp" name="cp" placeholder="Votre code postal" type="text" class="ui-autocomplete-input inputSelectShop" autocomplete="off" style="margin: auto;">
                        </div>
                        <span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
                    </div>
                    <p id="maCommune"></p>
                    <div class="shops">
                        {foreach $stores as $store}
                            <div id="{$store.code}" class="card m-3 agenceShop" style="border: 0;" data-zipcodes="{$store.zip_codes}">
                                <span style="border-top: 2px solid gray;height: 10px !important; width: 50%;display: block;"></span>
                                <div class="store-item-container clearfix">
                                    <div class="col-md-12">
                                        <div class="h3 card-title" style="font-size: 14px;text-align: left;text-decoration: underline;margin-bottom: 5px">{$store.name}</div>
                                        <p style="font-size: 14px;">Retrait disponible</p>
                                        <p style="color:#79c046;display: flex;font-weight: 200;font-family: Roboto Thin,sans-serif;" id="modalProductStock-{$store.id}"><svg fill="#79c046" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width: 20px;margin-right: 1rem;"><path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/></svg> {if $store.stock > 0 && $store.seuil && !$isKit}{$store.stock} en stock{elseif $store.stock <= 0 || !$store.seuil}Arrivage en cours{else}En stock{/if}</p>
                                        <address style="font-size: 14px;margin-bottom: 0;display: none;" id="address{$store.id}">
                                            {if $store.address}
                                                {$store.address nofilter}
                                            {/if}
                                        </address>
                                    </div>
                                    <div class="col-md-12 store-description" style="display: grid; grid-template-columns:1fr 1fr;gap: 5px;">
                                        <div>
                                            <button class="btn btn-primary btn-primary-select-shop mb-2 my-auto setStore shopId-{$store.id} selected-{$store.selected}" type="button" style="font-size: 10px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#fff" style="margin: 0 10px;height: 11px;">
                                                    <path d="M225.8 468.2l-2.5-2.3L48.1 303.2C17.4 274.7 0 234.7 0 192.8l0-3.3c0-70.4 50-130.8 119.2-144C158.6 37.9 198.9 47 231 69.6c9 6.4 17.4 13.8 25 22.3c4.2-4.8 8.7-9.2 13.5-13.3c3.7-3.2 7.5-6.2 11.5-9c0 0 0 0 0 0C313.1 47 353.4 37.9 392.8 45.4C462 58.6 512 119.1 512 189.5l0 3.3c0 41.9-17.4 81.9-48.1 110.4L288.7 465.9l-2.5 2.3c-8.2 7.6-19 11.9-30.2 11.9s-22-4.2-30.2-11.9zM239.1 145c-.4-.3-.7-.7-1-1.1l-17.8-20-.1-.1s0 0 0 0c-23.1-25.9-58-37.7-92-31.2C81.6 101.5 48 142.1 48 189.5l0 3.3c0 28.5 11.9 55.8 32.8 75.2L256 430.7 431.2 268c20.9-19.4 32.8-46.7 32.8-75.2l0-3.3c0-47.3-33.6-88-80.1-96.9c-34-6.5-69 5.4-92 31.2c0 0 0 0-.1 .1s0 0-.1 .1l-17.8 20c-.3 .4-.7 .7-1 1.1c-4.5 4.5-10.6 7-16.9 7s-12.4-2.5-16.9-7z"/>
                                                </svg>
                                                Définir comme favoris
                                            </button>
                                            <span class="yourShop btn btn-secondary" style="display: none;font-size: 10px;">
                                               <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#fff" style="margin: 0 10px;height: 11px;">
                                                   <path d="M225.8 468.2l-2.5-2.3L48.1 303.2C17.4 274.7 0 234.7 0 192.8l0-3.3c0-70.4 50-130.8 119.2-144C158.6 37.9 198.9 47 231 69.6c9 6.4 17.4 13.8 25 22.3c4.2-4.8 8.7-9.2 13.5-13.3c3.7-3.2 7.5-6.2 11.5-9c0 0 0 0 0 0C313.1 47 353.4 37.9 392.8 45.4C462 58.6 512 119.1 512 189.5l0 3.3c0 41.9-17.4 81.9-48.1 110.4L288.7 465.9l-2.5 2.3c-8.2 7.6-19 11.9-30.2 11.9s-22-4.2-30.2-11.9zM239.1 145c-.4-.3-.7-.7-1-1.1l-17.8-20-.1-.1s0 0 0 0c-23.1-25.9-58-37.7-92-31.2C81.6 101.5 48 142.1 48 189.5l0 3.3c0 28.5 11.9 55.8 32.8 75.2L256 430.7 431.2 268c20.9-19.4 32.8-46.7 32.8-75.2l0-3.3c0-47.3-33.6-88-80.1-96.9c-34-6.5-69 5.4-92 31.2c0 0 0 0-.1 .1s0 0-.1 .1l-17.8 20c-.3 .4-.7 .7-1 1.1c-4.5 4.5-10.6 7-16.9 7s-12.4-2.5-16.9-7z"/>
                                               </svg>
                                                Favoris
                                            </span>
                                        </div>
                                        <div>
                                            {if $store.address != false}
                                                <button class="btn btn-primary btn-coordonnes mb-2 my-auto" type="button" style="font-size: 10px;" data-address="{$store.id}">
                                                    COORDONNEES
                                                </button>
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
