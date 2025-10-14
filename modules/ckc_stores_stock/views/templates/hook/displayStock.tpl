{if !in_array(294,Product::getProductCategories($product->id|intval)) and !in_array(295,Product::getProductCategories($product->id|intval)) and !in_array(293,Product::getProductCategories($product->id|intval)) and (!$isKit || ($isKit and $seuil))}
    <div>
        <div class="d-flex align-items-center mb-2 blockDeliveryDisplayStock">
            <img src="/modules/ckc_stores_stock/assets/img/delivery.png" loading="lazy" alt="Icone livraison">
            <div class="small ml-4">
                {if $fin_de_vie}
                    <span class="style_findevie" style="font-weight: bold;">Fin de série !</span><br>
                    <span class="font-weight-bold text-secondary stock-display" style="color: red !important;">Plus que {$total_quantity} produits en stock</span><br>
                    <span class="thin style_findevie_thin">Commandez vite, stock bas !</span>
                {else}
                    <span class="style_findevie">Stock en magasin</span><br>
                    <span class="font-weight-bold text-secondary stock-display">{$total_quantity} en stock national</span><br>
                    <span class="thin style_findevie_thin"></span>
                {/if}
            </div>
        </div>
        <button type="button" id="selectShop" class="d-block small text-black bg-transparent remove_findevie" {if $fin_de_vie}style="display: none;"{/if}>Voir le magasin le plus proche <svg
                        xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-move-right ml-2">
                    <path d="M18 8L22 12L18 16" />
                    <path d="M2 12H22" />
                </svg>
            </button>
    </div>
        
{/if}
