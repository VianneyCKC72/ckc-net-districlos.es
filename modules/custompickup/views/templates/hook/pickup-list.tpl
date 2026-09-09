<div id="pickup-selected" class="pickup-main">
    <label>
        <strong>{$closest_store.store.store_name}</strong><br>

        {if isset($closest_store.distance_km)}
            <p class="pickup-distance">Se situe à {$closest_store.distance_km} km de chez vous</p>
        {/if}

        <p class="pickup-address"><img src="{$module_dir}views/img/icons/pickup-point.svg" alt="Point" width="16" height="16" class="icon-space">{$closest_store.store.address1} - {$closest_store.store.city}</p>

        {if $closest_store.store.phone}
            <p class="pickup-phone"><img src="{$module_dir}views/img/icons/pickup-phone.svg" alt="Téléphone" width="16" height="16" class="icon-space">
                {$closest_store.store.phone}</p>
        {/if}

        {if $closest_store.store.hours}
            <div class="opening-hours" data-hours='{$closest_store.store.hours|escape:'htmlall':'UTF-8'}'>
                <img src="{$module_dir}views/img/icons/pickup-clock.svg" alt="Clock" width="16" height="16" class="icon-space">
                <ul class="hours-list"></ul>
            </div>
        {/if}
    </label>
</div>

{if $other_stores|count > 0}
    <div id="pickup-list-container">
    <ul id="other-pickups" style="max-height: 300px; overflow-y: auto;">

        {* Le magasin par défaut *}
        <li>
            <label class="pickup-item">
                <span class="custom-radio pull-xs-left white">
                    <input type="radio" name="selected_pickup"
                           id="selected-pickup-id-{$closest_store.store.id_store}"
                           value="{$closest_store.store.id_store}"
                           data-carrier-id="{$closest_store.carrier.id_carrier}"
                           data-ckc-store-id="{$closest_store.store.id_ckc_store}"
                           data-store-name="{$closest_store.store.store_name}"
                           data-store-address="{$closest_store.store.address1}"
                           data-store-city="{$closest_store.store.city}"
                           data-carrier-name="{$closest_store.carrier.name}"
                           data-distance-km="{$closest_store.distance_km|escape:'htmlall':'UTF-8'}"
                           data-phone="{$closest_store.store.phone|escape:'htmlall':'UTF-8'}"
                           data-hours='{$closest_store.store.hours|escape:'htmlall':'UTF-8'}'
                           data-id-address="{$cart.id_address_delivery}"
                           checked>
                    <span></span>
                </span>
                <div class="pickup-text">
                <strong>{$closest_store.store.store_name}</strong>
                <p class="pickup-distance">Se situe à environ {$closest_store.distance_km} km de chez vous</p>
                </div>
            </label>
        </li>

        {foreach from=$other_stores item=item}
            <li>
                <label class="pickup-item">
                    <span class="custom-radio pull-xs-left white">
                        <input type="radio" name="selected_pickup"
                               id="selected-pickup-id-{$item.store.id_store}"
                               value="{$item.store.id_store}"
                               data-carrier-id="{$item.carrier.id_carrier}"
                               data-ckc-store-id="{$item.store.id_ckc_store}"
                               data-store-name="{$item.store.store_name}"
                               data-store-address="{$item.store.address1}"
                               data-store-city="{$item.store.city}"
                               data-carrier-name="{$item.carrier.name}"
                               data-distance-km="{$item.distance_km|escape:'htmlall':'UTF-8'}"
                               data-phone="{$item.store.phone|escape:'htmlall':'UTF-8'}"
                               data-hours='{$item.store.hours|escape:'htmlall':'UTF-8'}'
                               data-id-address="{$cart.id_address_delivery}">
                        <span></span>
                    </span>
                    <div class="pickup-text">
                        <strong>{$item.store.store_name}</strong>
                        {if isset($item.distance_km)}
                            <p class="pickup-distance">Se situe à environ {$item.distance_km} km de chez vous</p>
                        {else}
                            <p class="pickup-distance">Distance non calculable</p>
                        {/if}
                    </div>
                </label>
            </li>

        {/foreach}
    </ul>
    </div>
{/if}

<div id="pickup-links">
    {if $other_stores|count > 0}
        <a href="#" id="show-other-pickups" onclick="showOtherPickups(); return false;" class="clickShop">Voir les autres magasins</a>
    {/if}
</div>