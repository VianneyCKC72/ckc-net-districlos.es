{* Afficher uniquement si un produit service existe *}
{if !empty($data[0].dealt_service_product_id)}
    {*{dump($data)}*}
    {foreach $data as $service}
    <div class="dealt-offer-box"
         data-dealt="1"
         data-product-id="{$service.dealt_product_id|intval}"
         data-service-product-id="{$service.dealt_service_product_id|intval}"
         data-offer-id="{$service.dealt_offer_id|escape:'html':'UTF-8'}"
         data-store-id="{$service.dealt_store_id|escape:'html':'UTF-8'}"
         data-eligibility-url="{$service.dealt_check_availability_url|escape:'html':'UTF-8'}">

        <div class="picto">
            {include file="module:dealt/views/templates/hook/svg/cle_molette.tpl"}
        </div>

        <div class="dealt-offer-content">
            <div class="dealt-offer-title">
                Vous souhaitez une pose ? Vérifiez l'éligibilité
            </div>

            <p class="dealt-nom-produit">
                {$service.dealt_service_title|escape:'html':'UTF-8'}
            </p>

            <div class="dealt-offer-price">
                +{$service.dealt_service_price|escape:'html':'UTF-8'}
                {if !empty($service.dealt_service_url)}
                    <a href="{$service.dealt_service_url|escape:'html':'UTF-8'}"
                       rel="noopener noreferrer nofollow"
                       class="dealt-conditions-link">
                        Détails des conditions du service
                    </a>
                {/if}
            </div>

            {* Bloc vérification code postal *}
            <div data-dealt-zipcode-check>
                <div data-role="check-box" class="dealt-check-box">
                    <div class="dealt-check-label">
                        {include file="module:dealt/views/templates/hook/svg/pin.tpl"}
                        <label for="dealt-zipcode-input">Vérifiez la disponibilité du service</label>
                    </div>

                    <div class="dealt-zipcode-check">
                        <div class="dealt-zipcode-wrapper">
                            <input id="dealt-zipcode-input"
                                   name="dealt_zipcode"
                                   type="text"
                                   placeholder="Code postal"
                                   inputmode="numeric"
                                   pattern="^\d{5}$"
                                   maxlength="5"
                                   aria-label="Code postal (5 chiffres)"
                                   data-role="zipcode-input" />
                        </div>

                        <button class="zipcode-check-btn btn btn-secondary"
                                type="button"
                                data-role="zipcode-check-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="lucide lucide-search-icon lucide-search">
                                <path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/>
                            </svg>
                            Recherche
                        </button>
                    </div>
                </div>

                <div data-role="retry-block" class="dealt-retry-box" hidden>
                    <button type="button" id="dealt-add-service-only" class="btn btn-primary">
                        Ajouter le service de montage
                    </button>
                    <div>
                        <a href="#" id="dealt-try-again" data-role="try-again">Réessayer avec un autre code postal</a>
                    </div>
                </div>

                <div data-role="zipcode-result" class="dealt-result-msg" aria-live="polite"></div>
            </div>
        </div>
    </div>
    {/foreach}
    <div id="dealt_product_length" style="display: none;" >{$dealt_product_length}</div>
    <div id="dealt_ajaxurl" data-id-product-attribute="{$id_product_attribute}" style="display: none;" >{$ajaxUrl}</div>
    {include file="module:dealt/views/templates/front/_partials/modal_service.tpl"}
{/if}