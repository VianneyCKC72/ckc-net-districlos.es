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
            <a href="{$link->getCMSLink(656)|escape:'html':'UTF-8'}" class="dealt-conditions-link">{l s='Détails des conditions du service' d='Shop.Theme.Customdealt'}</a>
        </div>
        <div class="dealt-check">
            <label>{l s='Vérifier la disponibilité du service :' d='Shop.Theme.Customdealt'}</label>
            <div class="dealt-zipcode-check">
                <input type="text" placeholder="{l s='Code postal' d='Shop.Theme.Customdealt'}" id="dealt-zipcode-input" />
                <button id="dealt-zipcode-check-btn">
                    <i class="material-icons">search</i>
                </button>
            </div>
            <div id="dealt-zipcode-result"></div>
        </div>
    </div>
</div>
