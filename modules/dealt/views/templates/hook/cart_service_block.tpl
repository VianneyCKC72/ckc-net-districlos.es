<div class="dealt-cart-service">
    <h3>Services complémentaires</h3>

    <div class="dealt-cart-service-box">
        <div class="dealt-cart-service-header">
            <i class="material-icons">engineering</i>
            <strong>{$dealt_service_title}</strong>
            <span class="dealt-price">{$dealt_service_price}</span>
        </div>
        <div class="dealt-cart-service-body">
            <p>Faites le choix de la tranquillité et faites poser votre commande par un professionnel !</p>
            <ul>
                <li>Le terrain doit être plat</li>
                <li>Zone bien définie, bornée, propre</li>
                <li>Accès facile pour l’expert</li>
                <li>Ne comprend pas la dépose d'ancien matériel</li>
            </ul>
            <a href="{$dealt_service_url}" target="_blank" class="btn btn-secondary btn-sm mt-2">Conditions de réalisation</a>
        </div>
        <div class="dealt-cart-service-footer">
            <button class="btn btn-primary dealt-add-service"
                    data-service-product-id="{$dealt_service_product_id}">
                Ajouter le service de montage
            </button>
        </div>
    </div>
</div>
