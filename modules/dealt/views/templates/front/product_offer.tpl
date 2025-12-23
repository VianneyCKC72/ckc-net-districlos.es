<div class="dealt-offer-box">
    <div class="dealt-offer-icon">
        <i class="material-icons">build</i>
    </div>
    <div class="dealt-offer-content">
        <div class="dealt-offer-title">
            {$dealt_service_title}
        </div>
        <div class="dealt-offer-price">
            + {$dealt_service_price}
            <a href="{$dealt_service_url}" class="dealt-conditions-link">Détails des conditions du service</a>
        </div>
        <div class="dealt-check">
            <label>Vérifier la disponibilité du service :</label>
            <div class="dealt-zipcode-check">
                <input type="text" placeholder="Code postal" id="dealt-zipcode-input" />
                <button id="dealt-zipcode-check-btn">
                    <i class="material-icons">search</i>
                </button>
            </div>
            <div id="dealt-zipcode-result"></div>
        </div>
    </div>
</div>
