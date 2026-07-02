<article class="col-sm-6 col-md-4 col-xl-3 magasin" itemscope itemtype="https://schema.org/LocalBusiness">
    <picture class="magasin-thumbnail">
        <img src="/modules/ckcstorespresentation/uploads/{$magasin.img_path|default:'default.png'}" loading="lazy"
             width="362"
             height="167"
             alt="{$magasin.ville|default:''}" itemprop="image"/>
    </picture>
    <div class="details">
        <div class="block block-title">
            <span class="small-title" itemprop="name">Districlos</span>
            {if isset($magasin.departement) && $magasin.departement}
                <span class="departement">{$magasin.departement}</span>
            {/if}
            {if isset($magasin.distance)}
                <div class="distance">
                    ~ {($magasin.distance)|round:0} km
                </div>
            {/if}
        </div>

        {if isset($magasin.google_rating) && $magasin.google_rating}
            <div class="ckc-store-rating"
                 itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                <span class="ckc-store-rating__value" itemprop="ratingValue">
                    {$magasin.google_rating|string_format:"%.1f"}
                </span>
                <span class="ckc-store-rating__max">/5</span>
                <meta itemprop="bestRating" content="5"/>
                {if isset($magasin.google_rating_count) && $magasin.google_rating_count}
                    <meta itemprop="reviewCount" content="{$magasin.google_rating_count|intval}"/>
                {/if}
                <span class="ckc-store-rating__stars" aria-hidden="true"
                      style="--rating: {$magasin.google_rating|string_format:"%.2f"}"></span>
                {if isset($magasin.google_rating_count) && $magasin.google_rating_count}
                    <span class="ckc-store-rating__count">{$magasin.google_rating_count|intval} avis</span>
                {/if}
            </div>
        {/if}

        <address class="block block-adresse" itemprop="address" itemscope
                 itemtype="https://schema.org/PostalAddress">
            {if isset($magasin.adresse) && $magasin.adresse}
                <span class="adresse" itemprop="streetAddress">{$magasin.adresse}</span>
            {/if}
            {if isset($magasin.code_postal) && $magasin.code_postal || isset($magasin.ville) && $magasin.ville}
                <span class="adresse">
                    {if isset($magasin.code_postal) && $magasin.code_postal}
                        <span class="cp" itemprop="postalCode">{$magasin.code_postal}</span>
                    {/if}
                    {if isset($magasin.ville) && $magasin.ville}
                        <span class="ville" itemprop="addressLocality">{$magasin.ville}</span>
                    {/if}
                    <meta itemprop="addressCountry" content="FR"/>
                </span>
            {/if}
        </address>

        <div class="block block-contact">
            {if isset($magasin.email) && $magasin.email}
                <span class="email">
                        <a href="mailto:{$magasin.email}" itemprop="email">
                            <img src="/themes/classic/assets/img/magasins/email.png" alt="email" width="14" height="10">
                            {$magasin.email}
                        </a>
                    </span>
            {/if}
            {if isset($magasin.telephone) && $magasin.telephone}
                <span class="telephone">
                        <a href="tel:{$magasin.telephone}" itemprop="telephone">
                            <img src="/themes/classic/assets/img/magasins/telephone.png" alt="telephone" width="14"
                                 height="12">
                            {$magasin.telephone}
                        </a>
                    </span>
            {/if}
        </div>

        <div class="block block-horaire">
            {if isset($magasin.horaires_jours) && $magasin.horaires_jours}
                <span class="jours">{$magasin.horaires_jours}</span>
            {/if}
            {if isset($magasin.horaires_heures) && $magasin.horaires_heures}
                <span class="heures" itemprop="openingHours">{$magasin.horaires_heures}</span>
            {/if}
            <span class="heures" itemprop="openingHours">Samedi</span>
            {if isset($magasin.horaires_samedi) && $magasin.horaires_samedi}
                <span class="heures" itemprop="openingHours">{$magasin.horaires_samedi}</span>
            {else}
                <span class="heures" itemprop="openingHours">Fermé</span>
            {/if}
            {if isset($magasin.horaires_info_suppl) && $magasin.horaires_info_suppl}
                <span class="heures magasins-info-suppl" itemprop="openingHours">{$magasin.horaires_info_suppl}</span>
            {else}
                <span class="heures magasins-info-suppl" itemprop="openingHours"></span>
            {/if}
        </div>

        <div class="block block-actions">
            <a href="#" class="favoris selected-0 setStore shopId-{$magasin.id_multiboutique}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="lucide lucide-heart">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>
            </a>
            <a href="{Context::getContext()->link->getCMSLink($magasin.id_cms_detail)}" class="btn btn-primary btn-primary-small" itemprop="url">
                Voir la page <span class="only-lg">&nbsp;du magasin</span>
            </a>
        </div>
    </div>
</article>