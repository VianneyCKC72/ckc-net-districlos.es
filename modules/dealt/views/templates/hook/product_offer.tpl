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

        {*<div class="picto">
            {include file="module:dealt/views/templates/hook/svg/cle_molette.tpl"}
        </div>*}

        <div class="dealt-offer-content">
            {* Titre cliquable pour déplier/replier le contenu *}
            <button type="button" 
                    class="dealt-offer-toggle" 
                    data-role="dealt-toggle"
                    aria-expanded="false"
                    aria-controls="dealt-collapse-{$service.dealt_offer_id|escape:'html':'UTF-8'}">
                <div class="dealt-toggle-text">
                    <div class="dealt-title-row">
                        <span class="dealt-offer-title">
                            Besoin d'une pose ?
                        </span>
                        <span class="dealt-offer-badge" aria-label="Promotion pose -10%" style="padding: 5px; border-radius: 20px; background: #ff144e; color: #ffffff; font-weight: 700; font-size: 12px; line-height: 1;">
                            -10%
                        </span>
                        {if !empty($service.dealt_service_url)}
                            <a href="{$service.dealt_service_url|escape:'html':'UTF-8'}"
                               rel="noopener noreferrer nofollow"
                               class="dealt-conditions-link-toggle" 
                               target="_blank"
                               data-role="link-in-toggle"
                               style="display: none;"
                               onclick="event.stopPropagation();">
                                Détails conditions du service
                            </a>
                        {/if}
                    </div>
                    <span class="dealt-offer-subtitle" data-role="subtitle">
                        Economisez <strong> 10% </strong> en faisant appel à notre service !
                    </span>
                    <span class="dealt-offer-subtitle-expanded" data-role="subtitle-expanded" style="display: none;">
                        Réservez un installateur et <strong>réduisez votre TVA à 10% !</strong>
                    </span>
                </div>
                <span class="dealt-toggle-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </button>

            {* Contenu repliable *}
            <div class="dealt-collapse-content" 
                 id="dealt-collapse-{$service.dealt_offer_id|escape:'html':'UTF-8'}"
                 data-role="dealt-collapse"
                 style="display: none;">
                
                <p class="dealt-nom-produit">
                    {$service.dealt_service_title|escape:'html':'UTF-8'}
                </p>

                <div class="dealt-offer-price">
                    {if !empty($service.dealt_service_price_before)}
                        <span class="dealt-offer-price-before" style="margin-right: 8px; text-decoration: line-through;">
                            +{$service.dealt_service_price_before|escape:'html':'UTF-8'}
                        </span>
                        <span class="dealt-offer-price-now" style="color: #ff144e; font-weight: 700;">
                            +{$service.dealt_service_price|escape:'html':'UTF-8'}
                        </span>
                    {else}
                        +{$service.dealt_service_price|escape:'html':'UTF-8'}
                    {/if}
                </div>

                {* Bloc vérification code postal *}
                <div data-dealt-zipcode-check>
                    <div data-role="check-box" class="dealt-check-box">
                        <div class="dealt-check-label">
                            <label for="dealt-zipcode-input">Trouver un installateur</label>
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
                                <button class="zipcode-search-icon" 
                                        type="button"
                                        data-role="zipcode-check-btn"
                                        aria-label="Rechercher">
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

        {* Script pour gérer le déplier/replier *}
        <script>
        (function() {
            const toggle = document.querySelector('[data-role="dealt-toggle"][aria-controls="dealt-collapse-{$service.dealt_offer_id|escape:'html':'UTF-8'}"]');
            const content = document.getElementById('dealt-collapse-{$service.dealt_offer_id|escape:'html':'UTF-8'}');
            const linkInToggle = toggle ? toggle.querySelector('[data-role="link-in-toggle"]') : null;
            const subtitle = toggle ? toggle.querySelector('[data-role="subtitle"]') : null;
            const subtitleExpanded = toggle ? toggle.querySelector('[data-role="subtitle-expanded"]') : null;
            
            if (toggle && content) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    
                    if (isExpanded) {
                        // Replier
                        content.style.display = 'none';
                        this.setAttribute('aria-expanded', 'false');
                        this.classList.remove('is-expanded');
                        // Masquer le lien dans le toggle
                        if (linkInToggle) {
                            linkInToggle.style.display = 'none';
                        }
                        // Remettre le sous-titre initial
                        if (subtitle && subtitleExpanded) {
                            subtitle.style.display = 'block';
                            subtitleExpanded.style.display = 'none';
                        }
                    } else {
                        // Déplier
                        content.style.display = 'block';
                        this.setAttribute('aria-expanded', 'true');
                        this.classList.add('is-expanded');
                        // Afficher le lien dans le toggle
                        if (linkInToggle) {
                            linkInToggle.style.display = 'inline-block';
                        }
                        // Changer le sous-titre
                        if (subtitle && subtitleExpanded) {
                            subtitle.style.display = 'none';
                            subtitleExpanded.style.display = 'block';
                        }
                    }
                });
            }
        })();
        </script>
    </div>
    {/foreach}
    <div id="dealt_product_length" style="display: none;" >{$dealt_product_length}</div>
    <div id="dealt_ajaxurl" data-id-product-attribute="{$id_product_attribute}" style="display: none;" >{$ajaxUrl}</div>
    {include file="module:dealt/views/templates/front/_partials/modal_service.tpl"}
{/if}