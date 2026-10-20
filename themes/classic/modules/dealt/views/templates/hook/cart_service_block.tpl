<div class="dealt-cart-service">
    <h3>{l s='Services complémentaires' d='Shop.Theme.Customdealt'}</h3>

    <div class="dealt-cart-service-box">
        <div class="dealt-cart-service-header">
            <i class="material-icons">engineering</i>
            <strong>{$dealt_service_title}</strong>
            <span class="dealt-price">{$dealt_service_price}</span>
        </div>
        <div class="dealt-cart-service-body">
            <p>{l s='Faites le choix de la tranquillité et faites poser votre commande par un professionnel !' d='Shop.Theme.Customdealt'}</p>
            <ul>
                <li>{l s='Le terrain doit être plat' d='Shop.Theme.Customdealt'}</li>
                <li>{l s='Zone bien définie, bornée, propre' d='Shop.Theme.Customdealt'}</li>
                <li>{l s='Accès facile pour l’expert' d='Shop.Theme.Customdealt'}</li>
                <li>{l s='Ne comprend pas la dépose d\'ancien matériel' d='Shop.Theme.Customdealt'}</li>
            </ul>
            <a href="{$link->getCMSLink(656)|escape:'html':'UTF-8'}" target="_blank" class="btn btn-secondary btn-sm mt-2">{l s='Conditions de réalisation' d='Shop.Theme.Customdealt'}</a>
        </div>
        <div class="dealt-cart-service-footer">
            <button class="btn btn-primary dealt-add-service"
                    data-service-product-id="{$dealt_service_product_id}">
                {l s='Ajouter le service de montage' d='Shop.Theme.Customdealt'}
            </button>
        </div>
    </div>
</div>
