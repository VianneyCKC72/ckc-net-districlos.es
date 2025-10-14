{if $stock}
    <section id="stock-summary-content" class="page-content page-order-confirmation">
        <div class="card">
            <div class="card-header">
                {if $stock.isClickAndCollect}
                    Click & Collect
                {else}
                    Livraison
                {/if}
            </div>
            <div class="card-block">
                <span class="nameStore" style="text-decoration: underline;">{$stock.store_name}</span> <br>
                
                {* Afficher la section normale seulement s'il y a des produits normaux *}
                {if isset($stocks) && count($stocks) > 0}
                    <p class="productLimit" style="color:#79c046;">
                        {if $stock.isClickAndCollect}
                            {if $stock.stock_ok}
                                Super ! Tous vos produits sont en stock
                            {else}
                                Un ou des produits sont en cours de
                                réapprovisionnement
                            {/if}
                        {else}
                            Notre magasin vous livre à domicile ou sur
                            votre chantier !
                        {/if}
                    </p>
                    <div class=products>
                        {foreach from=$stocks item="productStock" key="index"}
                            <p class="productQuantity" style="color:#000000;">
                                {if $productStock.stock_ok}
                                    <img src="/modules/ckc_stores_stock/assets/img/selectionner.webp" class="unveil-loaded">
                                {else}
                                    <img src="/modules/ckc_stores_stock/assets/img/moins.webp" class="unveil-loaded">
                                {/if}
                                {$productStock.product_name}
                                <br>
                            </p>
                        {/foreach}
                    </div>
        
                    <div class="text">
                        {if $stock.isClickAndCollect}
                            {if $stock.stock_ok}
                                <p>Vous pouvez venir récupérer les produits en stock dans
                                    votre magasin 2h après votre commande.</p>
                            {else}
                                <p>Vous pouvez venir récupérer les produits en stock dans
                                    votre magasin.</p>
                                <p>Pour les produits en cours de réapprovisionnement,
                                    nous vous appellerons aussitôt ces derniers disponibles.</p>
                            {/if}
                        {else}
                            <p>La livraison se fait sur rendez-vous uniquement. Nous
                                allons vous contacter suite à votre commande pour
                                déterminer cette date.</p>
                            <p>Le délai moyen pour vos produits est de 10 jours.</p>
                        {/if}
                    </div>
                {/if}

                {if isset($stock.hasFinSerieProducts) && $stock.hasFinSerieProducts && isset($finSerieStocks) && count($finSerieStocks) > 0}
                    <div class="fin-serie-section mt-3">
                        <p class="productLimit" style="color:#79c046;">
                            Un ou des produits sont en stock sur plateforme
                        </p>
                        <div class="products fin-serie-products">
                            {foreach from=$finSerieStocks item="productStock" key="index"}
                                <p class="productQuantity" style="color:#000000;">
                                    <img src="/modules/ckc_stores_stock/assets/img/selectionner.webp" class="unveil-loaded">
                                    {$productStock.product_name}
                                </p>
                            {/foreach}
                        </div>
                    </div>
                {/if}

            </div>
        </div>
    </section>
{/if}