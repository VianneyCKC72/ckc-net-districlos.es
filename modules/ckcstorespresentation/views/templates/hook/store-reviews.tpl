{if $store.google_rating || $reviews|@count}
{assign var=star_path value="M12.4136 0.90236C12.2524 0.901393 12.0942 0.943858 11.9568 1.02494C11.8194 1.10602 11.7085 1.22247 11.6365 1.36109L8.67381 7.05581L1.6389 8.09632C1.48143 8.11971 1.33378 8.18455 1.21232 8.28366C1.09086 8.38278 1.00034 8.51229 0.950787 8.65783C0.901239 8.80337 0.894603 8.95926 0.931615 9.1082C0.968626 9.25715 1.04784 9.39333 1.16047 9.50165L6.02673 14.1785L4.94297 20.9472C4.91873 21.0987 4.93862 21.2536 5.00045 21.3948C5.06227 21.536 5.16361 21.658 5.29321 21.7473C5.42281 21.8365 5.5756 21.8895 5.73463 21.9003C5.89365 21.9111 6.05268 21.8793 6.19407 21.8086L12.408 18.6924L18.622 21.8086C18.7634 21.8793 18.9224 21.9111 19.0814 21.9003C19.2404 21.8895 19.3932 21.8365 19.5228 21.7473C19.6524 21.658 19.7538 21.536 19.8156 21.3948C19.8774 21.2536 19.8973 21.0987 19.8731 20.9472L18.7893 14.1785L23.6556 9.50165C23.7682 9.39333 23.8474 9.25715 23.8844 9.1082C23.9214 8.95926 23.9148 8.80337 23.8653 8.65783C23.8157 8.51229 23.7252 8.38278 23.6037 8.28366C23.4823 8.18455 23.3346 8.11971 23.1771 8.09632L16.1422 7.05581L13.1796 1.36109C13.1084 1.22411 12.9992 1.10875 12.864 1.02778C12.7288 0.946807 12.5729 0.903398 12.4136 0.90236Z"}

{* Hook Creative Elements : displayCkcStoreReviews (module ckcstorespresentation) *}
<section id="ckcstorespresentation-reviews" class="ckc-store-reviews ckc-store-reviews--hook my-3" data-ckc-hook="displayCkcStoreReviews" itemscope itemtype="https://schema.org/LocalBusiness">
    {if $store.departement}
        <meta itemprop="name" content="{$store.departement|escape:'html':'UTF-8'}" />
    {/if}

    <div class="ckc-store-reviews__row">
        {if $store.google_rating}
            <div class="ckc-store-reviews__summary" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                <span class="ckc-store-reviews__value" itemprop="ratingValue">{$store.google_rating|string_format:"%.1f"}</span>
                <span class="ckc-store-reviews__max">/5</span>
                <meta itemprop="bestRating" content="5" />
                {if $store.google_rating_count}
                    <meta itemprop="reviewCount" content="{$store.google_rating_count|intval}" />
                {/if}
                <span class="ckc-store-reviews__stars" aria-hidden="true">
                    {for $i=1 to 5}
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 24 22" fill="none" aria-hidden="true">
                            <path d="{$star_path}" fill="#FFD24C"/>
                        </svg>
                    {/for}
                </span>
                {if $store.google_rating_count}
                    <span class="ckc-store-reviews__count">{$store.google_rating_count|intval} {l s='avis' d='Shop.Theme.Customstores'}</span>
                {/if}
                {if $maps_url}
                    <a href="{$maps_url|escape:'html':'UTF-8'}" class="btn btn-primary btn-sm ckc-store-reviews__google" target="_blank" rel="noopener noreferrer">
                        {l s='Voir sur Google' d='Shop.Theme.Customstores'}
                    </a>
                {/if}
            </div>
        {/if}

        {if $reviews|@count}
            <div class="ckc-store-reviews__slider" aria-label="{l s='Avis clients' d='Shop.Theme.Customstores'}">
                {foreach from=$reviews item=review}
                    <article class="ckc-store-reviews__card" itemprop="review" itemscope itemtype="https://schema.org/Review">
                        <div class="ckc-store-reviews__card-stars" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                            <meta itemprop="ratingValue" content="5" />
                            <meta itemprop="bestRating" content="5" />
                            {for $i=1 to 5}
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="11" viewBox="0 0 24 22" fill="none" aria-hidden="true">
                                    <path d="{$star_path}" fill="#FFD24C"/>
                                </svg>
                            {/for}
                        </div>
                        <p class="ckc-store-reviews__card-text" itemprop="reviewBody">
                            {$review.review_text|escape:'html':'UTF-8'}
                        </p>
                        <footer class="ckc-store-reviews__card-meta">
                            <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                                <span itemprop="name">{$review.author_name|escape:'html':'UTF-8'}</span>
                            </span>
                            —
                            <time itemprop="datePublished" datetime="{$review.review_date|date_format:'%Y-%m-%d'}">
                                {$review.review_date|date_format:'%d/%m/%Y'}
                            </time>
                        </footer>
                    </article>
                {/foreach}
            </div>
        {/if}
    </div>
</section>
{/if}
