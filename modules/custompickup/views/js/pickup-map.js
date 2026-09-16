document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('pickup-map').setView([46.5, 2.5], 5); // France par défaut
    window.pickupMap = map;

    const greenIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.3/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const markersByStoreId = {};

    if (typeof window.pickupStores !== 'undefined') {
        window.pickupStores.forEach(item => {
            const store = item.store;

            if (!store.latitude || !store.longitude || !store.id_store) return;

            const marker = L.marker([store.latitude, store.longitude], {
                icon: greenIcon
            }).addTo(map);

            marker.bindPopup(`<strong>${store.store_name}</strong>`);
            markersByStoreId[store.id_store] = marker;

            // ⬅️ Clic sur le marker → coche le radio correspondant
            marker.on('click', () => {
                const radio = document.querySelector(`input[name="selected_pickup"][value="${store.id_store}"]`);
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change')); // Pour déclencher comportement miroir
                }
            });
        });
    }

    // ➡️ Clic sur radio → ouvre popup sur la carte
    document.querySelectorAll('input[name="selected_pickup"]').forEach(input => {
        input.addEventListener('change', () => {
            const storeId = input.value;
            const marker = markersByStoreId[storeId];

            if (marker) {
                marker.openPopup();
                map.setView(marker.getLatLng(), 12);
            }
        });
    });

    // ➕ Sélectionne automatiquement le point recommandé au chargement
    const defaultRadio = document.querySelector('input[name="selected_pickup"]:checked');
    if (defaultRadio) {
        const marker = markersByStoreId[defaultRadio.value];
        if (marker) {
            marker.openPopup();
            map.setView(marker.getLatLng(), 12);
        }
    }
});
