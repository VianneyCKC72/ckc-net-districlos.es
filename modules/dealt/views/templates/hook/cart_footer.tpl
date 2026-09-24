{* Afficher le bloc si au moins un service est suggéré ou si la TVA réduite n'est pas activée *}
{if ($dealt_cart_services|@count > 0) || (!$dealt_tva_enabled)}
    <div class="dealt-cart-services-container">
    <p class="h2" style="margin-top: 50px">{l s='Notre suggestion' d='Shop.Theme.Customdealt'}</p>

    <div class="dealt-cart-services mb-3">

        {* ====== LISTE DES SERVICES SUGGÉRÉS (un par produit non-service du panier) ====== *}
        {foreach from=$dealt_cart_services item=s}
            {if !$s.already_in_cart}
                <div class="dealt-cart-service-box">
                    <div class="dealt-cart-service-inner">

                        <div class="picto col-1">
                            {include file="module:dealt/views/templates/hook/svg/cle_molette.tpl"}
                        </div>
                        <div class="dealt-cart-service-info">
                            <div class="inside col-11">
                                <div class="block-left">
                                    <div class="block-left-header">
                                        <div class="picto-mobile">
                                            {include file="module:dealt/views/templates/hook/svg/cle_molette.tpl"}
                                        </div>
                                        <h3>{l s='Besoin d\'une pose ?' d='Shop.Theme.Customdealt'}</h3>
                                    </div>

                                    <p style="margin-bottom: 1rem;">
                                        {l s='Vérifiez votre éligibilité en fonction du produit et de votre zone géographique ! Nos experts s\'occupent de monter votre clôture, grillage ou portail à votre place.' d='Shop.Theme.Customdealt'}
                                    </p>

                                    <a href="{$s.service_url|default:'#'|escape:'html':'UTF-8'}"
                                       target="_blank" rel="noopener"
                                       class="service-link">
                                        {l s='Détails et conditions du service' d='Shop.Theme.Customdealt'}
                                    </a>
                                </div>

                                <div class="block-right">
                                    <p class="service-name">
                                        {$s.service_title|escape:'html':'UTF-8'}
                                    </p>

                                    {if $s.service_ref|default:''}
                                        <p class="service-ref">
                                            {l s='Ref :' d='Shop.Theme.Customdealt'} {$s.service_ref|escape:'html':'UTF-8'}
                                        </p>
                                    {/if}

                                    <div class="dealt-offer-price">
                                        + {$s.service_price|default:''}
                                    </div>

                                    <div class="dealt-cart-service-action">

                                        {assign var=offer_id value=$s.offer_id|default:''}
                                        {assign var=store_id value=$s.store_id|default:''}
                                        {assign var=elig_disabled value=($offer_id=='' || $store_id=='')}

                                        <div data-dealt-zipcode-check>
                                            <div data-role="check-box" class="dealt-check-box">
                                                <div class="dealt-check-label">
                                                    <label for="dealt-zipcode-input-{$s.service_product_id|intval}">{l s='Trouver un installateur' d='Shop.Theme.Customdealt'}</label>
                                                </div>

                                                <div class="dealt-zipcode-check">
                                                    <div class="dealt-zipcode-wrapper">
                                                        <input id="dealt-zipcode-input-{$s.service_product_id|intval}"
                                                               name="dealt_zipcode"
                                                               type="text"
                                                               placeholder="{l s='Code postal' d='Shop.Theme.Customdealt'}"
                                                               inputmode="numeric"
                                                               pattern="^\d{5}$"
                                                               maxlength="5"
                                                               aria-label="{l s='Code postal (5 chiffres)' d='Shop.Theme.Customdealt'}"
                                                               data-role="zipcode-input"
                                                               style="width: 100%;padding: 5px;border-radius: 20px;border: 2px solid;"
                                                               data-1p-ignore />
                                                        <button class="zipcode-search-icon{if $elig_disabled} is-disabled{/if}" 
                                                                type="button"
                                                                data-role="zipcode-check-btn"
                                                                data-offer-id="{$offer_id|escape:'html':'UTF-8'}"
                                                                data-store-id="{$store_id|escape:'html':'UTF-8'}"
                                                                data-service-product-id="{$s.service_product_id|intval}"
                                                                data-main-product-id="{$s.main_product_id|intval}"
                                                                data-eligibility-url="{$s.eligibility_url|escape:'html':'UTF-8'}"
                                                                aria-label="{l s='Rechercher' d='Shop.Theme.Customdealt'}"
                                                                {if $elig_disabled}disabled="disabled"
                                                                title="{l s='Configuration manquante pour la vérification' d='Shop.Theme.Customdealt'}"{/if}>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div data-role="retry-block" class="dealt-retry-box" hidden>
                                                <button type="button"
                                                        class="btn btn-primary dealt-add-service-only"
                                                        data-service-product-id="{$s.service_product_id|intval}">
                                                    {l s='Ajouter le service de montage' d='Shop.Theme.Customdealt'}
                                                </button>
                                                <div>
                                                    <a href="#" class="dealt-try-again" data-role="try-again">
                                                        {l s='Réessayer avec un autre code postal' d='Shop.Theme.Customdealt'}
                                                    </a>
                                                </div>
                                            </div>

                                            <div data-role="zipcode-result" class="dealt-result-msg" aria-live="polite"></div>

                                            {if $elig_disabled}
                                                <small class="text-muted" style="display:block;margin-top:.5rem;">
                                                    {l s='Vérification indisponible pour le moment (identifiants non configurés).' d='Shop.Theme.Customdealt'}
                                                </small>
                                            {/if}
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            {/if}
        {/foreach}
    </div>


    {include file="module:dealt/views/templates/front/_partials/modal_service.tpl"}

    {if !$dealt_tva_enabled}
        {include file="module:dealt/views/templates/front/_partials/modal_tva.tpl"}
        {if isset($tva_attestation_url)}
            <script>window.tva_attestation_url = '{$tva_attestation_url|escape:'javascript'}';</script>
        {/if}
    {/if}
        <div id="dealt_ajaxurl" style="display: none;" data-update-url="{$ajaxUrlUpdateCart}">{$ajaxUrl}</div>
    </div>
{/if}
