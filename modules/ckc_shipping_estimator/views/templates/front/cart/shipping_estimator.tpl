{if Context::getContext()->shop->id != 2}
    <div class="d-flex align-items-center mb-2 mt-4">
        <img src="/modules/ckc_shipping_estimator/assets/img/delivery.svg" loading="lazy" alt="Icone livraison">
        <div class="small ml-4">
            Livraison à domicile  {hook h='displayPromoImpactFranco'}<br>
            <span class="thin">sous 10 jours</span>
        </div>
    </div>
{/if}
<a href="#estimate-shipping-cost" id="openModalShippingCost" class="d-block small text-black font-weight-bold">Estimez vos frais de livraison <svg
            xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-move-right ml-2">
        <path d="M18 8L22 12L18 16"></path>
        <path d="M2 12H22"></path>
    </svg>
</a>

<div id="modalShippingCost" class="modal-shipping">
    <div class="modal-shipping-content">
        <div class="close-shipping-btn" id="closeModalShippingBtn"><span>&times;</span></div>
        <div class="container">
            <h2>Estimez vos frais de livraison</h2>
            <p>Cette simulation prend en compte les produits dans votre panier (ex : produits avec plus-value de transport). Pour confirmer un
                choix de livraison, vous devez aller jusqu'à l'étape "transport" en appuyant sur le bouton "passer commande".</p>
            <div class="shippingCp">
                <div>Renseignez votre code postal</div>
                <div class="form-shipping">
                    <div class="d-flex gap-1">
                        <input type="text" class="form-control moduleZipcodeInput" placeholder="Ex : 75000" id="postalCodeInput" data-1p-ignore>
                        <button type="button" id="submitZipCode" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#fff" style="width: 16px;">
                                <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Conteneur des suggestions -->
                    {if file_exists($smarty.const._PS_MODULE_DIR_|cat:'zipcodeautocomplete/views/templates/front/zipcode_input.tpl')}
                        {include file="modules/zipcodeautocomplete/views/templates/front/zipcode_input.tpl"}
                    {/if}
                </div>
            </div>
        </div>
        <div class="deliveries-content">
            <table class="table deliveriesTable" style="display: none;">
                <thead>
                <tr>
                    <th class="text-center">Transporteur</th>
                    <th>Durée de livraison</th>
                    <th>Informations et délais moyens</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <p class="deliveryCostError">Il n'y a aucun choix de livraison pour le code postal renseigné</p>
        </div>
    </div>
</div>
