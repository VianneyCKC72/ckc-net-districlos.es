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
                <span class="nameStore" style="text-decoration: underline;">
                    <img src="/modules/ckc_stores_stock/assets/img/nameStore.webp" alt="Magasin" style="width: 50px; height: 50px;">
                    <span class="font-weight-normal">Votre magasin</span> <span class="font-weight-bold">Districlos {$stock.store_name|replace:'DISTRICLOS ':''|replace:'Districlos ':''}</span>
                </span> <br>
                
                {* Afficher la section principale uniquement s'il y a des produits normaux *}
                {if isset($stocks) && count($stocks) > 0}
                    {* Afficher le message principal *}
                    <p class="productLimit" style="color:#79c046;">
                        {if $stock.isClickAndCollect}
                            {if $stock.stock_ok}
                                Super ! Un ou plusieurs produits sont en stock ( hors plateforme )
                            {else}
                                Un ou des produits sont en cours de
                                réapprovisionnement ( hors plateforme )
                            {/if}
                        {else}
                            Notre magasin vous livre à domicile ou sur
                            votre chantier !
                        {/if}
                        <span class="info-icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#79c046" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 5px; vertical-align: middle; cursor: pointer;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span class="custom-tooltip">
                                {if $stock.isClickAndCollect}
                                    {if $stock.stock_ok}
                                        {if $stock.store_id == 19}
                                            Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.
                                        {else}
                                            Vous pouvez venir récupérer les produits en stock dans votre magasin 2h après votre commande.
                                        {/if}
                                    {else}
                                        {if $stock.store_id == 19}
                                            Disponibilité sous 5 jours ouvrés en magasin, selon stock. Le magasin vous contactera dès que votre commande sera prête.
                                        {else}
                                            Vous pouvez venir récupérer les produits en stock dans votre magasin.<br><br>Pour les produits en cours de réapprovisionnement, nous vous appellerons aussitôt ces derniers disponibles.
                                        {/if}
                                    {/if}
                                {else}
                                    La livraison se fait sur rendez-vous uniquement. Nous allons vous contacter suite à votre commande pour déterminer cette date.<br><br>Le délai moyen pour vos produits est de 10 jours.
                                {/if}
                            </span>
                        </span>
                    </p>
                    
                    {* Afficher la liste des produits normaux *}
                    <div class=products>
                        {foreach from=$stocks item="productStock" key="index"}
                            <p class="productQuantity" style="color:#000000;">
                                {if $productStock.stock_ok}
                                    <img src="/modules/ckc_stores_stock/assets/img/selectionner.webp" class="unveil-loaded">
                                {else}
                                    <img src="/modules/ckc_stores_stock/assets/img/moins.webp" class="unveil-loaded">
                                {/if}
                                <span>
                                    {$productStock.product_name}<br>
                                    <small style="color: {if $productStock.stock_ok}#79C046{else}#F6AC00{/if}; font-size: 11px;">
                                        {if $productStock.stock_ok}Disponible en magasin{else}Forte demande : expédition/retrait sous 10 jours{/if}
                                    </small>
                                </span>
                            </p>
                        {/foreach}
                    </div>
                {/if}

                {if isset($stock.hasFinSerieProducts) && $stock.hasFinSerieProducts && isset($finSerieStocks) && count($finSerieStocks) > 0}
                    <div class="fin-serie-section mt-3">
                        <p class="productLimit" style="color:#79c046;">
                            Un ou des produits sont en stock sur plateforme
                            <span class="info-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#79c046" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 5px; vertical-align: middle; cursor: pointer;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                <span class="custom-tooltip">
                                    Ce produit est disponible sur notre plateforme logistique, pour une meilleure disponibilité.
                                </span>
                            </span>
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