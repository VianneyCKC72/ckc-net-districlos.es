{if !in_array(294,Product::getProductCategories($product->id|intval)) and !in_array(295,Product::getProductCategories($product->id|intval)) and !in_array(293,Product::getProductCategories($product->id|intval)) and (!$isKit || ($isKit and $seuil))}
    <div>
        <div class="d-flex align-items-center mb-2">
            <img src="/modules/ckc_stores_stock/assets/img/delivery.svg" loading="lazy" alt="Icone livraison">
            <div class="small ml-4">
                Stock en magasin<br>
                {if $total_quantity == 0}
                    <span class="font-weight-bold text-secondary stock-display">Arrivage en cours</span>
                {else}
                    <span class="font-weight-bold text-secondary stock-display">{$total_quantity} en stock national</span>
                {/if}

            </div>
        </div>
        <button type="button" id="selectShop" class="d-block small text-black bg-transparent">Voir le magasin le plus proche <svg
                    xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-move-right ml-2">
                <path d="M18 8L22 12L18 16" />
                <path d="M2 12H22" />
            </svg>
        </button>
    </div>
{/if}
