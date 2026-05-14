<div class="ckc-shipping-estimator-block ckc-shipping-estimator-block--ribbon">
    <span class="ckc-shipping-estimator-block__ribbon" aria-hidden="true">OFFRE LIMITÉE</span>
    <div class="ckc-shipping-estimator-product-block d-flex flex_cln align-items-center">
        <img class="ckc-shipping-estimator-product-block__icon" src="/modules/ckc_shipping_estimator/assets/img/camion.png" loading="lazy" alt="Icone livraison">
        <div class="small d-flex flex-column text-center ckc-shipping-estimator-product-block__text">
            Livraison à domicile
            {hook h='displayPromoImpactFranco'}
            {if in_array(294,Product::getProductCategories($product->id|intval)) or in_array(295,Product::getProductCategories($product->id|intval)) or in_array(293,Product::getProductCategories($product->id|intval))}
                <span class="thin">Sous 3 à 4 semaines.</span>

            {else}
                <span class="thin">Sous 10 jours.</span>

            {/if}
        </div>
    </div>
    <button type="button" class="d-block small text-black bg-transparent font-weight-semibold text-underline" id="showDelivery">Estimez vos frais de
        livraison {*<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                       fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                       stroke-linejoin="round" class="lucide lucide-move-right ml-2">
            <path d="M18 8L22 12L18 16" />
            <path d="M2 12H22" />
        </svg>*}
    </button>
</div>
