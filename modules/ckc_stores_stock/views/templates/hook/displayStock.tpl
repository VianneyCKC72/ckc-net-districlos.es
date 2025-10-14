{if !in_array(294,Product::getProductCategories($product->id|intval)) and !in_array(295,Product::getProductCategories($product->id|intval)) and !in_array(293,Product::getProductCategories($product->id|intval)) and (!$isKit || ($isKit and $seuil))}
    <div class="{if $victime_succes}stock-special victime-succes{elseif $fin_de_vie}stock-special fin-de-vie{/if}" 
         data-stock-state="{if $victime_succes}victime_succes{elseif $fin_de_vie}fin_de_vie{else}normal{/if}"
         data-total-quantity="{$total_quantity}"
         data-platform-stock="{$platform_stock|default:0}">
        <div class="d-flex align-items-center mb-2 blockDeliveryDisplayStock">
            <img src="/modules/ckc_stores_stock/assets/img/delivery{if $fin_de_vie || $victime_succes}FinDeVie{/if}.png" loading="lazy" alt="Icone livraison">
            <div class="small ml-4">
                {if $victime_succes}
                    <span class="style_findevie">Victime de son succès</span><br>
                    <span class="font-weight-bold text-secondary stock-display">Produit épuisé</span><br>
                    <span class="thin style_findevie_thin">Ce produit n'est plus disponible</span>
                {elseif $fin_de_vie}
                    <span class="style_findevie">Fin de série !</span><br>
                    <span class="font-weight-bold text-secondary stock-display">{$platform_stock|default:0} produits en stock</span><br>
                    <span class="thin style_findevie_thin">Commandez vite, stock bas !</span>
                {else}
                    <span class="style_findevie">Stock en magasin</span><br>
                    <span class="font-weight-bold text-secondary stock-display">{$total_quantity} en stock national</span><br>
                    <span class="thin style_findevie_thin"></span>
                {/if}
            </div>
        </div>
        <button type="button" id="selectShop" class="d-block small text-black bg-transparent remove_findevie" {if $fin_de_vie || $victime_succes}style="display: none;"{/if}>Voir le magasin le plus proche <svg
                        xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-move-right ml-2">
                    <path d="M18 8L22 12L18 16" />
                    <path d="M2 12H22" />
                </svg>
            </button>
    </div>
{/if}
