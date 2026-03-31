{if !in_array(294,Product::getProductCategories($product->id|intval)) and !in_array(295,Product::getProductCategories($product->id|intval)) and !in_array(293,Product::getProductCategories($product->id|intval))}
    <div class="ckc-stores-stock-block {if $ui.state=='victime_succes'}stock-special victime-succes{elseif $ui.state=='fin_de_vie' || $ui.state=='fin_de_vie_national'}stock-special fin-de-vie{/if}"
         data-stock-state="{$ui.state}"
         data-total-quantity="{$total_quantity}"
         data-platform-stock="{$platform_stock|default:0}">
        <div class="d-flex align-items-center blockDeliveryDisplayStock">
            <img class="ckc-stores-stock-block__icon" src="/modules/ckc_stores_stock/assets/img/{$ui.image}" loading="lazy" alt="Icone livraison">
            <div class="small d-flex flex-column text-center ckc-stores-stock-block__text">
                <span class="style_findevie">{$ui.title}</span>
                <span class="font-weight-bold text-secondary stock-display">{$ui.stock}</span>
                <span class="thin style_findevie_thin">{$ui.subtitle}</span>
            </div>
        </div>
        <button type="button" id="selectShop" style="text-align: start; text-decoration: underline;{if !$ui.showStoreButton} display: none;{/if}" class="d-block small text-black bg-transparent font-weight-semibold text-underline ckc-stores-stock-block__cta">Voir les stocks par magasin</button>
    </div>
{/if}
