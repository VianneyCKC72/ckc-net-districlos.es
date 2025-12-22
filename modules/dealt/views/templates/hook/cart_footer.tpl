{* Afficher le bloc si au moins un service est suggéré ou si la TVA réduite n'est pas activée *}
{if ($dealt_cart_services|@count > 0) || (!$dealt_tva_enabled)}
    <div class="dealt-cart-services-container">
    <p class="h2" style="margin-top: 50px">Notre suggestion</p>

    <div class="dealt-cart-services">

        {* ====== LISTE DES SERVICES SUGGÉRÉS (un par produit non-service du panier) ====== *}
        {foreach from=$dealt_cart_services item=s}
            {if !$s.already_in_cart}
                <div class="dealt-cart-service-box">
                    <div class="dealt-cart-service-inner">

                        <div class="picto col-1">
                            {include file="module:dealt/views/templates/hook/svg/cle_molette.tpl"}
                        </div>
                        <div class="dealt-cart-service-info">
                            <h3>Vous souhaitez une pose ? Vérifiez l'éligibilité</h3>

                            <div class="inside col-11">

                                <div class="block-left">
                                    <p class="service-name">
                                        {$s.service_title|escape:'html':'UTF-8'}
                                    </p>

                                    {if $s.service_ref|default:''}
                                        <p class="service-ref">
                                            Ref : {$s.service_ref|escape:'html':'UTF-8'}
                                        </p>
                                    {/if}

                                    <a href="{$s.service_url|default:'#'|escape:'html':'UTF-8'}"
                                       target="_blank" rel="noopener"
                                       class="service-link">
                                        Détails des conditions de service
                                    </a>

                                    {if $s.main_product_name|default:''}
                                        <small class="text-muted" style="display:block;margin-top:.25rem;">
                                            Pour : {$s.main_product_name|escape:'html':'UTF-8'}
                                        </small>
                                    {/if}
                                </div>

                                <div class="block-center">
                                    <div class="dealt-cart-service-action">

                                        {assign var=offer_id value=$s.offer_id|default:''}
                                        {assign var=store_id value=$s.store_id|default:''}
                                        {assign var=elig_disabled value=($offer_id=='' || $store_id=='')}

                                        <div data-dealt-zipcode-check>
                                            <div data-role="check-box" class="dealt-check-box">
                                                <div class="dealt-check-label">
                                                    {include file="module:dealt/views/templates/hook/svg/pin.tpl"}
                                                    <label for="dealt-zipcode-input-{$s.service_product_id|intval}">
                                                        Vérifiez la disponibilité du service
                                                    </label>
                                                </div>

                                                <div class="dealt-zipcode-check">
                                                    <div class="dealt-zipcode-wrapper">
                                                        <input type="text"
                                                               placeholder="Code postal"
                                                               data-role="zipcode-input"
{*                                                               id="dealt-zipcode-input-{$s.service_product_id|intval}" />*}
                                                        id="dealt-zipcode-input" />
                                                    </div>

                                                    <a href="{if $elig_disabled}javascript:void(0){else}#{/if}"
                                                       class="zipcode-check-btn{if $elig_disabled} is-disabled{/if}"
                                                       type="button"
                                                       data-role="zipcode-check-btn"
                                                       data-offer-id="{$offer_id|escape:'html':'UTF-8'}"
                                                       data-store-id="{$store_id|escape:'html':'UTF-8'}"
                                                       data-service-product-id="{$s.service_product_id|intval}"
                                                       data-main-product-id="{$s.main_product_id|intval}"
                                                       data-eligibility-url="{$s.eligibility_url|escape:'html':'UTF-8'}"
                                                       {if $elig_disabled}aria-disabled="true"
                                                       title="Configuration manquante pour la vérification"{/if}>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                             class="lucide lucide-search-icon lucide-search">
                                                            <path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/>
                                                        </svg>
                                                        Recherche
                                                    </a>
                                                </div>
                                            </div>

                                            <div data-role="retry-block" class="dealt-retry-box" hidden>
                                                <button type="button"
                                                        class="btn btn-primary dealt-add-service-only"
                                                        data-service-product-id="{$s.service_product_id|intval}">
                                                    Ajouter le service de montage
                                                </button>
                                                <div>
                                                    <a href="#" class="dealt-try-again" data-role="try-again">
                                                        Réessayer avec un autre code postal
                                                    </a>
                                                </div>
                                            </div>

                                            <div data-role="zipcode-result" class="dealt-result-msg" aria-live="polite"></div>

                                            {if $elig_disabled}
                                                <small class="text-muted" style="display:block;margin-top:.5rem;">
                                                    Vérification indisponible pour le moment (identifiants non configurés).
                                                </small>
                                            {/if}
                                        </div>

                                    </div>
                                </div>

                                <div class="block-right">
                                    <div class="dealt-offer-price">
                                        {$s.service_price|default:''}
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            {/if}
        {/foreach}

        {* ====== BLOC TVA RÉDUITE ====== *}
        {if !$dealt_tva_enabled}
            <div class="dealt-tva-box">
                <div class="dealt-cart-service-inner">
                    <div class="dealt-cart-tva-info">
                        <h3>Réduisez la TVA</h3>
                        <div class="inside">
                            <div class="picto col-1">
                                {include file="module:dealt/views/templates/hook/svg/coupon.tpl"}
                            </div>
                            <div class="no-padding col-11">
                                <strong>Faites des économies en bénéficiant d'une TVA réduite !</strong>
                                En ajoutant notre service de pose par un professionnel, vous pouvez bénéficier d'une TVA à 10%,
                                ce qui vous permettra d'économiser 10% sur le montant de vos achats !<br>
                                <strong>Je confirme que les travaux seront réalisés dans un lieu d'habitation achevé depuis
                                    plus de 2 ans (agrandissements exclus).</strong>
                            </div>
                        </div>
                        <div class="dealt-cart-service-action">
                            <a href="{$tva_modal_url|default:'#'|escape:'html':'UTF-8'}"
                               class="btn-add dealt-tva-open-popup">
                                Ajouter le service au panier
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        {/if}

    </div>


    {include file="module:dealt/views/templates/front/_partials/modal_service.tpl"}

    {if !$dealt_tva_enabled}
        {include file="module:dealt/views/templates/front/_partials/modal_tva.tpl"}
        {if isset($tva_attestation_url)}
            <script>window.tva_attestation_url = '{$tva_attestation_url|escape:'javascript'}';</script>
        {/if}
    {/if}
        <div id="dealt_ajaxurl" style="display: none;" >{$ajaxUrl}</div>
    </div>
{/if}
