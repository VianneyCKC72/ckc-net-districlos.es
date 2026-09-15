{* Inclure Leaflet *}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

{* Ton style de carte *}
<style>
    #pickup-map {
        min-height: 300px;
        min-width: 100%;
        background: #e0e0e0;
    }
</style>

{* Layout *}
<div id="pickup-section-container" class="col-12">
    <div class="pickup_map_store_container">
        <div style="flex:1;padding-left: 10px;">
            {include file='module:custompickup/views/templates/hook/pickup-list.tpl'}
        </div>
        <div style="flex:1;">
            <div id="pickup-map" style="height: 300px; width: 100%;border: 3px solid #79c046;"></div>
        </div>
    </div>
</div>

{* Injection des données JS + inclusion du fichier JS *}
<script>
    window.pickupStores = {$stores_json nofilter};
    // Récupérer le cartId depuis l'URL ou une variable globale
    window.currentCartId = null;
    try {
        // Essayer de récupérer depuis l'URL
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
