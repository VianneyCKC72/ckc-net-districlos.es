<div id="ckcproductreviews" class="my-4" itemscope itemtype="https://schema.org/Product">
    <meta itemprop="name" content="Nom du produit ou service évalué" />

    <div class="row align-items-center line-review">
        <!-- Bloc statique avec note globale -->
        <div class="col-md-4 mb-3">
            <div class="block-reviews" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                <div class="review-summary">
                    <div class="review-stars">
                        <div class="stars">
                            {for $i=1 to 5}
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="22" viewBox="0 0 24 22" fill="none">
                                    <path d="M12.4136 0.90236C12.2524 0.901393 12.0942 0.943858 11.9568 1.02494C11.8194 1.10602 11.7085 1.22247 11.6365 1.36109L8.67381 7.05581L1.6389 8.09632C1.48143 8.11971 1.33378 8.18455 1.21232 8.28366C1.09086 8.38278 1.00034 8.51229 0.950787 8.65783C0.901239 8.80337 0.894603 8.95926 0.931615 9.1082C0.968626 9.25715 1.04784 9.39333 1.16047 9.50165L6.02673 14.1785L4.94297 20.9472C4.91873 21.0987 4.93862 21.2536 5.00045 21.3948C5.06227 21.536 5.16361 21.658 5.29321 21.7473C5.42281 21.8365 5.5756 21.8895 5.73463 21.9003C5.89365 21.9111 6.05268 21.8793 6.19407 21.8086L12.408 18.6924L18.622 21.8086C18.7634 21.8793 18.9224 21.9111 19.0814 21.9003C19.2404 21.8895 19.3932 21.8365 19.5228 21.7473C19.6524 21.658 19.7538 21.536 19.8156 21.3948C19.8774 21.2536 19.8973 21.0987 19.8731 20.9472L18.7893 14.1785L23.6556 9.50165C23.7682 9.39333 23.8474 9.25715 23.8844 9.1082C23.9214 8.95926 23.9148 8.80337 23.8653 8.65783C23.8157 8.51229 23.7252 8.38278 23.6037 8.28366C23.4823 8.18455 23.3346 8.11971 23.1771 8.09632L16.1422 7.05581L13.1796 1.36109C13.1084 1.22411 12.9992 1.10875 12.864 1.02778C12.7288 0.946807 12.5729 0.903398 12.4136 0.90236Z"
                                          fill="#FFD24C" />
                                </svg>
                            {/for}
                        </div>
                    </div>
                    <div class="review-count">
                        <div>Calculé à partir de <span itemprop="reviewCount">564</span> avis
                            <br>
                            obtenus sur les 12 derniers mois</div>
                        <div style="font-size: 12px;"><span itemprop="reviewCount">6917</span> avis depuis le 09/01/2015</div>
                    </div>
                    <meta itemprop="ratingValue" content="4.8" />
                    <meta itemprop="bestRating" content="5" />

                    <div class="review-logo">
                        <picture>
                            <source srcset="{$module_dir}views/img/avis-verifies-logo-new.webp" type="image/webp">
                            <img src="{$module_dir}views/img/avis-verifies-logo-new.png" alt="Avis Vérifiés" class="img-fluid" loading="lazy">
                        </picture>
                    </div>

                    <div class="review-note italic">Avis soumis à un contrôle</div>
                    <a target="_blank" href="https://www.avis-verifies.com/avis-clients/cloture-discount.fr" class="btn btn-primary mt-2 btn-review">Voir tous les avis</a>
                </div>
            </div>
        </div>

        <!-- Bloc avis individuels -->
        <div class="col-md-8 ckc-avis-items">
            {if $enable_carousel}
                <div class="swiper ckc-review-carousel">
                    <div class="swiper-wrapper">
                        {foreach from=$reviews item=review}
                            <div class="swiper-slide review-card">
                                {include file='module:ckcproductreviews/views/templates/_partials/review_card.tpl'}
                            </div>
                        {/foreach}
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-right"><path d="M18 8L22 12L18 16"></path><path d="M2 12H22"></path></svg></div>
                    <div class="swiper-button-prev"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-move-left"><path d="M6 8L2 12L6 16"></path><path d="M2 12H22"></path></svg></div>
                </div>
            {else}
                <div class="row ckc-review-static">
                    {foreach from=$reviews item=review name=reviewLoop}
                        {if $smarty.foreach.reviewLoop.index < 3}
                            <div class="col-md-4">
                                <div class="review-card">
                                    {include file='module:ckcproductreviews/views/templates/_partials/review_card.tpl'}
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                </div>
            {/if}

        </div>
        <a target="_blank" href="https://www.avis-verifies.com/avis-clients/cloture-discount.fr" class="btn btn-primary mt-2 btn-review-mobile">Voir tous les avis</a>
    </div>
</div>
