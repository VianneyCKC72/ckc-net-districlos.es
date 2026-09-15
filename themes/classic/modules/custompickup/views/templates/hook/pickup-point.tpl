{* Inclure Leaflet *}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

<style>
    #pickup-map {
        min-height: 300px;
        min-width: 100%;
        background: #e0e0e0;
    }
</style>

<div id="pickup-section-container"
     class="col-12"
     data-i18n-day-mon="{l s='Lundi' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-day-tue="{l s='Mardi' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-day-wed="{l s='Mercredi' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-day-thu="{l s='Jeudi' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-day-fri="{l s='Vendredi' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-day-sat="{l s='Samedi' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-day-sun="{l s='Dimanche' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-closed="{l s='Fermé' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-hours-error="{l s='Erreur de lecture des horaires' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-distance-exact="{l s='Se situe à %distance% km de chez vous' d='Shop.Theme.Customcheckout' js=1}"
     data-i18n-distance-approx="{l s='Se situe à environ %distance% km de chez vous' d='Shop.Theme.Customcheckout' js=1}">
    <div class="pickup_map_store_container">
        <div style="flex:1;padding-left: 10px;">
            {include file='module:custompickup/views/templates/hook/pickup-list.tpl'}
        </div>
        <div style="flex:1;">
            <div id="pickup-map" style="height: 300px; width: 100%;border: 3px solid #79c046;"></div>
        </div>
    </div>
</div>

<script>
    window.pickupStores = {$stores_json nofilter};
    window.custompickupMainCarrierId = {if isset($custompickup_main_carrier_id) && $custompickup_main_carrier_id}{$custompickup_main_carrier_id|intval}{else}0{/if};
    window.currentCartId = null;
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const cartIdFromUrl = urlParams.get('id_cart');
        if (cartIdFromUrl) {
            window.currentCartId = parseInt(cartIdFromUrl);
        }
    } catch (e) {
        console.log('CustomPickup: Impossible de récupérer le cartId depuis l\'URL');
    }
</script>
<script src="{$module_dir}views/js/pickup-map.js"></script>
