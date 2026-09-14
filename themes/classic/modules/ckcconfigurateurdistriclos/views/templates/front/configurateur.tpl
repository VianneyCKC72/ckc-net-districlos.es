{extends file='layouts/layout-full-width.tpl'}

{block name='content'}
<script>
window.CKC_CFG_I18N = {
  select: '{l s='Sélectionner' d='Shop.Theme.Customconfigurator' js=1}',
  selected: '{l s='Sélectionné' d='Shop.Theme.Customconfigurator' js=1}',
  fromPrice: '{l s='À partir de' d='Shop.Theme.Customconfigurator' js=1}',
  none: '{l s='Aucun' d='Shop.Theme.Customconfigurator' js=1}',
  angle: '{l s='angle' d='Shop.Theme.Customconfigurator' js=1}',
  angles: '{l s='angles' d='Shop.Theme.Customconfigurator' js=1}',
  angleS: '{l s='angle(s)' d='Shop.Theme.Customconfigurator' js=1}',
  heights: '{l s='Hauteurs' d='Shop.Theme.Customconfigurator' js=1}',
  fenceHeight: '{l s='Hauteur de clôture' d='Shop.Theme.Customconfigurator' js=1}',
  configurePrefix: '{l s='Paramétrer :' d='Shop.Theme.Customconfigurator' js=1}',
  soubassement: '{l s='Soubassement' d='Shop.Theme.Customconfigurator' js=1}',
  option: '{l s='Option' d='Shop.Theme.Customconfigurator' js=1}',
  gamme: '{l s='Gamme' d='Shop.Theme.Customconfigurator' js=1}',
  stepType: '{l s='Type de clôture' d='Shop.Theme.Customconfigurator' js=1}',
  stepSpecs: '{l s='Spécificités' d='Shop.Theme.Customconfigurator' js=1}',
  stepRange: '{l s='Gamme' d='Shop.Theme.Customconfigurator' js=1}',
  stepDimensions: '{l s='Dimensions' d='Shop.Theme.Customconfigurator' js=1}',
  stepOptions: '{l s='Options' d='Shop.Theme.Customconfigurator' js=1}',
  stepSections: '{l s='Sections' d='Shop.Theme.Customconfigurator' js=1}',
  stepRecap: '{l s='Récapitulatif' d='Shop.Theme.Customconfigurator' js=1}',
  stepPrefix: '{l s='Étape' d='Shop.Theme.Customconfigurator' js=1}',
  sansOptions: '{l s='Sans options' d='Shop.Theme.Customconfigurator' js=1}',
  wallStart: '{l s='Départ sur mur' d='Shop.Theme.Customconfigurator' js=1}',
  freeStart: '{l s='Départ libre' d='Shop.Theme.Customconfigurator' js=1}',
  wallEnd: '{l s='fin sur mur' d='Shop.Theme.Customconfigurator' js=1}',
  freeEnd: '{l s='fin libre' d='Shop.Theme.Customconfigurator' js=1}',
  optionsSelected: '{l s='option(s) sélectionnée(s)' d='Shop.Theme.Customconfigurator' js=1}',
  ttcSuffix: '{l s=' € TTC' d='Shop.Theme.Customconfigurator' js=1}',
  addingToCart: '{l s='Ajout en cours…' d='Shop.Theme.Customconfigurator' js=1}',
  addToCart: '{l s='Ajouter au panier' d='Shop.Theme.Customconfigurator' js=1}',
  productPrefix: '{l s='Produit #' d='Shop.Theme.Customconfigurator' js=1}',
  bootstrapError: '{l s='Erreur bootstrap' d='Shop.Theme.Customconfigurator' js=1}',
  startSectionConfig: '{l s='Démarrer la configuration de la section' d='Shop.Theme.Customconfigurator' js=1}',
  editingSection: '{l s='Modification de la section' d='Shop.Theme.Customconfigurator' js=1}',
  configComplete: '{l s='Configuration terminée' d='Shop.Theme.Customconfigurator' js=1}',
  configInProgress: '{l s='Configuration en cours' d='Shop.Theme.Customconfigurator' js=1}',
  cartSuccess: '{l s='Produits ajoutés au panier avec succès !' d='Shop.Theme.Customconfigurator' js=1}',
  cartError: '{l s='Erreur lors de l\'ajout au panier.' d='Shop.Theme.Customconfigurator' js=1}',
  serverError: '{l s='Erreur serveur' d='Shop.Theme.Customconfigurator' js=1}',
  summaryError: '{l s='Erreur lors du chargement du récapitulatif.' d='Shop.Theme.Customconfigurator' js=1}',
  summaryErrorPrefix: '{l s='Erreur lors du chargement du récapitulatif :' d='Shop.Theme.Customconfigurator' js=1}',
};
</script>
{if isset($language.iso_code) && $language.iso_code != 'fr'}
{* Catalogue FR→EN for API/DB strings — served from theme (tracked in parent repo) *}
<script src="{$urls.base_url|escape:'htmlall':'UTF-8'}themes/classic/modules/ckcconfigurateurdistriclos/views/js/cfg-catalogue-en.js"></script>
<script>
(function () {
  var cat = window.CKC_CFG_CATALOGUE;
  var i18n = window.CKC_CFG_I18N;
  if (!cat || !i18n) return;
  Object.keys(i18n).forEach(function (k) {
    var v = i18n[k];
    if (typeof v === 'string' && Object.prototype.hasOwnProperty.call(cat, v)) {
      i18n[k] = cat[v];
    }
  });
})();
</script>
{/if}

<div id="ckc-configurator"
     x-data
     x-init="$store.configurator.boot({ tunnelId: {$tunnel_id|intval}, apiUrl: '{$api_url|escape:'htmlall':'UTF-8'}' })">

  {* ======================================================
     CHARGEMENT
     ====================================================== *}
  <template x-if="$store.configurator.loading">
    <div class="ckc-loading-screen">
      <div class="ckc-spinner"></div>
      <p style="color:var(--ckc-muted);font-size:.9rem;">{l s='Chargement du configurateur…' d='Shop.Theme.Customconfigurator'}</p>
    </div>
  </template>

  {* ======================================================
     ERREUR
     ====================================================== *}
  <template x-if="!$store.configurator.loading && $store.configurator.error">
    <div class="ckc-error-screen">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <p x-text="$store.configurator.error"></p>
    </div>
  </template>

  {* ======================================================
     CONFIGURATEUR PRINCIPAL
     ====================================================== *}
  <template x-if="!$store.configurator.loading && !$store.configurator.error">
    <div class="ckc-wrapper">

      {* ---- Header ---- *}
      <header class="ckc-header">
        <div class="ckc-header__center">
          <div class="ckc-header__current-step-container">
            <span class="ckc-header__step-name" x-text="$store.configurator.currentStepName"></span>
            <span class="ckc-header__step-counter"
                  x-text="$store.configurator.currentStep + ' / ' + $store.configurator.totalSteps"></span>
          </div>
          <div :class="$store.configurator.currentStep == 1 ? 'active-step' : ''" class="ckc-header__center_step">
            <div :class="$store.configurator.currentStep >= 1 ? 'active' : ''" class="ckc-header__center_step_number">1</div>
            <div :class="$store.configurator.currentStep >= 1 ? 'active' : ''" class="ckc-header__center_step_text">{l s='Choix du type' d='Shop.Theme.Customconfigurator'}</div>
            <span :class="$store.configurator.currentStep > 1 ? 'passed' : ''" class="ckc-header__center_step-separator"></span>
          </div>
          <div :class="$store.configurator.currentStep == 2 ? 'active-step' : ''" class="ckc-header__center_step">
            <div :class="$store.configurator.currentStep >= 2 ? 'active' : ''" class="ckc-header__center_step_number">2</div>
            <div :class="$store.configurator.currentStep >= 2 ? 'active' : ''" class="ckc-header__center_step_text">{l s='Spécificités' d='Shop.Theme.Customconfigurator'}</div>
            <span :class="$store.configurator.currentStep > 2 ? 'passed' : ''" class="ckc-header__center_step-separator"></span>
          </div>
          <div :class="$store.configurator.currentStep == 3 ? 'active-step' : ''" class="ckc-header__center_step">
            <div :class="$store.configurator.currentStep >= 3 ? 'active' : ''" class="ckc-header__center_step_number">3</div>
            <div :class="$store.configurator.currentStep >= 3 ? 'active' : ''" class="ckc-header__center_step_text">{l s='Gamme' d='Shop.Theme.Customconfigurator'}</div>
            <span :class="$store.configurator.currentStep > 3 ? 'passed' : ''" class="ckc-header__center_step-separator"></span>
          </div>
          <div :class="$store.configurator.currentStep == 4 ? 'active-step' : ''" class="ckc-header__center_step">
            <div :class="$store.configurator.currentStep >= 4 ? 'active' : ''" class="ckc-header__center_step_number">4</div>
            <div :class="$store.configurator.currentStep >= 4 ? 'active' : ''" class="ckc-header__center_step_text">{l s='Dimensions' d='Shop.Theme.Customconfigurator'}</div>
            <span :class="$store.configurator.currentStep > 4 ? 'passed' : ''" class="ckc-header__center_step-separator"></span>
          </div>
          <div x-show="$store.configurator.hasSoubassementStep" :class="$store.configurator.currentStep == $store.configurator.stepSoubassement ? 'active-step' : ''" class="ckc-header__center_step">
            <div :class="$store.configurator.currentStep >= $store.configurator.stepSoubassement ? 'active' : ''" class="ckc-header__center_step_number" x-text="$store.configurator.stepSoubassement"></div>
            <div :class="$store.configurator.currentStep >= $store.configurator.stepSoubassement ? 'active' : ''" class="ckc-header__center_step_text">{l s='Soubassement' d='Shop.Theme.Customconfigurator'}</div>
            <span :class="$store.configurator.currentStep > $store.configurator.stepSoubassement ? 'passed' : ''" class="ckc-header__center_step-separator"></span>
          </div>
          <div :class="$store.configurator.currentStep == $store.configurator.stepOptions ? 'active-step' : ''" class="ckc-header__center_step">
            <div :class="$store.configurator.currentStep >= $store.configurator.stepOptions ? 'active' : ''" class="ckc-header__center_step_number" x-text="$store.configurator.stepOptions"></div>
            <div :class="$store.configurator.currentStep >= $store.configurator.stepOptions ? 'active' : ''" class="ckc-header__center_step_text">{l s='Options' d='Shop.Theme.Customconfigurator'}</div>
            <span :class="$store.configurator.currentStep > $store.configurator.stepOptions ? 'passed' : ''" class="ckc-header__center_step-separator"></span>
          </div>
          <template x-if="$store.configurator.multiTroncons">
            <div :class="$store.configurator.currentStep == $store.configurator.stepMultiTroncons ? 'active-step' : ''" class="ckc-header__center_step">
              <div :class="$store.configurator.currentStep >= $store.configurator.stepMultiTroncons ? 'active' : ''" class="ckc-header__center_step_number" x-text="$store.configurator.stepMultiTroncons"></div>
              <div :class="$store.configurator.currentStep >= $store.configurator.stepMultiTroncons ? 'active' : ''" class="ckc-header__center_step_text">{l s='Gestion sections' d='Shop.Theme.Customconfigurator'}</div>
              <span :class="$store.configurator.currentStep > $store.configurator.stepMultiTroncons ? 'passed' : ''" class="ckc-header__center_step-separator"></span>
            </div>
          </template>
          <div :class="$store.configurator.currentStep == $store.configurator.totalSteps ? 'active-step' : ''" class="ckc-header__center_step">
            <div :class="$store.configurator.currentStep >= $store.configurator.totalSteps ? 'active' : ''" class="ckc-header__center_step_number" x-text="$store.configurator.totalSteps"></div>
            <div :class="$store.configurator.currentStep >= $store.configurator.totalSteps ? 'active' : ''" class="ckc-header__center_step_text">{l s='Récapitulatif' d='Shop.Theme.Customconfigurator'}</div>
          </div>
        </div>

        {*<div class="ckc-header__right">
          {if isset($phone_number) && $phone_number}
          <a href="tel:{$phone_number|escape:'htmlall'}" class="ckc-header__phone">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.57 19.79 19.79 0 01.01 5.02 2 2 0 012 2.84h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 10.1a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
            <span class="ckc-phone-text">{$phone_number|escape:'htmlall'}</span>
          </a>
          {/if}
        </div>*}
      </header>

      {* ---- Zone de contenu ---- *}
      <div class="ckc-layout" :class="{ 'ckc-layout--full': $store.configurator.currentStep === 1 }">
      <main class="ckc-content">

        {* ==================================================
           ÉTAPE 1 — Type de clôture
           ================================================== *}
        <div x-show="$store.configurator.currentStep === 1">
          <h1>{l s='Choisissez votre type de clôture' d='Shop.Theme.Customconfigurator'}</h1>

          <h2 class="ckc-step-heading">{l s='Sélectionnez le type de clôture qui convient le mieux à votre projet' d='Shop.Theme.Customconfigurator'}</h2>

          {*<template x-if="$store.configurator.infoTexts">
            <div class="ckc-info-banner">
              <template x-if="$store.configurator.infoTexts.hauteur">
                <div class="ckc-info-item">
                  <span class="ckc-info-item__icon"><img src="/modules/ckcconfigurateurdistriclos/views/img/ckc-icon-hauteur.svg" alt="icon hauteur" width="16" height="16" ></span>
                  <span class="ckc-info-item__label">Hauteur</span>
                  <span x-text="$store.configurator.infoTexts.hauteur"></span>
                </div>
              </template>
              <template x-if="$store.configurator.infoTexts.garantie">
                <div class="ckc-info-item">
                  <span class="ckc-info-item__icon"><img src="/modules/ckcconfigurateurdistriclos/views/img/ckc-icon-garanties.svg" alt="icon garanties" width="16" height="16" ></span>
                  <span class="ckc-info-item__label">Garantie</span>
                  <span x-text="$store.configurator.infoTexts.garantie"></span>
                </div>
              </template>
              <template x-if="$store.configurator.infoTexts.installation">
                <div class="ckc-info-item">
                  <span class="ckc-info-item__icon"><img src="/modules/ckcconfigurateurdistriclos/views/img/ckc-icon-installation.svg" alt="icon installation" width="16" height="16" ></span>
                  <span class="ckc-info-item__label">Installation</span>
                  <span x-text="$store.configurator.infoTexts.installation"></span>
                </div>
              </template>
            </div>
          </template>*}

          {* Conseil d'expert étape 1 *}
          <template x-if="$store.configurator.stepAdvice['step_1'] && $store.configurator.stepAdvice['step_1'].active && $store.configurator.stepAdvice['step_1'].content">
            <div class="ckc-expert">
              <div class="ckc-icon-conseils-text-container">
                <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-expert.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="27" height="27" ></div>
                <div class="ckc-expert__body">
                  <p class="ckc-expert__title_text"><strong x-text="$store.configurator.stepAdvice['step_1'].title" x-show="$store.configurator.stepAdvice['step_1'].title"></strong></p>
                  <p class="ckc-expert__text" x-html="$store.configurator.stepAdvice['step_1'].content"></p>
                </div>
              </div>
              <div class="ckc-icon-conseils-img-container">
                <img class="ckc-icon-conseils-masques" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-masques.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="22" height="16" >
                <img class="ckc-icon-conseils-affiches" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-affiches.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="24" height="24" >
              </div>
            </div>
          </template>

          {* Toggle plusieurs sections *}
          <div
                  class="ckc-multitroncon-check"
                  :class="{ 'checked': $store.configurator.multiTroncons }"
                  @click="$store.configurator.multiTroncons = !$store.configurator.multiTroncons"
          >
            <label class="ckc-switch-troncon" @click.stop>
              <input type="checkbox"
                     :checked="$store.configurator.multiTroncons"
                     @change="$store.configurator.multiTroncons = !$store.configurator.multiTroncons">
                    <span class="checkbox"></span>
            </label>
            <div>
              <strong>{l s='J\'ai plusieurs hauteurs de clôture' d='Shop.Theme.Customconfigurator'}</strong>
              <div><span>{l s='Activez cette option si votre projet nécessite différentes hauteurs sur plusieurs sections' d='Shop.Theme.Customconfigurator'}</span></div>
            </div>
          </div>

          <div class="ckc-grid">
            <template x-for="item in $store.configurator.fenceTypes" :key="item.id">
              <div
                class="ckc-card"
                :class="{ 'ckc-card--selected': $store.configurator.selection.fenceTypeId === item.id }"
                @click="$store.configurator.selectFenceType(item.id)"
                role="button"
                :aria-pressed="$store.configurator.selection.fenceTypeId === item.id"
              >
                <div class="ckc-card__row">
                  <div class="ckc-card__img-wrap">
                    <template x-if="item.promo_message">
                      <span class="ckc-card__badge_promo" x-text="item.promo_message"></span>
                    </template>
                    <template x-if="item.image_url">
                      <img :src="item.image_url" :alt="item.title" loading="lazy">
                    </template>
                  </div>

                  <div class="ckc-card__body">
                    <p class="ckc-card__title" x-text="item.title"></p>
                    <p class="ckc-card__subtitle" x-text="item.subtitle" x-show="item.subtitle"></p>
                    <p class="ckc-card__desc" x-text="item.description" x-show="item.description"></p>
                  </div>
                </div>

                <button type="button" class="ckc-configurateur-item-select-btn">{l s='Sélectionner' d='Shop.Theme.Customconfigurator'}</button>
              </div>
            </template>
          </div>
        </div>

        {* ==================================================
           ÉTAPE 4 — Dimensions
           ================================================== *}
        <div x-show="$store.configurator.currentStep === 4">
          <template x-if="$store.configurator.troncons.length > 1">
            <div class="ckc-editing-badge">
              {l s='Modification de la section' d='Shop.Theme.Customconfigurator'} <strong x-text="$store.configurator.currentTroncon + 1"></strong> / <span x-text="$store.configurator.troncons.length"></span>
            </div>
          </template>
          <h2 class="h2">{l s='Dimensions' d='Shop.Theme.Customconfigurator'}</h2>
          <div class="ckc-step-heading">{l s='Définissez les caractéristiques techniques de votre clôture' d='Shop.Theme.Customconfigurator'}</div>

          {* Conseil d'expert étape 2 *}
          <template x-if="$store.configurator.stepAdvice['step_2'] && $store.configurator.stepAdvice['step_2'].active && $store.configurator.stepAdvice['step_2'].content">
            <div class="ckc-expert">
              <div class="ckc-icon-conseils-text-container">
                <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-expert.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="27" height="27" ></div>
                <div class="ckc-expert__body">
                  <p class="ckc-expert__title_text"><strong x-text="$store.configurator.stepAdvice['step_2'].title" x-show="$store.configurator.stepAdvice['step_2'].title"></strong></p>
                  <p class="ckc-expert__text" x-html="$store.configurator.stepAdvice['step_2'].content"></p>
                </div>
              </div>
              <div class="ckc-icon-conseils-img-container">
                <img class="ckc-icon-conseils-masques" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-masques.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="22" height="16" >
                <img class="ckc-icon-conseils-affiches" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-affiches.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="24" height="24" >
              </div>
            </div>
          </template>

          {* Toggle plusieurs sections *}
          {*<div
                  class="ckc-multitroncon-check"
                  :class="{ 'checked': $store.configurator.multiTroncons }"
                  @click="$store.configurator.multiTroncons = !$store.configurator.multiTroncons"
          >
            <label class="ckc-switch-troncon" @click.stop>
              <input type="checkbox"
                     :checked="$store.configurator.multiTroncons"
                     @change="$store.configurator.multiTroncons = !$store.configurator.multiTroncons">
              *}{*<div class="ckc-switch__track"></div>*}{*
            </label>
            <div>
              <strong>J'ai plusieurs hauteurs de clôture</strong>
              <div><span>Activez cette option si votre projet nécessite différentes hauteurs sur plusieurs tronçons</span></div>
            </div>
          </div>*}

          {*<hr class="ckc-divider">*}

          <div class="ckc-etape-selection-container">
            <div class="ckc-etape-selection-title">{l s='Dimensions de la section' d='Shop.Theme.Customconfigurator'}</div>
              <div class="ckc-etape-deux-fields-container">
              {* Longueur *}
                <div class="ckc-field-group">
                  <span class="ckc-field-label">{l s='Longueur de la section' d='Shop.Theme.Customconfigurator'}</span>
                  <div class="ckc-stepper">
    {*                <button class="ckc-stepper__btn"
                            @click="$store.configurator.stepperDec('longueur', 0.5, 0.5)">−</button>*}
                    <input
                            class="ckc-stepper__input"
                            type="number"
                            :min="$store.configurator.currentMinLength"
                            :step="$store.configurator.currentLengthStep"
                            x-model.number="$store.configurator.currentTronconData().longueur"
                            @change="$store.configurator.snapLength()"
                    >
    {*                <button class="ckc-stepper__btn"
                            @click="$store.configurator.stepperInc('longueur', 0.5)">+</button>*}
                    <span class="ckc-stepper__unit">{l s='mètres' d='Shop.Theme.Customconfigurator'}</span>
                  </div>
                </div>

                {* Angles *}
                <div class="ckc-field-group">
                  <span class="ckc-field-label">{l s='Nombre d\'angles > 90°' d='Shop.Theme.Customconfigurator'}</span>
                  <div class="ckc-angles">
                    <select name="ckc-angles" id="ckc-angles" x-model.number="$store.configurator.currentTronconData().nbAngles">

                    <template x-for="n in [0,1,2,3,4,5]" :key="n">
                      <option :value="n" x-text="n"></option>
    {*                  <label class="ckc-angle-item">
                        <input type="radio" :name="'ckc-angles-' + $store.configurator.currentTroncon"
                               :value="n"
                               x-model.number="$store.configurator.currentTronconData().nbAngles">
                        <div class="ckc-angle-circle" x-text="n"></div>
                        <span class="ckc-angle-label" x-text="n === 0 ? ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.none)||'Aucun') : (n === 1 ? ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.angle)||'angle') : ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.angles)||'angles'))"></span>
                      </label>*}
                    </template>
                    </select>
                  </div>
                </div>
              </div>
                {* Hauteur *}
                <div class="ckc-field-group" x-show="$store.configurator.heights.length > 0">
                  <span class="ckc-field-label" x-text="$store.configurator.selectedFenceType?.height_mode === 'packs' ? ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.heights)||'Hauteurs') : ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.fenceHeight)||'Hauteur de clôture')"></span>
                  <div class="ckc-heights">
                    <template x-for="h in $store.configurator.heights" :key="h.id">
                      <button
                              class="ckc-height-pill"
                              :class="{ 'selected': $store.configurator.currentTronconData().heightAttrId === h.id }"
                              @click="$store.configurator.currentTronconData().heightAttrId = h.id; $store.configurator.computePrice()"
                      ><span x-text="h.label"></span></button>
                    </template>
                  </div>
                </div>
          </div>


        </div>

        {* ==================================================
           ÉTAPE 2 — Spécificités
           ================================================== *}
        <div x-show="$store.configurator.currentStep === 2">
          <template x-if="$store.configurator.troncons.length > 1">
            <div class="ckc-editing-badge">
              {l s='Modification de la section' d='Shop.Theme.Customconfigurator'} <strong x-text="$store.configurator.currentTroncon + 1"></strong> / <span x-text="$store.configurator.troncons.length"></span>
            </div>
          </template>

          <div class="ckc-field-group">
            <h2 class="h2">{l s='Spécificités' d='Shop.Theme.Customconfigurator'}</h2>
            <div class="ckc-step-heading">{l s='Sélectionnez le type de pose et indiquez les connexions murales de votre clôture' d='Shop.Theme.Customconfigurator'}</div>

            {* Type de pose *}
            <div class="ckc-field-group" x-show="$store.configurator.installTypes.length > 0">
              <span class="ckc-field-label ckc-etape-selection-title">{l s='Type de pose' d='Shop.Theme.Customconfigurator'}</span>
              <div class="ckc-grid ckc-grid--2">
                <template x-for="item in $store.configurator.installTypes" :key="item.id">
                  <div
                          class="ckc-card"
                          :class="{ 'ckc-card--selected': $store.configurator.currentTronconData().installTypeId === item.id }"
                          @click="$store.configurator.selectInstallType(item.id)"
                  >
                    <div class="ckc-card__check">✓</div>
                    <div class="ckc-card__img-wrap">
                      <template x-if="item.image_url">
                        <img :src="item.image_url" :alt="item.title" loading="lazy">
                      </template>
                    </div>
                    <div class="ckc-card__body">
                      <p class="ckc-card__title" x-text="item.title"></p>
                      <p class="ckc-card__subtitle" x-text="item.subtitle" x-show="item.subtitle"></p>
                      <p class="ckc-card__desc" x-text="item.description" x-show="item.description"></p>

                      <template x-if="(item.info_items || []).length > 0">
                        <div class="ckc-info-item-container">
                          <template x-for="(info, infoIdx) in (item.info_items || [])" :key="infoIdx">
                            <div class="ckc-info-item-inside">
                              <span class="ckc-info-item-inside__icon"><img :src="info.icon_url" :alt="info.label" width="16" height="16" x-show="info.icon_url"></span>
                              <span class="ckc-info-item-inside__label" x-text="info.label"></span>
                              <span x-text="info.text"></span>
                            </div>
                          </template>
                        </div>
                      </template>

                      <template x-if="item.price_base !== null && item.price_base !== undefined">
                        <div class="ckc-card__price">
                          {*<span class="ckc-price-from" x-show="item.show_from_price">À partir de</span>*}
                          <strong class="ckc-price-main"
                                  x-text="((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.fromPrice)||'À partir de') + ' ' + (item.price_base || 0).toFixed(2) + ' €'"></strong>
                          <s class="ckc-price-old" x-show="item.price_promo !== null && item.price_promo !== undefined"
                             x-text="item.price_promo ? item.price_promo.toFixed(2) + ' €' : ''"></s>
                        </div>
                      </template>

                      <template x-if="item.promo_message">
                        <span class="ckc-card__badge_promo" x-text="item.promo_message"></span>
                      </template>

                      <template x-if="item.expert_advice && item.expert_advice.active && item.expert_advice.content">
                        <div class="ckc-expert" style="margin:.75rem 0 0;padding:.6rem .875rem;">
                          <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-expert.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="27" height="27" ></div>
                          <p class="ckc-expert__text" x-text="item.expert_advice.content"></p>
                        </div>
                      </template>
                      <button class="ckc-configurateur-item-select-btn">{l s='Sélectionner' d='Shop.Theme.Customconfigurator'}</button>
                    </div>
                  </div>
                </template>
              </div>
            </div>

            {* Connexions murales *}
            <div class="ckc-field-group">
              <span class="ckc-field-label ckc-etape-selection-title">{l s='Connexions murales' d='Shop.Theme.Customconfigurator'}</span>
              <div class="ckc-wall-options">
                <div
                  class="ckc-wall-item"
                  :class="{ 'selected': $store.configurator.currentTronconData().startOnWall }"
                  @click="$store.configurator.currentTronconData().startOnWall = !$store.configurator.currentTronconData().startOnWall"
                >
                  <label class="ckc-switch" @click.stop>
                    <input type="checkbox"
                           :checked="$store.configurator.currentTronconData().startOnWall"
                           @change="$store.configurator.currentTronconData().startOnWall = !$store.configurator.currentTronconData().startOnWall">
                    <span class="checkbox"></span>
                  </label>
                  <div>
                    <span><strong>{l s='La clôture commence sur un mur' d='Shop.Theme.Customconfigurator'}</strong></span>
                    <div>
                      <span>{l s='Cochez si votre clôture démarre depuis un mur existant (maison, garage, muret, etc...)' d='Shop.Theme.Customconfigurator'}</span>
                    </div>
                  </div>
                </div>
                <div
                  class="ckc-wall-item"
                  :class="{ 'selected': $store.configurator.currentTronconData().endOnWall }"
                  @click="$store.configurator.currentTronconData().endOnWall = !$store.configurator.currentTronconData().endOnWall"
                >
                  <label class="ckc-switch" @click.stop>
                    <input type="checkbox"
                           :checked="$store.configurator.currentTronconData().endOnWall"
                           @change="$store.configurator.currentTronconData().endOnWall = !$store.configurator.currentTronconData().endOnWall">
                    <span class="checkbox"></span>
                  </label>
                  <div>
                    <span><strong>{l s='La clôture se termine sur un mur' d='Shop.Theme.Customconfigurator'}</strong></span>
                    <div>
                      <span>{l s='Cochez si votre clôture se termine contre un mur existant (maison, garage, muret, etc...)' d='Shop.Theme.Customconfigurator'}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {* ==================================================
           ÉTAPE 3 — Gamme
           ================================================== *}
        <div x-show="$store.configurator.currentStep === 3">
          <template x-if="$store.configurator.troncons.length > 1">
            <div class="ckc-editing-badge">
              {l s='Modification de la section' d='Shop.Theme.Customconfigurator'} <strong x-text="$store.configurator.currentTroncon + 1"></strong> / <span x-text="$store.configurator.troncons.length"></span>
            </div>
          </template>
          <h2 class="h2">{l s='Gamme' d='Shop.Theme.Customconfigurator'}</h2>
          <div class="ckc-step-heading">{l s='Sélectionnez la gamme de produits et personnalisez l\'apparence' d='Shop.Theme.Customconfigurator'}</div>

          {* Conseil d'expert étape 3 *}
          <template x-if="$store.configurator.stepAdvice['step_3'] && $store.configurator.stepAdvice['step_3'].active && $store.configurator.stepAdvice['step_3'].content">
            <div class="ckc-expert">
              <div class="ckc-icon-conseils-text-container">
                <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-expert.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="27" height="27" ></div>
                <div class="ckc-expert__body">
                  <p class="ckc-expert__title_text"><strong x-text="$store.configurator.stepAdvice['step_3'].title" x-show="$store.configurator.stepAdvice['step_3'].title"></strong></p>
                  <p class="ckc-expert__text" x-html="$store.configurator.stepAdvice['step_3'].content"></p>
                </div>
              </div>
              <div class="ckc-icon-conseils-img-container">
                <img class="ckc-icon-conseils-masques" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-masques.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="22" height="16" >
                <img class="ckc-icon-conseils-affiches" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-affiches.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="24" height="24" >
              </div>
            </div>
          </template>

          {* Gammes disponibles *}
          <div class="ckc-field-group" x-show="$store.configurator.currentTronconData().installTypeId">
            <div class="ckc-grid">
              <template x-for="r in $store.configurator.ranges" :key="r.id">
                <div
                  class="ckc-card"
                  :class="{ 'ckc-card--selected': $store.configurator.currentTronconData().rangeId === r.id }"
                  @click="$store.configurator.selectRange(r.id)"
                >
                  <div class="ckc-card__check">✓</div>
                  <div class="ckc-card__img-wrap">
                    <template x-if="r.image_url">
                      <img :src="r.image_url" :alt="r.title" loading="lazy">
                    </template>
                  </div>
                  <div class="ckc-card__body">
                    <p class="ckc-card__title" x-text="r.title"></p>
                    <p class="ckc-card__subtitle" x-text="r.subtitle" x-show="r.subtitle"></p>
                    <p class="ckc-card__desc" x-text="r.description" x-show="r.description"></p>

                    <template x-if="(r.info_items || []).length > 0">
                      <div class="ckc-info-item-container">
                        <template x-for="(info, infoIdx) in (r.info_items || [])" :key="infoIdx">
                          <div class="ckc-info-item-inside">
                            <span class="ckc-info-item-inside__icon"><img :src="info.icon_url" :alt="info.label" width="16" height="16" x-show="info.icon_url"></span>
                            <span class="ckc-info-item-inside__label" x-text="info.label"></span>
                            <span x-text="info.text"></span>
                          </div>
                        </template>
                      </div>
                    </template>

                    <template x-if="r.tech_specs && r.tech_specs.length">
                      <ul class="ckc-specs">
                        <template x-for="spec in r.tech_specs" :key="spec.text">
                          <li><span x-show="spec.icon" x-text="spec.icon"></span><span x-text="spec.text"></span></li>
                        </template>
                      </ul>
                    </template>

                    <template x-if="r.price_base !== null && r.price_base !== undefined">
                      <div class="ckc-card__price">
                        {*<span class="ckc-price-from" x-show="r.show_from_price">À partir de</span>*}
                        <strong class="ckc-price-main" x-text="((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.fromPrice)||'À partir de') + ' ' + (r.price_base || 0).toFixed(2) + ' €'"></strong>
                        <s class="ckc-price-old" x-show="r.price_promo !== null && r.price_promo !== undefined"
                           x-text="r.price_promo ? r.price_promo.toFixed(2) + ' €' : ''"></s>
                      </div>
                    </template>

                    <template x-if="r.promo_message">
                      <span class="ckc-card__badge_promo" x-text="r.promo_message"></span>
                    </template>

                    <template x-if="r.expert_advice && r.expert_advice.active && r.expert_advice.content">
                      <div class="ckc-expert" style="margin:.75rem 0 0;padding:.6rem .875rem;">
                        <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-expert.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="27" height="27" ></div>
                        <p class="ckc-expert__text" x-text="r.expert_advice.content"></p>
                      </div>
                    </template>
                    <button class="ckc-configurateur-item-select-btn">{l s='Sélectionner' d='Shop.Theme.Customconfigurator'}</button>
                  </div>
                </div>
              </template>
              <template x-for="r in $store.configurator.notAvailablesRanges" :key="r.id">
                <div
                        class="ckc-card ckc-not-available-range"
                >
                  <div class="ckc-not-available-range-message">{l s='Non disponible pour cette hauteur' d='Shop.Theme.Customconfigurator'}</div>
                  <div class="ckc-card__check">✓</div>
                  <div class="ckc-card__img-wrap">
                    <template x-if="r.image_url">
                      <img :src="r.image_url" :alt="r.title" loading="lazy">
                    </template>
                  </div>
                  <div class="ckc-card__body">
                    <p class="ckc-card__title" x-text="r.title"></p>
                    <p class="ckc-card__subtitle" x-text="r.subtitle" x-show="r.subtitle"></p>
                    <p class="ckc-card__desc" x-text="r.description" x-show="r.description"></p>

                    <template x-if="(r.info_items || []).length > 0">
                      <div class="ckc-info-item-container">
                        <template x-for="(info, infoIdx) in (r.info_items || [])" :key="infoIdx">
                          <div class="ckc-info-item-inside">
                            <span class="ckc-info-item-inside__icon"><img :src="info.icon_url" :alt="info.label" width="16" height="16" x-show="info.icon_url"></span>
                            <span class="ckc-info-item-inside__label" x-text="info.label"></span>
                            <span x-text="info.text"></span>
                          </div>
                        </template>
                      </div>
                    </template>

                    <template x-if="r.tech_specs && r.tech_specs.length">
                      <ul class="ckc-specs">
                        <template x-for="spec in r.tech_specs" :key="spec.text">
                          <li><span x-show="spec.icon" x-text="spec.icon"></span><span x-text="spec.text"></span></li>
                        </template>
                      </ul>
                    </template>

                    <template x-if="r.price_base !== null && r.price_base !== undefined">
                      <div class="ckc-card__price">
                        {*<span class="ckc-price-from" x-show="r.show_from_price">À partir de</span>*}
                        <strong class="ckc-price-main" x-text="((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.fromPrice)||'À partir de') + ' ' + (r.price_base || 0).toFixed(2) + ' €'"></strong>
                        <s class="ckc-price-old" x-show="r.price_promo !== null && r.price_promo !== undefined"
                           x-text="r.price_promo ? r.price_promo.toFixed(2) + ' €' : ''"></s>
                      </div>
                    </template>

                    <template x-if="r.promo_message">
                      <span class="ckc-card__badge_promo" x-text="r.promo_message"></span>
                    </template>

                    <template x-if="r.expert_advice && r.expert_advice.active && r.expert_advice.content">
                      <div class="ckc-expert" style="margin:.75rem 0 0;padding:.6rem .875rem;">
                        <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-expert.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="27" height="27" ></div>
                        <p class="ckc-expert__text" x-text="r.expert_advice.content"></p>
                      </div>
                    </template>
                    <button class="ckc-configurateur-item-select-btn">{l s='Sélectionner' d='Shop.Theme.Customconfigurator'}</button>
                  </div>
                </div>
              </template>
            </div>
          </div>

          {* Type de poteau (depuis attributs PS) *}
          <div class="ckc-field-group ckc-etape-selection-container" x-show="$store.configurator.postTypes.length > 0">
           {* <hr class="ckc-divider">*}
            <span class="ckc-field-label ckc-etape-selection-title">{l s='Type de poteaux' d='Shop.Theme.Customconfigurator'}</span>
            <div class="{*ckc-heights *}ckc-wall-options">
              <template x-for="pt in $store.configurator.postTypes" :key="pt.id">
                <div
                        class="ckc-wall-item ckc-wall-item--post"
                        :class="{ 'selected': $store.configurator.currentTronconData().postTypeId === pt.id }"
                        @click="$store.configurator.selectPostType(pt.id)"
                >
                  <label class="ckc-switch" @click.stop>
                    <input type="checkbox"
                         :checked="$store.configurator.currentTronconData().postTypeId === pt.id"
                         @change="$store.configurator.selectPostType(pt.id)">
                    <span class="checkbox"></span>
                  </label>
                  <div>
                    <span><strong x-text="pt.title"></strong></span>
                    <div>
                      <span x-text="pt.description"></span>
                    </div>
                  </div>
                </div>
{*                <button
                        class="ckc-height-pill"
                        :class="{ 'selected': $store.configurator.currentTronconData().postTypeId === pt.id }"
                        @click="$store.configurator.selectPostType(pt.id)"
                        x-text="pt.title"
                ></button>*}
              </template>
            </div>
          </div>

          {* Couleurs (après sélection gamme, depuis attributs PS) *}
          <template x-if="$store.configurator.currentTronconData().rangeId && $store.configurator.colors.length > 0">
            <div class="ckc-field-group ckc-etape-selection-container">
              {*<hr class="ckc-divider">*}
              <span class="ckc-field-label ckc-etape-selection-title">{l s='Choix du coloris' d='Shop.Theme.Customconfigurator'}</span>
              <div class="ckc-heights">
                <template x-for="c in $store.configurator.colors" :key="c.id">
                  <div class="ckc-color-container"
                       :class="{ 'selected': $store.configurator.currentTronconData().colorAttrId === c.id }"
                       @click="$store.configurator.currentTronconData().colorAttrId = c.id; $store.configurator.computePrice()">
                    <div class="ckc-color-selected-icon">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <rect width="16" height="16" rx="8" fill="#7CBA49"/>
                        <path d="M11.5162 5.0002C11.3941 5.00377 11.2782 5.05385 11.1931 5.13982L6.35636 9.88676L4.80896 8.3681C4.76566 8.32384 4.7138 8.2885 4.65641 8.26416C4.59902 8.23982 4.53726 8.22695 4.47474 8.22633C4.41222 8.22571 4.35021 8.23733 4.29232 8.26052C4.23444 8.28372 4.18186 8.31801 4.13765 8.3614C4.09344 8.40479 4.0585 8.45639 4.03486 8.5132C4.01123 8.57001 3.99939 8.63087 4.00002 8.69223C4.00066 8.75358 4.01376 8.8142 4.03857 8.87052C4.06337 8.92685 4.09938 8.97775 4.14448 9.02024L6.02412 10.865C6.11225 10.9514 6.23175 11 6.35636 11C6.48096 11 6.60047 10.9514 6.6886 10.865L11.8576 5.79196C11.9255 5.72718 11.9719 5.64378 11.9907 5.55269C12.0094 5.46159 11.9997 5.36706 11.9628 5.28147C11.9259 5.19588 11.8635 5.12323 11.7838 5.07304C11.7041 5.02285 11.6108 4.99746 11.5162 5.0002Z" fill="white"/>
                      </svg>
                    </div>
                    <div class="ckc-color"
                         :class="{ 'ckc-color--white': /blanc/i.test(c.label || '') }"
                         :style="c.color ? 'background-color:' + c.color : ''"></div>
                    <div class="ckc-color-container-text">
                      <button
                              class="ckc-color-pill"
                              x-text="c.label"
                      ></button>
                    </div>

                  </div>
                </template>
              </div>
              <div class="ckc-apercu-coloris-info">
                <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-coloris-info.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="20" height="20" ></div>
                <div class="ckc-expert__body">
                  <p class="ckc-expert__title_text"><strong>{l s='Aperçu des coloris' d='Shop.Theme.Customconfigurator'}</strong></p>
                  <p class="ckc-expert__text">{l s='Les couleurs affichées peuvent varier légèrement selon votre écran.' d='Shop.Theme.Customconfigurator'}</p>
                </div>
              </div>
            </div>
          </template>

        </div>

        {* ==================================================
           ÉTAPE SOUBASSEMENT (dynamique)
           ================================================== *}
        <div x-show="$store.configurator.hasSoubassementStep && $store.configurator.currentStep === $store.configurator.stepSoubassement">
          <template x-if="$store.configurator.troncons.length > 1">
            <div class="ckc-editing-badge">
              {l s='Modification de la section' d='Shop.Theme.Customconfigurator'} <strong x-text="$store.configurator.currentTroncon + 1"></strong> / <span x-text="$store.configurator.troncons.length"></span>
            </div>
          </template>
          <h2 class="h2">{l s='Personnalisez le soubassement de votre clôture' d='Shop.Theme.Customconfigurator'}</h2>
          <div class="ckc-step-heading">{l s='Sélectionnez les options de soubassement que vous souhaitez ajouter à votre clôture.' d='Shop.Theme.Customconfigurator'}</div>

          {* Conseil d'expert étape soubassement *}
          <template x-if="$store.configurator.stepAdvice['step_soubassement'] && $store.configurator.stepAdvice['step_soubassement'].active && $store.configurator.stepAdvice['step_soubassement'].content">
            <div class="ckc-expert">
              <div class="ckc-icon-conseils-text-container">
                <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-expert.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="27" height="27" ></div>
                <div class="ckc-expert__body">
                  <p class="ckc-expert__title_text"><strong x-text="$store.configurator.stepAdvice['step_soubassement'].title" x-show="$store.configurator.stepAdvice['step_soubassement'].title"></strong></p>
                  <p class="ckc-expert__text" x-html="$store.configurator.stepAdvice['step_soubassement'].content"></p>
                </div>
              </div>
              <div class="ckc-icon-conseils-img-container">
                <img class="ckc-icon-conseils-masques" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-masques.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="22" height="16" >
                <img class="ckc-icon-conseils-affiches" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-affiches.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="24" height="24" >
              </div>
            </div>
          </template>

          <template x-if="$store.configurator.soubassements.length === 0">
            <p style="color:var(--ckc-muted);font-size:.9rem;">{l s='Aucun soubassement disponible pour ce tunnel.' d='Shop.Theme.Customconfigurator'}</p>
          </template>

          <div class="ckc-grid" style="margin-bottom: 25px;">
            <template x-for="s in $store.configurator.soubassements" :key="s.id">
              <div
                      class="ckc-card"
                      :class="{ 'ckc-card--selected': $store.configurator.currentTronconData().soubassements[s.id] }"
                      @click="$store.configurator.openSoubassementPanel(s.id)"
              >
              {*<template x-if="s.promo_message">
                  <span class="ckc-card__badge" x-text="s.promo_message"></span>
                </template>*}
                <div class="ckc-card__img-wrap">
                  <template x-if="s.image_url">
                    <img :src="s.image_url" :alt="s.title" loading="lazy">
                  </template>
                </div>
                <div class="ckc-card__body">
                  <p class="ckc-card__title" x-text="s.title"></p>
                  <p class="ckc-card__subtitle" x-text="s.subtitle" x-show="s.subtitle"></p>
                  <p class="ckc-card__desc" x-html="s.description" x-show="s.description"></p>
                  <template x-if="s.price_base !== null && s.price_base !== undefined">
                    <div class="ckc-card__price">
                      <strong class="ckc-price-main" x-text="s.show_from_price ? (((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.fromPrice)||'À partir de') + ' ' + (s.price_base || 0).toFixed(2) + ' €') : ('+ ' + (s.price_base || 0).toFixed(2) + ' €')"></strong>
                    </div>
                  </template>
                  <template x-if="s.promo_message">
                    <span class="ckc-card__badge_promo" x-text="s.promo_message"></span>
                  </template>
                  <template x-if="!$store.configurator.currentTronconData().soubassements[s.id]">
                    <button class="ckc-configurateur-item-select-btn">{l s='Sélectionner' d='Shop.Theme.Customconfigurator'}</button>
                  </template>
                  <template x-if="$store.configurator.currentTronconData().soubassements[s.id] && $store.configurator.soubHasAttributes(s.id)">
                    <div class="ckc-card__actions">
                      <button class="ckc-configurateur-item-select-btn" @click.stop="$store.configurator.openSoubassementPanel(s.id)">{l s='Modifier' d='Shop.Theme.Customconfigurator'}</button>
                      <button class="ckc-configurateur-item-remove-btn" @click.stop="$store.configurator.removeSoubassement(s.id)">{l s='Retirer' d='Shop.Theme.Customconfigurator'}</button>
                    </div>
                  </template>
                </div>
              </div>
  {*            <div
                class="ckc-option-card"
                :class="{ 'selected': $store.configurator.currentTronconData().soubassements[s.id] }"
                @click="$store.configurator.toggleOption('soubassements', s.id)"
              >
                <template x-if="s.image_url">
                  <img class="ckc-option-card__img" :src="s.image_url" :alt="s.title" loading="lazy">
                </template>
                <div class="ckc-option-card__body">
                  <p class="ckc-option-card__title" x-text="s.title"></p>
                  <p class="ckc-option-card__subtitle" x-text="s.subtitle" x-show="s.subtitle"></p>
                  <template x-if="s.price_base !== null && s.price_base !== undefined">
                    <p class="ckc-option-card__price"
                      x-text="s.show_from_price ? (((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.fromPrice)||'À partir de') + ' ' + (s.price_base || 0).toFixed(2) + ' €') : ('+ ' + (s.price_base || 0).toFixed(2) + ' €')"></p>
                  </template>
                  <p class="ckc-option-card__promo" x-text="s.promo_message" x-show="s.promo_message"></p>
                </div>
                <div class="ckc-option-card__toggle">
                  <label class="ckc-switch" @click.stop>
                    <input type="checkbox"
                           :checked="$store.configurator.currentTronconData().soubassements[s.id]"
                           @change="$store.configurator.toggleOption('soubassements', s.id)">
                    <div class="ckc-switch__track"></div>
                  </label>
                </div>
              </div>*}
            </template>
          </div>

          {* Soubassement configuration panel (same pattern as options) *}
          <div class="ckc-configurator-options-selection-container" :class="{ 'ckc-configurator-options-selection-open' : $store.configurator.soubPanelOpen }">
            <div class="ckc-configurator-options-selection-close" @click="$store.configurator.soubPanelOpen = false">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M10.2929 0.292893C10.6834 -0.0976311 11.3164 -0.0976311 11.707 0.292893C12.0975 0.683417 12.0975 1.31643 11.707 1.70696L7.41399 5.99992L11.707 10.2929C12.0975 10.6834 12.0975 11.3164 11.707 11.707C11.3164 12.0975 10.6834 12.0975 10.2929 11.707L5.99992 7.41399L1.70696 11.707C1.31643 12.0975 0.683417 12.0975 0.292893 11.707C-0.0976311 11.3164 -0.0976311 10.6834 0.292893 10.2929L4.58586 5.99992L0.292893 1.70696C-0.0976311 1.31643 -0.0976311 0.683417 0.292893 0.292893C0.683417 -0.0976311 1.31643 -0.0976311 1.70696 0.292893L5.99992 4.58586L10.2929 0.292893Z" fill="black"/>
              </svg>
            </div>
            <div class="ckc-configurator-options-selection-title" x-text="((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.configurePrefix)||'Paramétrer :') + ' ' + ($store.configurator.soubPanelSoubassement?.title || ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.soubassement)||'Soubassement'))"></div>

            <template x-if="$store.configurator.soubPanelLoading">
              <div style="text-align:center;padding:2rem;color:var(--ckc-muted);">
                <div class="ckc-spinner" style="width:24px;height:24px;margin:0 auto .5rem;"></div>
                {l s='Chargement des déclinaisons…' d='Shop.Theme.Customconfigurator'}
              </div>
            </template>

            <template x-if="!$store.configurator.soubPanelLoading && $store.configurator.soubPanelGroups.length === 0">
              <div style="padding:1rem;color:var(--ckc-muted);font-size:.9rem;">{l s='Aucune déclinaison disponible.' d='Shop.Theme.Customconfigurator'}</div>
            </template>

            <template x-for="group in $store.configurator.soubPanelGroups.filter(g => g.attributes.length > 1)" :key="group.id_attribute_group">
              <div class="ckc-configurator-options-selection-options-container">
                <div class="ckc-configurator-options-selection-subtitle" x-text="group.name + ' :'"></div>
                <template x-if="group.group_type === 'color'">
                  <div class="ckc-configurator-options-selection-options-color">
                    <template x-for="attr in group.attributes" :key="attr.id_attribute">
                      <div class="ckc-configurator-options-selection-option-color"
                           :class="{ 'ckc-configurator-options-selected-option-color': $store.configurator.soubPanelSelections[group.id_attribute_group] === attr.id_attribute }"
                           @click="$store.configurator.selectSoubPanelAttribute(group.id_attribute_group, attr.id_attribute)">
                        <div class="ckc-color-selected-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 16 16" fill="none">
                            <rect width="16" height="16" rx="8" fill="#7CBA49"></rect>
                            <path d="M11.5162 5.0002C11.3941 5.00377 11.2782 5.05385 11.1931 5.13982L6.35636 9.88676L4.80896 8.3681C4.76566 8.32384 4.7138 8.2885 4.65641 8.26416C4.59902 8.23982 4.53726 8.22695 4.47474 8.22633C4.41222 8.22571 4.35021 8.23733 4.29232 8.26052C4.23444 8.28372 4.18186 8.31801 4.13765 8.3614C4.09344 8.40479 4.0585 8.45639 4.03486 8.5132C4.01123 8.57001 3.99939 8.63087 4.00002 8.69223C4.00066 8.75358 4.01376 8.8142 4.03857 8.87052C4.06337 8.92685 4.09938 8.97775 4.14448 9.02024L6.02412 10.865C6.11225 10.9514 6.23175 11 6.35636 11C6.48096 11 6.60047 10.9514 6.6886 10.865L11.8576 5.79196C11.9255 5.72718 11.9719 5.64378 11.9907 5.55269C12.0094 5.46159 11.9997 5.36706 11.9628 5.28147C11.9259 5.19588 11.8635 5.12323 11.7838 5.07304C11.7041 5.02285 11.6108 4.99746 11.5162 5.0002Z" fill="white"></path>
                          </svg>
                        </div>
                        <div class="ckc-configurator-options-selection-color" :style="'background-color:' + attr.color"></div>
                        <div class="ckc-configurator-options-selection-color-title" x-text="attr.name"></div>
                      </div>
                    </template>
                  </div>
                </template>
                <template x-if="group.group_type !== 'color'">
                  <div class="ckc-option-attr-pills">
                    <template x-for="attr in group.attributes" :key="attr.id_attribute">
                      <button type="button" class="ckc-option-attr-pill"
                              :class="{ 'selected': $store.configurator.soubPanelSelections[group.id_attribute_group] === attr.id_attribute }"
                              @click="$store.configurator.selectSoubPanelAttribute(group.id_attribute_group, attr.id_attribute)"
                              x-text="attr.name"></button>
                    </template>
                  </div>
                </template>
              </div>
            </template>

            <button class="ckc-btn-next" @click="$store.configurator.validateSoubPanel()"
                    x-show="!$store.configurator.soubPanelLoading && $store.configurator.soubPanelGroups.length > 0"
                    :disabled="!$store.configurator.soubPanelComplete"
                    :class="{ 'ckc-btn-disabled': !$store.configurator.soubPanelComplete }">
              {l s='Valider' d='Shop.Theme.Customconfigurator'}
            </button>
          </div>

        </div>

        {* ==================================================
           ÉTAPE OPTIONS (dynamique)
           ================================================== *}
        <div x-show="$store.configurator.currentStep === $store.configurator.stepOptions">
          <template x-if="$store.configurator.troncons.length > 1">
            <div class="ckc-editing-badge">
              {l s='Modification de la section' d='Shop.Theme.Customconfigurator'} <strong x-text="$store.configurator.currentTroncon + 1"></strong> / <span x-text="$store.configurator.troncons.length"></span>
            </div>
          </template>
          <h2 class="h2">{l s='Personnalisez votre clôture avec nos options' d='Shop.Theme.Customconfigurator'}</h2>
          <div class="ckc-step-heading">{l s='Ajoutez des éléments complémentaires pour une clôture parfaitement adaptée à vos besoins.' d='Shop.Theme.Customconfigurator'}</div>

          {* Conseil d'expert étape options *}
          <template x-if="$store.configurator.stepAdvice['step_options'] && $store.configurator.stepAdvice['step_options'].active && $store.configurator.stepAdvice['step_options'].content">
            <div class="ckc-expert">
              <div class="ckc-icon-conseils-text-container">
                <div class="ckc-expert__avatar"><img src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-expert.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="27" height="27" ></div>
                <div class="ckc-expert__body">
                  <p class="ckc-expert__title_text"><strong x-text="$store.configurator.stepAdvice['step_options'].title" x-show="$store.configurator.stepAdvice['step_options'].title"></strong></p>
                  <p class="ckc-expert__text" x-html="$store.configurator.stepAdvice['step_options'].content"></p>
                </div>
              </div>
            </div>
            <div class="ckc-icon-conseils-img-container">
              <img class="ckc-icon-conseils-masques" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-masques.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="22" height="16" >
              <img class="ckc-icon-conseils-affiches" src="/modules/ckcconfigurateurdistriclos/views/img/icon-conseils-affiches.svg" alt="{l s='Icône conseils expert' d='Shop.Theme.Customconfigurator'}" width="24" height="24" >
            </div>
          </template>

          <template x-if="$store.configurator.options.length === 0">
            <p style="color:var(--ckc-muted);font-size:.9rem;">{l s='Aucune option disponible pour ce type de clôture.' d='Shop.Theme.Customconfigurator'}</p>
          </template>

          {* Level 1: Option type categories *}
          <div class="ckc-option-types">
            <template x-for="ot in $store.configurator.optionTypes" :key="ot.key">
              <div class="ckc-option-type-card"
                   :class="{ 'ckc-option-type-card--active': $store.configurator.activeOptionType === ot.key,
                             'ckc-option-type-card--has-selection': ot.options.some(o => $store.configurator.currentTronconData().options[o.id]) }"
                   @click="$store.configurator.activeOptionType = ($store.configurator.activeOptionType === ot.key) ? null : ot.key">
                <span class="ckc-option-type-card__label" x-text="ot.label"></span>
                <span class="ckc-option-type-card__count" x-text="ot.options.filter(o => $store.configurator.currentTronconData().options[o.id]).length + '/' + ot.options.length"></span>
              </div>
            </template>
          </div>

          {* Level 2: Products in selected category *}
          <template x-if="$store.configurator.activeOptionType">
            <div class="ckc-option-products">
              <template x-for="opt in $store.configurator.activeOptionTypeOptions" :key="opt.id">
                <div class="ckc-card"
                     :class="{ 'ckc-card--selected': $store.configurator.currentTronconData().options[opt.id] }"
                     @click="$store.configurator.openOptionPanel(opt.id)">
                  <div class="ckc-card__check">✓</div>
                  <div class="ckc-card__img-wrap">
                    <template x-if="opt.image_url">
                      <img :src="opt.image_url" :alt="opt.title" loading="lazy">
                    </template>
                  </div>
                  <div class="ckc-card__body">
                    <p class="ckc-card__title" x-text="opt.title"></p>
                    <p class="ckc-card__subtitle" x-text="opt.subtitle" x-show="opt.subtitle"></p>
                    <p class="ckc-card__desc" x-text="opt.description" x-show="opt.description"></p>
                    <template x-if="opt.price_base !== null && opt.price_base !== undefined">
                      <div class="ckc-card__price">
                        <strong class="ckc-price-main" x-text="opt.show_from_price ? (((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.fromPrice)||'À partir de') + ' ' + (opt.price_base || 0).toFixed(2) + ' €') : ('+ ' + (opt.price_base || 0).toFixed(2) + ' €')"></strong>
                      </div>
                    </template>
                    <template x-if="!$store.configurator.currentTronconData().options[opt.id]">
                      <button class="ckc-configurateur-item-select-btn">{l s='Sélectionner' d='Shop.Theme.Customconfigurator'}</button>
                    </template>
                    <template x-if="$store.configurator.currentTronconData().options[opt.id]">
                      <div class="ckc-card__actions">
                        <button class="ckc-configurateur-item-select-btn" @click.stop="$store.configurator.openOptionPanel(opt.id)">{l s='Modifier' d='Shop.Theme.Customconfigurator'}</button>
                        <button class="ckc-configurateur-item-remove-btn" @click.stop="$store.configurator.removeOption(opt.id)">{l s='Retirer' d='Shop.Theme.Customconfigurator'}</button>
                      </div>
                    </template>
                  </div>
                </div>
              </template>
            </div>
          </template>

          <div class="ckc-configurator-options-selection-container" :class="{ 'ckc-configurator-options-selection-open' : $store.configurator.optionPanelOpen }">
            <div class="ckc-configurator-options-selection-close" @click="$store.configurator.optionPanelOpen = false">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M10.2929 0.292893C10.6834 -0.0976311 11.3164 -0.0976311 11.707 0.292893C12.0975 0.683417 12.0975 1.31643 11.707 1.70696L7.41399 5.99992L11.707 10.2929C12.0975 10.6834 12.0975 11.3164 11.707 11.707C11.3164 12.0975 10.6834 12.0975 10.2929 11.707L5.99992 7.41399L1.70696 11.707C1.31643 12.0975 0.683417 12.0975 0.292893 11.707C-0.0976311 11.3164 -0.0976311 10.6834 0.292893 10.2929L4.58586 5.99992L0.292893 1.70696C-0.0976311 1.31643 -0.0976311 0.683417 0.292893 0.292893C0.683417 -0.0976311 1.31643 -0.0976311 1.70696 0.292893L5.99992 4.58586L10.2929 0.292893Z" fill="black"/>
              </svg>
            </div>
            <div class="ckc-configurator-options-selection-title" x-text="((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.configurePrefix)||'Paramétrer :') + ' ' + ($store.configurator.optionPanelOption?.title || ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.option)||'Option'))"></div>

            <template x-if="$store.configurator.optionPanelLoading">
              <div style="text-align:center;padding:2rem;color:var(--ckc-muted);">
                <div class="ckc-spinner" style="width:24px;height:24px;margin:0 auto .5rem;"></div>
                {l s='Chargement des déclinaisons…' d='Shop.Theme.Customconfigurator'}
              </div>
            </template>

            <template x-if="!$store.configurator.optionPanelLoading && $store.configurator.optionPanelGroups.length === 0">
              <div style="padding:1rem;color:var(--ckc-muted);font-size:.9rem;">{l s='Aucune déclinaison disponible pour cette option.' d='Shop.Theme.Customconfigurator'}</div>
            </template>

            <template x-for="group in $store.configurator.optionPanelGroups.filter(g => g.attributes.length > 1)" :key="group.id_attribute_group">
              <div class="ckc-configurator-options-selection-options-container">
                <div class="ckc-configurator-options-selection-subtitle" x-text="group.name + ' :'"></div>
                {* Color group: show color swatches *}
                <template x-if="group.group_type === 'color'">
                  <div class="ckc-configurator-options-selection-options-color">
                    <template x-for="attr in group.attributes" :key="attr.id_attribute">
                      <div class="ckc-configurator-options-selection-option-color"
                           :class="{ 'ckc-configurator-options-selected-option-color': $store.configurator.optionPanelSelections[group.id_attribute_group] === attr.id_attribute }"
                           @click="$store.configurator.selectOptionAttribute(group.id_attribute_group, attr.id_attribute)">
                        <div class="ckc-color-selected-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 16 16" fill="none">
                            <rect width="16" height="16" rx="8" fill="#7CBA49"></rect>
                            <path d="M11.5162 5.0002C11.3941 5.00377 11.2782 5.05385 11.1931 5.13982L6.35636 9.88676L4.80896 8.3681C4.76566 8.32384 4.7138 8.2885 4.65641 8.26416C4.59902 8.23982 4.53726 8.22695 4.47474 8.22633C4.41222 8.22571 4.35021 8.23733 4.29232 8.26052C4.23444 8.28372 4.18186 8.31801 4.13765 8.3614C4.09344 8.40479 4.0585 8.45639 4.03486 8.5132C4.01123 8.57001 3.99939 8.63087 4.00002 8.69223C4.00066 8.75358 4.01376 8.8142 4.03857 8.87052C4.06337 8.92685 4.09938 8.97775 4.14448 9.02024L6.02412 10.865C6.11225 10.9514 6.23175 11 6.35636 11C6.48096 11 6.60047 10.9514 6.6886 10.865L11.8576 5.79196C11.9255 5.72718 11.9719 5.64378 11.9907 5.55269C12.0094 5.46159 11.9997 5.36706 11.9628 5.28147C11.9259 5.19588 11.8635 5.12323 11.7838 5.07304C11.7041 5.02285 11.6108 4.99746 11.5162 5.0002Z" fill="white"></path>
                          </svg>
                        </div>
                        <div class="ckc-configurator-options-selection-color" :style="'background-color:' + attr.color"></div>
                        <div class="ckc-configurator-options-selection-color-title" x-text="attr.name"></div>
                      </div>
                    </template>
                  </div>
                </template>
                {* Non-color group: show text pills *}
                <template x-if="group.group_type !== 'color'">
                  <div class="ckc-option-attr-pills">
                    <template x-for="attr in group.attributes" :key="attr.id_attribute">
                      <button type="button"
                              class="ckc-option-attr-pill"
                              :class="{ 'selected': $store.configurator.optionPanelSelections[group.id_attribute_group] === attr.id_attribute }"
                              @click="$store.configurator.selectOptionAttribute(group.id_attribute_group, attr.id_attribute)"
                              x-text="attr.name"></button>
                    </template>
                  </div>
                </template>
              </div>
            </template>

            <button class="ckc-btn-next" @click="$store.configurator.validateOptionPanel()"
                    x-show="!$store.configurator.optionPanelLoading && $store.configurator.optionPanelGroups.length > 0"
                    :disabled="!$store.configurator.optionPanelComplete"
                    :class="{ 'ckc-btn-disabled': !$store.configurator.optionPanelComplete }">
              {l s='Valider' d='Shop.Theme.Customconfigurator'}
            </button>
          </div>
            {*<div
              class="ckc-option-card"
              :class="{ 'selected': $store.configurator.currentTronconData().options[opt.id] }"
              @click="$store.configurator.toggleOption('options', opt.id)"
            >
              <template x-if="opt.image_url">
                <img class="ckc-option-card__img" :src="opt.image_url" :alt="opt.title" loading="lazy">
              </template>
              <div class="ckc-option-card__body">
                <p class="ckc-option-card__title" x-text="opt.title"></p>
                <p class="ckc-option-card__subtitle" x-text="opt.subtitle" x-show="opt.subtitle"></p>
                <p class="ckc-option-card__subtitle" x-text="opt.description" x-show="opt.description && !opt.subtitle"></p>
                <template x-if="opt.price_base !== null && opt.price_base !== undefined">
                  <p class="ckc-option-card__price"
                    x-text="opt.show_from_price ? (((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.fromPrice)||'À partir de') + ' ' + (opt.price_base || 0).toFixed(2) + ' €') : ('+ ' + (opt.price_base || 0).toFixed(2) + ' €')"></p>
                </template>
                <template x-if="opt.price_promo !== null && opt.price_promo !== undefined">
                  <p class="ckc-option-card__subtitle">
                    <s x-text="opt.price_promo.toFixed(2) + ' €'"></s>
                  </p>
                </template>
                <p class="ckc-option-card__promo" x-text="opt.promo_message" x-show="opt.promo_message"></p>
              </div>
              <div class="ckc-option-card__toggle">
                <label class="ckc-switch" @click.stop>
                  <input type="checkbox"
                         :checked="$store.configurator.currentTronconData().options[opt.id]"
                         @change="$store.configurator.toggleOption('options', opt.id)">
                  <div class="ckc-switch__track"></div>
                </label>
              </div>
            </div>*}

        </div>

        {* ==================================================
           ÉTAPE TRONÇONS SUPPLÉMENTAIRES (dynamique)
           ================================================== *}
        <div x-show="$store.configurator.currentStep === $store.configurator.stepMultiTroncons">
          <h2 class="h2">{l s='Configuration d\'une nouvelle section' d='Shop.Theme.Customconfigurator'}</h2>

          <template x-if="!$store.configurator.multiTroncons">
            <div class="ckc-step-heading">
              {l s='Vous avez indiqué avoir une seule hauteur de clôture. Passez au récapitulatif ou retournez à l\'étape 1 pour modifier.' d='Shop.Theme.Customconfigurator'}
            </div>
          </template>

          <template x-if="$store.configurator.multiTroncons">
            <div>
              <div class="ckc-step-heading">{l s='Vous avez indiqué avoir plusieurs hauteurs de clôture. Configurez maintenant vos différentes sections.' d='Shop.Theme.Customconfigurator'}</div>
{*              <p style="margin-bottom:1.25rem;font-size:.9rem;color:var(--ckc-muted);">
                Vous avez <strong x-text="$store.configurator.troncons.length" style="color:var(--ckc-text)"></strong> tronçon(s) configuré(s).
                Ajoutez-en d'autres ou dupliquez le dernier.
              </p>*}

              <div class="ckc-troncon-list">
                <div class="ckc-etape-selection-title">{l s='Vos sections' d='Shop.Theme.Customconfigurator'}</div>
                <template x-for="(t, idx) in $store.configurator.troncons" :key="idx">
                  <div class="ckc-troncon-item" :class="{ 'current': idx === $store.configurator.currentTroncon }">
                    <div class="ckc-troncon-item__info_actions">
                      <div class="ckc-troncon-item__info">
                        <div class="ckc-troncon-item__number"><span x-text="idx + 1"></span></div>
                        <div class="ckc-troncon-item__meta">
                          <div class="ckc-troncon-item__title">{l s='Section' d='Shop.Theme.Customconfigurator'} <span x-text="idx + 1"></span></div>
                          <div class="ckc-troncon-item__status"><svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 8 8" fill="none">
                              <rect width="8" height="8" rx="4" fill="#7CBA49"/>
                              <path d="M5.26438 2.76462C5.39455 2.63445 5.60556 2.63445 5.73573 2.76462C5.86591 2.8948 5.86591 3.1058 5.73573 3.23598L3.73573 5.23598C3.60556 5.36615 3.39455 5.36615 3.26438 5.23598L2.26438 4.23598C2.1342 4.1058 2.1342 3.8948 2.26438 3.76462C2.39455 3.63445 2.60556 3.63445 2.73573 3.76462L3.50006 4.52895L5.26438 2.76462Z" fill="white"/>
                            </svg>  {l s='Configuration terminée' d='Shop.Theme.Customconfigurator'}</div>
{*                          <span x-text="t.longueur ? t.longueur + ' m' : '—'"></span>
                          <template x-if="t.rangeId">
                            <span> · <span x-text="$store.configurator.ranges.find(r => r.id === t.rangeId)?.title || ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.gamme)||'Gamme')"></span></span>
                          </template>*}
                        </div>
                      </div>
                      <div class="ckc-troncon-item__actions">
                        <button class="ckc-btn-sm ckc-btn-modify" @click="$store.configurator.editTroncon(idx)">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M10.5286 1.86135C11.3412 1.04878 12.6587 1.04886 13.4713 1.86135C14.2839 2.67397 14.2839 3.99143 13.4713 4.80406L5.09634 13.1797C4.88827 13.3878 4.71025 13.5718 4.49282 13.7051C4.35862 13.7873 4.21511 13.8532 4.06574 13.9017L3.9147 13.944C3.66666 14.0036 3.41082 13.9994 3.11652 13.9994H1.99998C1.63179 13.9994 1.33332 13.7009 1.33332 13.3327V12.2168C1.33332 11.9224 1.32912 11.666 1.38865 11.418C1.43767 11.2139 1.51871 11.0187 1.62824 10.8399C1.76148 10.6226 1.9455 10.4445 2.15363 10.2364L10.5286 1.86135ZM13.5078 11.5495C13.7562 11.2778 14.1781 11.2589 14.4499 11.5072C14.7216 11.7556 14.7405 12.1775 14.4922 12.4492L13.8255 13.1784C13.3525 13.6958 12.6971 13.9994 12 13.9994C11.3032 13.9993 10.6487 13.696 10.1758 13.1791V13.1797C9.94119 12.924 9.63662 12.793 9.33332 12.793C9.03009 12.793 8.72553 12.924 8.49087 13.1797C8.24195 13.4508 7.82068 13.4688 7.54946 13.2201C7.27821 12.9712 7.2596 12.5493 7.50845 12.278C7.982 11.7619 8.63689 11.4597 9.33332 11.4597C9.9861 11.4597 10.6024 11.7252 11.067 12.1836L11.1582 12.278L11.1588 12.2793C11.3929 12.5353 11.6971 12.666 12 12.666C12.3028 12.666 12.607 12.5353 12.8411 12.2793L13.5078 11.5495ZM2.66665 12.666H3.11652C3.47425 12.666 3.5447 12.6619 3.6035 12.6478C3.6715 12.6315 3.73653 12.6043 3.79621 12.5677C3.84776 12.5361 3.90095 12.489 4.15363 12.2364L12.5286 3.86135C12.8023 3.58762 12.8196 3.15431 12.5801 2.8607L12.5286 2.80406C12.2366 2.51222 11.7632 2.51224 11.4713 2.80406L3.09634 11.1797C2.84343 11.4326 2.7966 11.4855 2.76496 11.5371L2.76431 11.5365C2.72774 11.5962 2.70118 11.6614 2.68488 11.7292C2.67074 11.7881 2.66665 11.859 2.66665 12.2168V12.666Z" fill="#026473"/>
                          </svg>
                          {l s='Modifier' d='Shop.Theme.Customconfigurator'}
                        </button>
                        <button class="ckc-btn-sm" @click="$store.configurator.duplicateLastTroncon()">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <g clip-path="url(#clip0_3859_1046)">
                              <path d="M14.0001 7.46712C14.0001 7.08276 13.9992 6.8344 13.9838 6.64551C13.969 6.46446 13.944 6.39721 13.9272 6.36426C13.8633 6.23899 13.7615 6.13716 13.6362 6.07325C13.6032 6.05646 13.536 6.0314 13.3549 6.0166C13.166 6.00117 12.9177 6.00033 12.5333 6.00033H7.46688C7.08251 6.00033 6.83416 6.00117 6.64527 6.0166C6.46421 6.0314 6.39697 6.05645 6.36402 6.07325C6.2387 6.13713 6.13689 6.23894 6.073 6.36426C6.05621 6.39722 6.03116 6.46445 6.01636 6.64551C6.00093 6.8344 6.00008 7.08276 6.00008 7.46712V12.5335C6.00008 12.9179 6.00093 13.1662 6.01636 13.3551C6.03116 13.5362 6.05622 13.6035 6.073 13.6364C6.13692 13.7617 6.23874 13.8636 6.36402 13.9274C6.39697 13.9442 6.46421 13.9693 6.64527 13.9841C6.83416 13.9995 7.08251 14.0003 7.46688 14.0003H12.5333C12.9177 14.0003 13.166 13.9995 13.3549 13.9841C13.536 13.9693 13.6032 13.9442 13.6362 13.9274C13.7615 13.8635 13.8633 13.7617 13.9272 13.6364C13.944 13.6035 13.969 13.5362 13.9838 13.3551C13.9992 13.1662 14.0001 12.9179 14.0001 12.5335V7.46712ZM10.0001 3.46713C10.0001 3.08276 9.99924 2.8344 9.98381 2.64551C9.96901 2.46446 9.94396 2.39721 9.92717 2.36426C9.86333 2.23899 9.76151 2.13716 9.63615 2.07325C9.60321 2.05646 9.53598 2.0314 9.3549 2.0166C9.166 2.00117 8.91765 2.00033 8.53329 2.00033H3.46688C3.08251 2.00033 2.83416 2.00117 2.64527 2.0166C2.46421 2.0314 2.39697 2.05645 2.36402 2.07325C2.2387 2.13713 2.13689 2.23894 2.073 2.36426C2.05621 2.39722 2.03116 2.46445 2.01636 2.64551C2.00093 2.8344 2.00008 3.08276 2.00008 3.46713V8.53353C2.00008 8.9179 2.00093 9.16625 2.01636 9.35515C2.03116 9.53623 2.05622 9.60346 2.073 9.6364C2.13692 9.76175 2.23875 9.86357 2.36402 9.92741C2.39697 9.9442 2.46421 9.96925 2.64527 9.98405C2.83416 9.99949 3.08251 10.0003 3.46688 10.0003H4.66675V7.46712C4.66675 7.10476 4.66603 6.79257 4.68693 6.53679C4.70847 6.27328 4.75634 6.01099 4.88485 5.75879C5.07656 5.38264 5.38239 5.07681 5.75855 4.88509C6.01075 4.75659 6.27304 4.70872 6.53654 4.68718C6.79232 4.66628 7.10452 4.66699 7.46688 4.66699H10.0001V3.46713ZM11.3334 4.66699H12.5333C12.8957 4.66699 13.2078 4.66628 13.4636 4.68718C13.7271 4.70872 13.9894 4.75658 14.2416 4.88509C14.6177 5.07678 14.9236 5.38258 15.1153 5.75879C15.2438 6.01099 15.2917 6.27328 15.3132 6.53679C15.3341 6.79257 15.3334 7.10476 15.3334 7.46712V12.5335C15.3334 12.8959 15.3341 13.2081 15.3132 13.4639C15.2917 13.7274 15.2438 13.9897 15.1153 14.2419C14.9236 14.618 14.6178 14.9239 14.2416 15.1156C13.9894 15.2441 13.7271 15.2919 13.4636 15.3135C13.2078 15.3344 12.8957 15.3337 12.5333 15.3337H7.46688C7.10452 15.3337 6.79233 15.3344 6.53654 15.3135C6.27303 15.2919 6.01075 15.2441 5.75855 15.1156C5.38234 14.9238 5.07654 14.618 4.88485 14.2419C4.75634 13.9897 4.70847 13.7274 4.68693 13.4639C4.66603 13.2081 4.66675 12.8959 4.66675 12.5335V11.3337H3.46688C3.10452 11.3337 2.79233 11.3344 2.53654 11.3135C2.27303 11.2919 2.01075 11.2441 1.75855 11.1156C1.38234 10.9239 1.07654 10.618 0.88485 10.2419C0.756336 9.98966 0.708473 9.72738 0.686933 9.46387C0.666035 9.20808 0.666751 8.8959 0.666751 8.53353V3.46713C0.666751 3.10476 0.666035 2.79257 0.686933 2.53679C0.708474 2.27328 0.756345 2.01099 0.88485 1.75879C1.07656 1.38264 1.38239 1.07681 1.75855 0.885094C2.01075 0.756589 2.27304 0.708718 2.53654 0.687177C2.79232 0.666279 3.10451 0.666995 3.46688 0.666995H8.53329C8.89565 0.666995 9.20784 0.666279 9.46363 0.687177C9.72713 0.708717 9.98941 0.75658 10.2416 0.885094C10.6177 1.07678 10.9236 1.38258 11.1153 1.75879C11.2438 2.01099 11.2917 2.27328 11.3132 2.53679C11.3341 2.79257 11.3334 3.10476 11.3334 3.46713V4.66699Z" fill="white"/>
                            </g>
                            <defs>
                              <clipPath id="clip0_3859_1046">
                                <rect width="16" height="16" fill="white"/>
                              </clipPath>
                            </defs>
                          </svg>
                          {l s='Dupliquer' d='Shop.Theme.Customconfigurator'}
                        </button>
                        <button
                                class="ckc-btn-sm ckc-btn-sm--danger"
                                @click="$store.configurator.removeTroncon(idx)"
                                x-show="$store.configurator.troncons.length > 1"
                        >{l s='Supprimer' d='Shop.Theme.Customconfigurator'}</button>
                      </div>
                    </div>
                    <div class="ckc-troncon-item__etapes">
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Type' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="$store.configurator.fenceTypes.find(f => f.id === t.fenceTypeId)?.title || '—'"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Dimensions' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="(t.longueur || '—') + 'm × ' + ($store.configurator.heights.find(h => h.id === t.heightAttrId)?.label || '—')"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Angles' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="t.nbAngles || 0"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Gamme' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="$store.configurator.ranges.find(r => r.id === t.rangeId)?.title || '—'"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Poteaux' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="$store.configurator.postTypes.find(p => p.id === t.postTypeId)?.title || '—'"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Couleur' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="$store.configurator.colors.find(c => c.id === t.colorAttrId)?.label || '—'"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Options' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="$store.configurator.options.filter(o => t.options[o.id]).map(o => o.title).join(', ') || ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.sansOptions)||'Sans options')"></div>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
              <div class="ckc-configurator-troncon-selection-container">
                <div class="ckc-configurator-options-selection-close">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M10.2929 0.292893C10.6834 -0.0976311 11.3164 -0.0976311 11.707 0.292893C12.0975 0.683417 12.0975 1.31643 11.707 1.70696L7.41399 5.99992L11.707 10.2929C12.0975 10.6834 12.0975 11.3164 11.707 11.707C11.3164 12.0975 10.6834 12.0975 10.2929 11.707L5.99992 7.41399L1.70696 11.707C1.31643 12.0975 0.683417 12.0975 0.292893 11.707C-0.0976311 11.3164 -0.0976311 10.6834 0.292893 10.2929L4.58586 5.99992L0.292893 1.70696C-0.0976311 1.31643 -0.0976311 0.683417 0.292893 0.292893C0.683417 -0.0976311 1.31643 -0.0976311 1.70696 0.292893L5.99992 4.58586L10.2929 0.292893Z" fill="black"></path>
                  </svg>
                </div>
                <div class="ckc-configurator-options-selection-title">{l s='Section 1 :' d='Shop.Theme.Customconfigurator'}</div>
                <div class="ckc-configurator-options-selection-subtitle">
                  <div>{l s='Type de clôture :' d='Shop.Theme.Customconfigurator'}</div>
                  <img src="/modules/ckcconfigurateurdistriclos/views/img/ckc-btn-arrow-down-primary.svg" alt="icône à sélectionner"  width="14" height="8">
                </div>
                <div class="ckc-configurator-options-selection-type">
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Grillage souple' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <hr class="ckc-divider">
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Grillage rigide' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <hr class="ckc-divider">
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Grillage alluminium' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                </div>
                <div class="ckc-configurator-options-selection-subtitle">
                  <div>{l s='Spécifications section :' d='Shop.Theme.Customconfigurator'}</div>
                  <img src="/modules/ckcconfigurateurdistriclos/views/img/ckc-btn-arrow-down-primary.svg" alt="icône à sélectionner"  width="14" height="8">
                </div>
                <div class="ckc-configurator-options-selection-type">
                  <div class="ckc-field-group">
                    <span class="ckc-field-label">{l s='Longueur de la section' d='Shop.Theme.Customconfigurator'}</span>
                    <div class="ckc-stepper">
                      <input class="ckc-stepper__input" type="number" :min="$store.configurator.currentMinLength" :step="$store.configurator.currentLengthStep" x-model.number="$store.configurator.currentTronconData().longueur" @change="$store.configurator.snapLength()" placeholder="0">
                      <span class="ckc-stepper__unit">{l s='mètres' d='Shop.Theme.Customconfigurator'}</span>
                    </div>
                  </div>
                  <div class="ckc-field-group">
                    <span class="ckc-field-label">{l s='Nombre d\'angles > 90°' d='Shop.Theme.Customconfigurator'}</span>
                    <div class="ckc-angles">
                      <select name="ckc-angles" id="ckc-angles" x-model.number="$store.configurator.currentTronconData().nbAngles">
                        <template x-for="n in [0,1,2,3,4,5]" :key="n">
                          <option :value="n" x-text="n"></option>
                        </template>
                      </select>
                    </div>
                  </div>
                  <div class="ckc-field-group" x-show="$store.configurator.heights.length &gt; 0">
                    <span class="ckc-field-label">{l s='Hauteur de clôture' d='Shop.Theme.Customconfigurator'}</span>
                    <div class="ckc-heights">
                      <template x-for="h in $store.configurator.heights" :key="h.id">
                        <button class="ckc-height-pill" :class="{ 'selected': $store.configurator.currentTronconData().heightAttrId === h.id }" @click="$store.configurator.currentTronconData().heightAttrId = h.id; $store.configurator.computePrice()"><span x-text="h.label"></span><br>{l s='Standard' d='Shop.Theme.Customconfigurator'}</button>
                      </template>
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Pose sur platine' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Scellement sur muret' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Scellement en terre' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-wall-options">
                    <div class="ckc-wall-item" :class="{ 'selected': $store.configurator.currentTronconData().startOnWall }" @click="$store.configurator.currentTronconData().startOnWall = !$store.configurator.currentTronconData().startOnWall">
                      <label class="ckc-switch" @click.stop="">
                        <input type="checkbox" :checked="$store.configurator.currentTronconData().startOnWall" @change="$store.configurator.currentTronconData().startOnWall = !$store.configurator.currentTronconData().startOnWall">
                        <span class="checkbox"></span>
                      </label>
                      <div>
                        <span><strong>{l s='La clôture commence sur un mur' d='Shop.Theme.Customconfigurator'}</strong></span>
                        <div>
                          <span>{l s='Cochez si votre clôture démarre depuis un mur existant (maison, garage, muret, etc...)' d='Shop.Theme.Customconfigurator'}</span>
                        </div>
                      </div>
                    </div>
                    <div class="ckc-wall-item" :class="{ 'selected': $store.configurator.currentTronconData().endOnWall }" @click="$store.configurator.currentTronconData().endOnWall = !$store.configurator.currentTronconData().endOnWall">

                      <label class="ckc-switch" @click.stop="">
                        <input type="checkbox" :checked="$store.configurator.currentTronconData().endOnWall" @change="$store.configurator.currentTronconData().endOnWall = !$store.configurator.currentTronconData().endOnWall">
                        <span class="checkbox"></span>
                      </label>
                      <div>
                        <span><strong>{l s='La clôture se termine sur un mur' d='Shop.Theme.Customconfigurator'}</strong></span>
                        <div>
                          <span>{l s='Cochez si votre clôture se termine contre un mur existant (maison, garage, muret, etc...)' d='Shop.Theme.Customconfigurator'}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="ckc-configurator-options-selection-subtitle">
                  <div>{l s='Gammes & couleurs :' d='Shop.Theme.Customconfigurator'}</div>
                  <img src="/modules/ckcconfigurateurdistriclos/views/img/ckc-btn-arrow-down-primary.svg" alt="icône à sélectionner"  width="14" height="8">
                </div>
                <div class="ckc-configurator-options-selection-type">
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Pose sur platine' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Scellement sur muret' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-wall-options">
                    <div class="ckc-wall-item" :class="{ 'selected': $store.configurator.currentTronconData().startOnWall }" @click="$store.configurator.currentTronconData().startOnWall = !$store.configurator.currentTronconData().startOnWall">
                      <label class="ckc-switch" @click.stop="">
                        <input type="checkbox" :checked="$store.configurator.currentTronconData().startOnWall" @change="$store.configurator.currentTronconData().startOnWall = !$store.configurator.currentTronconData().startOnWall">
                        <span class="checkbox"></span>
                      </label>
                      <div>
                        <span><strong>{l s='Poteaux à clips' d='Shop.Theme.Customconfigurator'}</strong></span>
                        <div>
                          <span>{l s='Cochez si votre clôture démarre depuis un mur existant (maison, garage, muret, etc...)' d='Shop.Theme.Customconfigurator'}</span>
                        </div>
                      </div>
                    </div>
                    <div class="ckc-wall-item" :class="{ 'selected': $store.configurator.currentTronconData().endOnWall }" @click="$store.configurator.currentTronconData().endOnWall = !$store.configurator.currentTronconData().endOnWall">

                      <label class="ckc-switch" @click.stop="">
                        <input type="checkbox" :checked="$store.configurator.currentTronconData().endOnWall" @change="$store.configurator.currentTronconData().endOnWall = !$store.configurator.currentTronconData().endOnWall">
                        <span class="checkbox"></span>
                      </label>
                      <div>
                        <span><strong>{l s='Poteaux à encoche' d='Shop.Theme.Customconfigurator'}</strong></span>
                        <div>
                          <span>{l s='Cochez si votre clôture se termine contre un mur existant (maison, garage, muret, etc...)' d='Shop.Theme.Customconfigurator'}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-options-color">
                    <div class="ckc-configurator-options-selection-option-color" x-data="{ selectColor: false }" @click="selectColor = !selectColor" :class="selectColor ? 'ckc-configurator-options-selected-option-color' : ''">
                      <div class="ckc-color-selected-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 16 16" fill="none">
                          <rect width="16" height="16" rx="8" fill="#7CBA49"></rect>
                          <path d="M11.5162 5.0002C11.3941 5.00377 11.2782 5.05385 11.1931 5.13982L6.35636 9.88676L4.80896 8.3681C4.76566 8.32384 4.7138 8.2885 4.65641 8.26416C4.59902 8.23982 4.53726 8.22695 4.47474 8.22633C4.41222 8.22571 4.35021 8.23733 4.29232 8.26052C4.23444 8.28372 4.18186 8.31801 4.13765 8.3614C4.09344 8.40479 4.0585 8.45639 4.03486 8.5132C4.01123 8.57001 3.99939 8.63087 4.00002 8.69223C4.00066 8.75358 4.01376 8.8142 4.03857 8.87052C4.06337 8.92685 4.09938 8.97775 4.14448 9.02024L6.02412 10.865C6.11225 10.9514 6.23175 11 6.35636 11C6.48096 11 6.60047 10.9514 6.6886 10.865L11.8576 5.79196C11.9255 5.72718 11.9719 5.64378 11.9907 5.55269C12.0094 5.46159 11.9997 5.36706 11.9628 5.28147C11.9259 5.19588 11.8635 5.12323 11.7838 5.07304C11.7041 5.02285 11.6108 4.99746 11.5162 5.0002Z" fill="white"></path>
                        </svg>
                      </div>
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Vert' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Gris' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Noir' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Blanc' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Chêne clair' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Chêne clair' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                  </div>
                </div>
                <div class="ckc-configurator-options-selection-subtitle">
                  <div>{l s='Type de soubassement :' d='Shop.Theme.Customconfigurator'}</div>
                  <img src="/modules/ckcconfigurateurdistriclos/views/img/ckc-btn-arrow-down-primary.svg" alt="icône à sélectionner"  width="14" height="8">
                </div>
                <div class="ckc-configurator-options-selection-type">
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Soubassement béton 0.25m' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Soubassement béton 0.50m' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Soubassement acier 0.25m' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-options-color">
                    <div class="ckc-configurator-options-selection-option-color" x-data="{ selectColor: false }" @click="selectColor = !selectColor" :class="selectColor ? 'ckc-configurator-options-selected-option-color' : ''">
                      <div class="ckc-color-selected-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 16 16" fill="none">
                          <rect width="16" height="16" rx="8" fill="#7CBA49"></rect>
                          <path d="M11.5162 5.0002C11.3941 5.00377 11.2782 5.05385 11.1931 5.13982L6.35636 9.88676L4.80896 8.3681C4.76566 8.32384 4.7138 8.2885 4.65641 8.26416C4.59902 8.23982 4.53726 8.22695 4.47474 8.22633C4.41222 8.22571 4.35021 8.23733 4.29232 8.26052C4.23444 8.28372 4.18186 8.31801 4.13765 8.3614C4.09344 8.40479 4.0585 8.45639 4.03486 8.5132C4.01123 8.57001 3.99939 8.63087 4.00002 8.69223C4.00066 8.75358 4.01376 8.8142 4.03857 8.87052C4.06337 8.92685 4.09938 8.97775 4.14448 9.02024L6.02412 10.865C6.11225 10.9514 6.23175 11 6.35636 11C6.48096 11 6.60047 10.9514 6.6886 10.865L11.8576 5.79196C11.9255 5.72718 11.9719 5.64378 11.9907 5.55269C12.0094 5.46159 11.9997 5.36706 11.9628 5.28147C11.9259 5.19588 11.8635 5.12323 11.7838 5.07304C11.7041 5.02285 11.6108 4.99746 11.5162 5.0002Z" fill="white"></path>
                        </svg>
                      </div>
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Vert' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Gris' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Noir' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Blanc' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                  </div>
                </div>
                <div class="ckc-configurator-options-selection-subtitle">
                  <div>{l s='Nom de l\'option :' d='Shop.Theme.Customconfigurator'}</div>
                  <img src="/modules/ckcconfigurateurdistriclos/views/img/ckc-btn-arrow-down-primary.svg" alt="icône à sélectionner"  width="14" height="8">
                </div>
                <div class="ckc-configurator-options-selection-type">
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Soubassement béton 0.25m' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Soubassement béton 0.50m' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-option" x-data="{ select: false }" @click="select = !select" :class="select ? 'ckc-configurator-options-selected-option' : ''">
                    <div class="ckc-configurator-options-selection-option-img">
                      <img src="" alt="Image Option" class="ckc-configurator-options-selection-option-img" width="48" height="48">
                      <div class="ckc-configurator-options-selection-option-title">{l s='Soubassement acier 0.25m' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-select">
                      <img class="ckc-configurator-options-selection-icon-select" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config.svg" alt="icône à sélectionner" width="24" height="24">
                      <img class="ckc-configurator-options-selection-icon-selected" src="/modules/ckcconfigurateurdistriclos/views/img/ckc-select-troncon-config-selected.svg" alt="icône sélectionnée" width="24" height="24">
                    </div>
                  </div>
                  <div class="ckc-configurator-options-selection-options-color">
                    <div class="ckc-configurator-options-selection-option-color" x-data="{ selectColor: false }" @click="selectColor = !selectColor" :class="selectColor ? 'ckc-configurator-options-selected-option-color' : ''">
                      <div class="ckc-color-selected-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 16 16" fill="none">
                          <rect width="16" height="16" rx="8" fill="#7CBA49"></rect>
                          <path d="M11.5162 5.0002C11.3941 5.00377 11.2782 5.05385 11.1931 5.13982L6.35636 9.88676L4.80896 8.3681C4.76566 8.32384 4.7138 8.2885 4.65641 8.26416C4.59902 8.23982 4.53726 8.22695 4.47474 8.22633C4.41222 8.22571 4.35021 8.23733 4.29232 8.26052C4.23444 8.28372 4.18186 8.31801 4.13765 8.3614C4.09344 8.40479 4.0585 8.45639 4.03486 8.5132C4.01123 8.57001 3.99939 8.63087 4.00002 8.69223C4.00066 8.75358 4.01376 8.8142 4.03857 8.87052C4.06337 8.92685 4.09938 8.97775 4.14448 9.02024L6.02412 10.865C6.11225 10.9514 6.23175 11 6.35636 11C6.48096 11 6.60047 10.9514 6.6886 10.865L11.8576 5.79196C11.9255 5.72718 11.9719 5.64378 11.9907 5.55269C12.0094 5.46159 11.9997 5.36706 11.9628 5.28147C11.9259 5.19588 11.8635 5.12323 11.7838 5.07304C11.7041 5.02285 11.6108 4.99746 11.5162 5.0002Z" fill="white"></path>
                        </svg>
                      </div>
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Vert' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Gris' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Noir' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                    <div class="ckc-configurator-options-selection-option-color">
                      <div class="ckc-configurator-options-selection-color"></div>
                      <div class="ckc-configurator-options-selection-color-title">{l s='Blanc' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-configurator-options-selection-color-descr">RAL 6005</div>
                    </div>
                  </div>
                </div>
                <button class="ckc-btn-next ckc-btn-add-option-troncon" @click="$store.configurator.optionPanelOpen = true" >
                  {l s='Ajouter une option' d='Shop.Theme.Customconfigurator'}
                </button>
                <button class="ckc-btn-next" @click="$store.configurator.next()" >
                  {l s='Valider' d='Shop.Theme.Customconfigurator'}
                </button>
              </div>


              <div class="ckc-new-troncon">
                <div class="ckc-etape-selection-title" x-text="((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.startSectionConfig)||'Démarrer la configuration de la section') + ' ' + ($store.configurator.troncons.length + 1)"></div>
                <div class="ckc-new-troncon-item">
                  <div class="ckc-troncon-item__info_actions">
                    <div class="ckc-troncon-item__info">
                      <div class="ckc-troncon-item__number"><span x-text="$store.configurator.troncons.length + 1"></span></div>

                      <div class="ckc-troncon-item__meta">
                        <div class="ckc-troncon-item__title">{l s='Section' d='Shop.Theme.Customconfigurator'} <span x-text="$store.configurator.troncons.length + 1"></span></div>
                        <div class="ckc-troncon-item__status">
                          <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 8 8" fill="none">
                            <g clip-path="url(#clip0_3860_1140)"><path d="M3.66659 7.33301V5.99967C3.66659 5.81558 3.81582 5.66634 3.99992 5.66634C4.18401 5.66634 4.33325 5.81558 4.33325 5.99967V7.33301C4.33325 7.5171 4.18401 7.66634 3.99992 7.66634C3.81582 7.66634 3.66659 7.5171 3.66659 7.33301ZM2.34757 5.18066C2.47775 5.05049 2.68876 5.05049 2.81893 5.18066C2.9491 5.31084 2.9491 5.52184 2.81893 5.65202L1.87622 6.59473C1.74605 6.7249 1.53504 6.7249 1.40487 6.59473C1.27469 6.46455 1.27469 6.25355 1.40487 6.12337L2.34757 5.18066ZM5.18091 5.18066C5.31108 5.05049 5.52209 5.05049 5.65226 5.18066L6.59497 6.12337C6.72515 6.25355 6.72515 6.46455 6.59497 6.59473C6.4648 6.7249 6.25379 6.7249 6.12362 6.59473L5.18091 5.65202C5.05073 5.52184 5.05073 5.31084 5.18091 5.18066ZM1.99992 3.66634C2.18401 3.66634 2.33325 3.81558 2.33325 3.99967C2.33325 4.18377 2.18401 4.33301 1.99992 4.33301H0.666585C0.48249 4.33301 0.333252 4.18377 0.333252 3.99967C0.333252 3.81558 0.48249 3.66634 0.666585 3.66634H1.99992ZM7.33325 3.66634C7.51735 3.66634 7.66659 3.81558 7.66659 3.99967C7.66659 4.18377 7.51735 4.33301 7.33325 4.33301H5.99992C5.81582 4.33301 5.66659 4.18377 5.66659 3.99967C5.66659 3.81558 5.81582 3.66634 5.99992 3.66634H7.33325ZM1.40487 1.43066C1.53504 1.30053 1.74606 1.3005 1.87622 1.43066L2.81893 2.37337C2.94909 2.50353 2.94906 2.71455 2.81893 2.84473C2.68875 2.9749 2.47775 2.9749 2.34757 2.84473L1.40487 1.90202C1.27469 1.77184 1.27469 1.56084 1.40487 1.43066ZM6.12362 1.43066C6.25378 1.3005 6.46479 1.30052 6.59497 1.43066C6.72515 1.56084 6.72514 1.77184 6.59497 1.90202L5.65226 2.84473C5.52209 2.9749 5.31108 2.9749 5.18091 2.84473C5.05078 2.71455 5.05075 2.50353 5.18091 2.37337L6.12362 1.43066ZM3.66659 1.99967V0.666341C3.66659 0.482246 3.81582 0.333008 3.99992 0.333008C4.18401 0.333008 4.33325 0.482246 4.33325 0.666341V1.99967C4.33325 2.18377 4.18401 2.33301 3.99992 2.33301C3.81582 2.33301 3.66659 2.18377 3.66659 1.99967Z" fill="#026473"/></g><defs><clipPath id="clip0_3860_1140"><rect width="8" height="8" fill="white"/></clipPath></defs>
                          </svg>   {l s='Configuration en cours' d='Shop.Theme.Customconfigurator'}</div>
{*                        <span x-text="t.longueur ? t.longueur + ' m' : '—'"></span>
                        <template x-if="t.rangeId">
                          <span> · <span x-text="$store.configurator.ranges.find(r => r.id === t.rangeId)?.title || ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.gamme)||'Gamme')"></span></span>
                        </template>*}
                      </div>
                    </div>
                    <div class="ckc-troncon-item__actions">
                      <button class="ckc-btn-sm" @click="$store.configurator.addTroncon()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                          <g clip-path="url(#clip0_3860_1144)"><path d="M14.0001 8.00033C14.0001 4.68662 11.3138 2.00033 8.00008 2.00033C4.68637 2.00033 2.00008 4.68662 2.00008 8.00033C2.00008 11.314 4.68637 14.0003 8.00008 14.0003C11.3138 14.0003 14.0001 11.314 14.0001 8.00033ZM7.33342 10.667V8.66699H5.33342C4.96523 8.66699 4.66675 8.36852 4.66675 8.00033C4.66675 7.63214 4.96523 7.33366 5.33342 7.33366H7.33342V5.33366C7.33342 4.96547 7.63189 4.66699 8.00008 4.66699C8.36827 4.66699 8.66675 4.96547 8.66675 5.33366V7.33366H10.6667C11.0349 7.33366 11.3334 7.63214 11.3334 8.00033C11.3334 8.36852 11.0349 8.66699 10.6667 8.66699H8.66675V10.667C8.66675 11.0352 8.36827 11.3337 8.00008 11.3337C7.63189 11.3337 7.33342 11.0352 7.33342 10.667ZM15.3334 8.00033C15.3334 12.0504 12.0501 15.3337 8.00008 15.3337C3.94999 15.3337 0.666748 12.0504 0.666748 8.00033C0.666748 3.95024 3.94999 0.666992 8.00008 0.666992C12.0501 0.666992 15.3334 3.95023 15.3334 8.00033Z" fill="white"/></g><defs><clipPath id="clip0_3860_1144"><rect width="16" height="16" fill="white"/></clipPath></defs>
                        </svg>
                        {l s='Ajouter' d='Shop.Theme.Customconfigurator'}
                      </button>
                    </div>
                  </div>
                  <div class="ckc-troncon-item__etapes">
                    <div class="ckc-troncon-item__etape">
                      <div class="ckc-troncon-item__etape_title">{l s='Type' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-troncon-item__etape_selection">{l s='Choisir' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-troncon-item__etape">
                      <div class="ckc-troncon-item__etape_title">{l s='Dimensions' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-troncon-item__etape_selection">{l s='Choisir' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-troncon-item__etape">
                      <div class="ckc-troncon-item__etape_title">{l s='Angles' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-troncon-item__etape_selection">{l s='Choisir' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-troncon-item__etape">
                      <div class="ckc-troncon-item__etape_title">{l s='Gamme' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-troncon-item__etape_selection">{l s='Choisir' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-troncon-item__etape">
                      <div class="ckc-troncon-item__etape_title">{l s='Poteaux' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-troncon-item__etape_selection">{l s='Choisir' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-troncon-item__etape">
                      <div class="ckc-troncon-item__etape_title">{l s='Couleur' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-troncon-item__etape_selection">{l s='Choisir' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                    <div class="ckc-troncon-item__etape">
                      <div class="ckc-troncon-item__etape_title">{l s='Options' d='Shop.Theme.Customconfigurator'}</div>
                      <div class="ckc-troncon-item__etape_selection">{l s='Choisir' d='Shop.Theme.Customconfigurator'}</div>
                    </div>
                  </div>
                </div>
              </div>

{*              <button class="ckc-add-troncon-btn" @click="$store.configurator.addTroncon()">
                + Ajouter un tronçon
              </button>
              <button class="ckc-add-troncon-btn" @click="$store.configurator.duplicateLastTroncon()"
                      style="margin-top:.5rem; border-style:dashed;">
                Dupliquer le dernier tronçon
              </button>*}
            </div>
          </template>
        </div>

        {* ==================================================
           ÉTAPE RÉCAPITULATIF (dernière étape)
           ================================================== *}
        <div x-show="$store.configurator.currentStep === $store.configurator.totalSteps">
          <h2 class="h2">{l s='Récapitulatif de votre configuration' d='Shop.Theme.Customconfigurator'}</h2>
          <div class="ckc-step-heading">{l s='Vérifiez et validez votre commande' d='Shop.Theme.Customconfigurator'}</div>

          <template x-if="!$store.configurator.summary">
            <div class="ckc-loading-screen" style="min-height:200px;">
              <div class="ckc-spinner"></div>
              <p style="color:var(--ckc-muted);font-size:.875rem;">{l s='Calcul de votre configuration…' d='Shop.Theme.Customconfigurator'}</p>
            </div>
          </template>

          <template x-if="$store.configurator.summary">
            <div>
              <template x-for="t in $store.configurator.summary.troncons" :key="t.index">

                <div class="ckc-new-troncon ckc-new-troncon-recap">
                  <div class="ckc-new-troncon-item">
                    <div class="ckc-troncon-item__info_actions">
                      <div class="ckc-troncon-item__info">
                        <div class="ckc-troncon-item__number"><span x-text="t.index"></span></div>

                        <div class="ckc-troncon-item__meta">
                          <div class="ckc-troncon-item__title">{l s='Section' d='Shop.Theme.Customconfigurator'} <span x-text="t.index"></span></div>
                        </div>
                      </div>
                      <div class="ckc-troncon-item__actions">
                        <button class="ckc-btn-sm ckc-btn-modify" @click="$store.configurator.editTroncon(t.index - 1)">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M10.5286 1.86135C11.3412 1.04878 12.6587 1.04886 13.4713 1.86135C14.2839 2.67397 14.2839 3.99143 13.4713 4.80406L5.09634 13.1797C4.88827 13.3878 4.71025 13.5718 4.49282 13.7051C4.35862 13.7873 4.21511 13.8532 4.06574 13.9017L3.9147 13.944C3.66666 14.0036 3.41082 13.9994 3.11652 13.9994H1.99998C1.63179 13.9994 1.33332 13.7009 1.33332 13.3327V12.2168C1.33332 11.9224 1.32912 11.666 1.38865 11.418C1.43767 11.2139 1.51871 11.0187 1.62824 10.8399C1.76148 10.6226 1.9455 10.4445 2.15363 10.2364L10.5286 1.86135ZM13.5078 11.5495C13.7562 11.2778 14.1781 11.2589 14.4499 11.5072C14.7216 11.7556 14.7405 12.1775 14.4922 12.4492L13.8255 13.1784C13.3525 13.6958 12.6971 13.9994 12 13.9994C11.3032 13.9993 10.6487 13.696 10.1758 13.1791V13.1797C9.94119 12.924 9.63662 12.793 9.33332 12.793C9.03009 12.793 8.72553 12.924 8.49087 13.1797C8.24195 13.4508 7.82068 13.4688 7.54946 13.2201C7.27821 12.9712 7.2596 12.5493 7.50845 12.278C7.982 11.7619 8.63689 11.4597 9.33332 11.4597C9.9861 11.4597 10.6024 11.7252 11.067 12.1836L11.1582 12.278L11.1588 12.2793C11.3929 12.5353 11.6971 12.666 12 12.666C12.3028 12.666 12.607 12.5353 12.8411 12.2793L13.5078 11.5495ZM2.66665 12.666H3.11652C3.47425 12.666 3.5447 12.6619 3.6035 12.6478C3.6715 12.6315 3.73653 12.6043 3.79621 12.5677C3.84776 12.5361 3.90095 12.489 4.15363 12.2364L12.5286 3.86135C12.8023 3.58762 12.8196 3.15431 12.5801 2.8607L12.5286 2.80406C12.2366 2.51222 11.7632 2.51224 11.4713 2.80406L3.09634 11.1797C2.84343 11.4326 2.7966 11.4855 2.76496 11.5371L2.76431 11.5365C2.72774 11.5962 2.70118 11.6614 2.68488 11.7292C2.67074 11.7881 2.66665 11.859 2.66665 12.2168V12.666Z" fill="#026473"/>
                          </svg>
                          {l s='Modifier' d='Shop.Theme.Customconfigurator'}
                        </button>
                      </div>
                    </div>
                    <div class="ckc-troncon-item__etapes">
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Type' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="t.fence_type?.title || '—'"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Dimensions' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="(t.longueur || '—') + 'm × ' + (t.height?.label || t.height || '—')"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Angles' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="(t.nb_angles || 0) + ' ' + ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.angleS)||'angle(s)')"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Gamme' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="t.range?.title || '—'"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Poteaux' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="t.post_type?.title || '—'"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Couleur' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="t.color?.label || t.color || '—'"></div>
                      </div>
                      <div class="ckc-troncon-item__etape">
                        <div class="ckc-troncon-item__etape_title">{l s='Options' d='Shop.Theme.Customconfigurator'}</div>
                        <div class="ckc-troncon-item__etape_selection" x-text="t.options_list?.length ? t.options_list.map(o => o.title).join(', ') : ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.sansOptions)||'Sans options')"></div>
                      </div>
                    </div>
                    <div class="ckc-recap-row">
                      <span class="ckc-recap-row__label">{l s='Prix de la section' d='Shop.Theme.Customconfigurator'}</span>
                      <span class="ckc-recap-row__value"></span>
                      <span class="ckc-recap-row__price-wrap">
                        <template x-if="t.ml_discount_percent > 0">
                          <span class="ckc-recap-row__price-before" x-text="t.price_before_discount.toFixed(2) + ' €'"></span>
                        </template>
                        <strong class="ckc-recap-row__price" x-text="t.price.toFixed(2) + ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.ttcSuffix)||' € TTC')"></strong>
                      </span>
                    </div>

                    {* Product list for this troncon *}
                    <template x-if="t.products && t.products.length">
                      <div class="ckc-recap-products">
                        <div class="ckc-recap-products__title">{l s='Détail des produits' d='Shop.Theme.Customconfigurator'}</div>
                        <template x-for="p in t.products" :key="p.key">
                          <div class="ckc-recap-product-row">
                            <div class="ckc-recap-product-row__info">
                              <span class="ckc-recap-product-row__name" x-text="p.product_name"></span>
                              <span class="ckc-recap-product-row__attr" x-text="p.attribute_name" x-show="p.attribute_name"></span>
                            </div>
                            <div class="ckc-recap-product-row__qty" x-text="'× ' + p.qty"></div>
                            <div class="ckc-recap-product-row__price" x-text="(p.unit_price * p.qty).toFixed(2) + ' €'"></div>
                          </div>
                        </template>
                      </div>
                    </template>
                  </div>
                </div>

                {*<div class="ckc-recap-card">
                  <div class="ckc-recap-card__header">
                    <span>Tronçon <span x-text="t.index"></span></span>
                    <button @click="$store.configurator.editTroncon(t.index - 1)">✏ Modifier</button>
                  </div>

                  <template x-if="t.fence_type">
                    <div class="ckc-recap-row">
                      <span class="ckc-recap-row__label">Clôture</span>
                      <span class="ckc-recap-row__value" x-text="t.fence_type?.title || '—'"></span>
                    </div>
                  </template>
                  <template x-if="t.range">
                    <div class="ckc-recap-row">
                      <span class="ckc-recap-row__label">Gamme</span>
                      <span class="ckc-recap-row__value" x-text="t.range.title"></span>
                    </div>
                  </template>
                  <template x-if="t.longueur">
                    <div class="ckc-recap-row">
                      <span class="ckc-recap-row__label">Longueur</span>
                      <span class="ckc-recap-row__value" x-text="t.longueur + ' m'"></span>
                    </div>
                  </template>
                  <template x-if="t.height">
                    <div class="ckc-recap-row">
                      <span class="ckc-recap-row__label">Hauteur</span>
                      <span class="ckc-recap-row__value" x-text="t.height.label || t.height"></span>
                    </div>
                  </template>
                  <template x-if="t.install_type">
                    <div class="ckc-recap-row">
                      <span class="ckc-recap-row__label">Pose</span>
                      <span class="ckc-recap-row__value" x-text="t.install_type.title || t.install_type"></span>
                    </div>
                  </template>
                  <template x-if="t.color">
                    <div class="ckc-recap-row">
                      <span class="ckc-recap-row__label">Couleur</span>
                      <span class="ckc-recap-row__value" x-text="t.color.label || t.color"></span>
                    </div>
                  </template>
                  <template x-if="t.post_type">
                    <div class="ckc-recap-row">
                      <span class="ckc-recap-row__label">Poteaux</span>
                      <span class="ckc-recap-row__value" x-text="t.post_type?.title || '—'"></span>
                    </div>
                  </template>

                  <div class="ckc-recap-row">
                    <span class="ckc-recap-row__label">Prix tronçon</span>
                    <span class="ckc-recap-row__value"></span>
                    <strong class="ckc-recap-row__price" x-text="t.price.toFixed(2) + ' €'"></strong>
                  </div>
                </div>*}
              </template>

              {*<div class="ckc-recap-total">
                <span class="ckc-recap-total__label">Total TTC estimé</span>
                <span class="ckc-recap-total__amount"
                  x-text="$store.configurator.summary.total_price.toFixed(2) + ' €'"></span>
              </div>

              <button
                class="ckc-add-to-cart-btn"
                @click="$store.configurator.addToCart()"
                :disabled="$store.configurator.addingToCart"
              >
                <template x-if="$store.configurator.addingToCart">
                  <span>
                    <svg class="ckc-spinner" style="width:20px;height:20px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:.4rem;" viewBox="0 0 24 24"></svg>
                    Ajout en cours…
                  </span>
                </template>
                <template x-if="!$store.configurator.addingToCart">
                  <span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:.4rem;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.99 1.61h9.72a2 2 0 001.95-1.57l1.65-8.42H6"/></svg>
                    Et voilà ! Ajouter le kit au panier
                  </span>
                </template>
              </button>*}

              <template x-if="$store.configurator.cartMessage">
                <div
                  class="ckc-cart-feedback"
                  :class="$store.configurator.cartOk ? 'ckc-cart-feedback--ok' : 'ckc-cart-feedback--err'"
                  x-text="$store.configurator.cartMessage"
                ></div>
              </template>
            </div>
          </template>
        </div>

      </main>{* /ckc-content *}

      {* ---- Sidebar ---- *}
      <aside class="ckc-sidebar" x-show="$store.configurator.currentStep > 1">
        <template x-if="$store.configurator.currentStep < $store.configurator.totalSteps">
          <div class="ckc-sidebar__title">{l s='Votre configuration' d='Shop.Theme.Customconfigurator'}</div>
        </template>
        <template x-if="$store.configurator.currentStep >= $store.configurator.totalSteps">
          <div class="ckc-sidebar__title-recapitulatif">{l s='Récapitulatif de votre commande' d='Shop.Theme.Customconfigurator'}</div>
        </template>
        <template x-if="$store.configurator.currentStep < $store.configurator.totalSteps">
          <div class="ckc-sidebar-type-cloture-step-total-container">
            <div class="ckc-sidebar-type-cloture-step-total-text">
              <div>{l s='Type sélectionné' d='Shop.Theme.Customconfigurator'}</div>
              <p><strong x-text="$store.configurator.fenceTypes.find(f => f.id === $store.configurator.selection.fenceTypeId)?.title || '—'"></strong></p>
            </div>
            <div class="ckc-sidebar-type-cloture-step-total-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="13" viewBox="0 0 18 13" fill="none">
                <path d="M16.2929 0.292893C16.6834 -0.0976312 17.3164 -0.0976309 17.707 0.292893C18.0975 0.683417 18.0975 1.31643 17.707 1.70696L6.70696 12.707C6.31643 13.0975 5.68342 13.0975 5.29289 12.707L0.292893 7.70696C-0.0976311 7.31643 -0.0976311 6.68342 0.292893 6.29289C0.683417 5.90237 1.31643 5.90237 1.70696 6.29289L5.99992 10.5859L16.2929 0.292893Z" fill="black"/>
              </svg>
            </div>
          </div>
        </template>
        <template x-if="$store.configurator.currentStep > 2 && $store.configurator.currentStep < $store.configurator.totalSteps">
          <div class="ckc-sidebar-type-cloture-step-total-container">
            <div class="ckc-sidebar-type-cloture-step-total-text">
              <div>{l s='Spécificités' d='Shop.Theme.Customconfigurator'}</div>
              <p><strong x-text="$store.configurator.installTypes.find(i => i.id === $store.configurator.currentTronconData().installTypeId)?.title || '—'"></strong></p>
              <p class="ckc-sidebar-type-cloture-step-total-text-sm" x-text="($store.configurator.currentTronconData().startOnWall ? ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.wallStart)||'Départ sur mur') : ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.freeStart)||'Départ libre')) + ', ' + ($store.configurator.currentTronconData().endOnWall ? ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.wallEnd)||'fin sur mur') : ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.freeEnd)||'fin libre'))"></p>
            </div>
            <div class="ckc-sidebar-type-cloture-step-total-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="13" viewBox="0 0 18 13" fill="none">
                <path d="M16.2929 0.292893C16.6834 -0.0976312 17.3164 -0.0976309 17.707 0.292893C18.0975 0.683417 18.0975 1.31643 17.707 1.70696L6.70696 12.707C6.31643 13.0975 5.68342 13.0975 5.29289 12.707L0.292893 7.70696C-0.0976311 7.31643 -0.0976311 6.68342 0.292893 6.29289C0.683417 5.90237 1.31643 5.90237 1.70696 6.29289L5.99992 10.5859L16.2929 0.292893Z" fill="black"/>
              </svg>
            </div>
          </div>
        </template>
        <template x-if="$store.configurator.currentStep > 3 && $store.configurator.currentStep < $store.configurator.totalSteps">
          <div class="ckc-sidebar-type-cloture-step-total-container">
            <div class="ckc-sidebar-type-cloture-step-total-text">
              <div>{l s='Gamme' d='Shop.Theme.Customconfigurator'}</div>
              <p><strong x-text="$store.configurator.ranges.find(r => r.id === $store.configurator.currentTronconData().rangeId)?.title || '—'"></strong></p>
              <p class="ckc-sidebar-type-cloture-step-total-text-sm" x-text="($store.configurator.postTypes.find(p => p.id === $store.configurator.currentTronconData().postTypeId)?.title || '-') + (($store.configurator.colors.find(c => c.id === $store.configurator.currentTronconData().colorAttrId)?.label) ? ', ' + $store.configurator.colors.find(c => c.id === $store.configurator.currentTronconData().colorAttrId).label : '')"></p>
            </div>
            <div class="ckc-sidebar-type-cloture-step-total-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="13" viewBox="0 0 18 13" fill="none">
                <path d="M16.2929 0.292893C16.6834 -0.0976312 17.3164 -0.0976309 17.707 0.292893C18.0975 0.683417 18.0975 1.31643 17.707 1.70696L6.70696 12.707C6.31643 13.0975 5.68342 13.0975 5.29289 12.707L0.292893 7.70696C-0.0976311 7.31643 -0.0976311 6.68342 0.292893 6.29289C0.683417 5.90237 1.31643 5.90237 1.70696 6.29289L5.99992 10.5859L16.2929 0.292893Z" fill="black"/>
              </svg>
            </div>
          </div>
        </template>
        <template x-if="$store.configurator.currentStep > 4 && $store.configurator.currentStep < $store.configurator.totalSteps">
          <div class="ckc-sidebar-type-cloture-step-total-container">
            <div class="ckc-sidebar-type-cloture-step-total-text">
              <div>{l s='Dimensions' d='Shop.Theme.Customconfigurator'}</div>
              <p><strong x-text="($store.configurator.currentTronconData().longueur || '—') + 'm × ' + ($store.configurator.heights.find(h => h.id === $store.configurator.currentTronconData().heightAttrId)?.label || '—')"></strong></p>
              <p class="ckc-sidebar-type-cloture-step-total-text-sm" x-text="($store.configurator.currentTronconData().nbAngles || 0) + ' ' + ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.angleS)||'angle(s)')"></p>
            </div>
            <div class="ckc-sidebar-type-cloture-step-total-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="13" viewBox="0 0 18 13" fill="none">
                <path d="M16.2929 0.292893C16.6834 -0.0976312 17.3164 -0.0976309 17.707 0.292893C18.0975 0.683417 18.0975 1.31643 17.707 1.70696L6.70696 12.707C6.31643 13.0975 5.68342 13.0975 5.29289 12.707L0.292893 7.70696C-0.0976311 7.31643 -0.0976311 6.68342 0.292893 6.29289C0.683417 5.90237 1.31643 5.90237 1.70696 6.29289L5.99992 10.5859L16.2929 0.292893Z" fill="black"/>
              </svg>
            </div>
          </div>
        </template>
        <template x-if="$store.configurator.hasSoubassementStep && $store.configurator.currentStep > 4 && $store.configurator.currentStep < $store.configurator.totalSteps">
          <div class="ckc-sidebar-type-cloture-step-total-container">
            <div class="ckc-sidebar-type-cloture-step-total-text">
              <div>{l s='Soubassement' d='Shop.Theme.Customconfigurator'}</div>
              <p><strong x-text="$store.configurator.soubassements.filter(s => $store.configurator.currentTronconData().soubassements[s.id]).map(s => s.title).join(', ') || ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.none)||'Aucun')"></strong></p>
              <p class="ckc-sidebar-type-cloture-step-total-text-sm">-</p>
            </div>
            <div class="ckc-sidebar-type-cloture-step-total-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="13" viewBox="0 0 18 13" fill="none">
                <path d="M16.2929 0.292893C16.6834 -0.0976312 17.3164 -0.0976309 17.707 0.292893C18.0975 0.683417 18.0975 1.31643 17.707 1.70696L6.70696 12.707C6.31643 13.0975 5.68342 13.0975 5.29289 12.707L0.292893 7.70696C-0.0976311 7.31643 -0.0976311 6.68342 0.292893 6.29289C0.683417 5.90237 1.31643 5.90237 1.70696 6.29289L5.99992 10.5859L16.2929 0.292893Z" fill="black"/>
              </svg>
            </div>
          </div>
        </template>
        <template x-if="$store.configurator.currentStep > $store.configurator.stepOptions && $store.configurator.currentStep < $store.configurator.totalSteps">
          <div class="ckc-sidebar-type-cloture-step-total-container">
            <div class="ckc-sidebar-type-cloture-step-total-text">
              <div>{l s='Options' d='Shop.Theme.Customconfigurator'}</div>
              <p><strong x-text="$store.configurator.options.filter(o => $store.configurator.currentTronconData().options[o.id]).map(o => o.title).join(', ') || ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.sansOptions)||'Sans options')"></strong></p>
              <p class="ckc-sidebar-type-cloture-step-total-text-sm" x-text="$store.configurator.options.filter(o => $store.configurator.currentTronconData().options[o.id]).length + ' ' + ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.optionsSelected)||'option(s) sélectionnée(s)')"></p>
            </div>
            <div class="ckc-sidebar-type-cloture-step-total-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="13" viewBox="0 0 18 13" fill="none">
                <path d="M16.2929 0.292893C16.6834 -0.0976312 17.3164 -0.0976309 17.707 0.292893C18.0975 0.683417 18.0975 1.31643 17.707 1.70696L6.70696 12.707C6.31643 13.0975 5.68342 13.0975 5.29289 12.707L0.292893 7.70696C-0.0976311 7.31643 -0.0976311 6.68342 0.292893 6.29289C0.683417 5.90237 1.31643 5.90237 1.70696 6.29289L5.99992 10.5859L16.2929 0.292893Z" fill="black"/>
              </svg>
            </div>
          </div>
        </template>
        <template x-if="$store.configurator.currentStep >= $store.configurator.totalSteps">
          <div class="ckc-sidebar-type-cloture-step-total-container">
            <div class="ckc-sidebar-type-cloture-step-total-text">
              {l s='Configuration totale' d='Shop.Theme.Customconfigurator'}
            </div>
            <div class="ckc-sidebar-type-steps-total-info">
              <div class="ckc-sidebar-type-step-total-info">
                <div class="ckc-sidebar-type-step-total-title">{l s='Type de grillage' d='Shop.Theme.Customconfigurator'}</div>
                <div class="ckc-sidebar-type-step-total-value" x-text="$store.configurator.fenceTypes.find(f => f.id === $store.configurator.selection.fenceTypeId)?.title || '—'"></div>
              </div>
              <div class="ckc-sidebar-type-step-total-info">
                <div class="ckc-sidebar-type-step-total-title">{l s='Longueur totale' d='Shop.Theme.Customconfigurator'}</div>
                <div class="ckc-sidebar-type-step-total-value" x-text="$store.configurator.troncons.reduce((s, t) => s + (parseFloat(t.longueur) || 0), 0) + 'm'"></div>
              </div>
              <div class="ckc-sidebar-type-step-total-info">
                <div class="ckc-sidebar-type-step-total-title">{l s='Type de pose' d='Shop.Theme.Customconfigurator'}</div>
                <div class="ckc-sidebar-type-step-total-value" x-text="$store.configurator.installTypes.find(i => i.id === $store.configurator.currentTronconData().installTypeId)?.title || '—'"></div>
              </div>
              <div class="ckc-sidebar-type-step-total-info">
                <div class="ckc-sidebar-type-step-total-title">{l s='Hauteur' d='Shop.Theme.Customconfigurator'}</div>
                <div class="ckc-sidebar-type-step-total-value" x-text="$store.configurator.heights.find(h => h.id === $store.configurator.currentTronconData().heightAttrId)?.label || '—'"></div>
              </div>
              <div class="ckc-sidebar-type-step-total-info">
                <div class="ckc-sidebar-type-step-total-title">{l s='Couleur' d='Shop.Theme.Customconfigurator'}</div>
                <div class="ckc-sidebar-type-step-total-value" x-text="$store.configurator.colors.find(c => c.id === $store.configurator.currentTronconData().colorAttrId)?.label || '—'"></div>
              </div>
            </div>
          </div>
        </template>
        <template x-if="$store.configurator.currentStep >= $store.configurator.totalSteps && $store.configurator.summary">
          <div class="ckc-sidebar__items-container">
            <template x-for="item in $store.configurator.allSummaryProducts" :key="item.key">
              <div class="ckc-sidebar__item">
                <div class="ckc-sidebar__item-row1">
                  <span class="ckc-sidebar__item-name" x-text="item.product_name || (((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.productPrefix)||'Produit #') + item.id_product)"></span>
                </div>
                <div class="ckc-sidebar__item-row2">
                  <div class="ckc-sidebar__item-info-attribute" x-show="item.attribute_name" x-html="item.attribute_name ? item.attribute_name.split(', ').join('<br>') : ''"></div>
                  <div class="ckc-sidebar__item-qty-selector">
                    <button type="button" class="btn-quantity decrease"
                            @click="$store.configurator.decQty(item.id_product, item.id_product_attribute)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-minus">
                        <path d="M5 12h14"></path>
                      </svg>
                    </button>
                    <span class="ckc-sidebar__item-qty" x-text="item.qty"></span>
                    <button type="button" class="btn-quantity increase"
                            @click="$store.configurator.incQty(item.id_product, item.id_product_attribute)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus">
                        <path d="M5 12h14"></path>
                        <path d="M12 5v14"></path>
                      </svg>
                    </button>
                  </div>
                  <div class="ckc-sidebar__item-price" x-text="item.unit_price ? (item.unit_price * item.qty).toFixed(2) + '€' : ''"></div>
                </div>
              </div>
            </template>
          </div>
        </template>
        <template x-if="$store.configurator.mlDiscountTiers.length">
          <div class="ckc-sidebar-ml-degressive">
            <div class="ckc-sidebar-ml-degressive__title">{l s='Remise dégressive' d='Shop.Theme.Customconfigurator'}</div>
            <table class="ckc-sidebar-ml-degressive__table">
              <thead>
                <tr>
                  <th>{l s='Longueur (m)' d='Shop.Theme.Customconfigurator'}</th>
                  <th>{l s='Remise' d='Shop.Theme.Customconfigurator'}</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="(tier, idx) in $store.configurator.mlDiscountTiers" :key="'ml-tier-' + idx">
                  <tr :class="{ 'is-active': $store.configurator.isMlTierActive(tier) }">
                    <td x-text="tier.from + ' – ' + (tier.to !== null && tier.to !== undefined ? tier.to : '+')"></td>
                    <td x-text="'-' + tier.percent + '%'"></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </template>
        <div class="ckc-footer__price">
          <div class="ckc-footer__total_container">
            <div><span class="ckc-footer__ttc">{l s='Total TTC' d='Shop.Theme.Customconfigurator'}</span></div>
            <div class="ckc-footer__amount-wrap">
              <template x-if="$store.configurator.totalPriceBeforeDiscount && $store.configurator.totalPriceBeforeDiscount > $store.configurator.totalPrice">
                <span class="ckc-footer__amount-before"
                      x-text="$store.configurator.totalPriceBeforeDiscount.toFixed(2) + ' €'"></span>
              </template>
              <span class="ckc-footer__amount"
                    x-text="$store.configurator.totalPrice.toFixed(2) + ' €'"></span>
            </div>
          </div>
          <div  class="ckc-footer__total_note">{l s='Prix indicatif, devis final après configuration complète.' d='Shop.Theme.Customconfigurator'}</div>
          <button class="ckc-footer__add-to-cart btn btn-primary"
                  :disabled="$store.configurator.currentStep < $store.configurator.totalSteps || $store.configurator.addingToCart"
                  @click="$store.configurator.addToCart()"
                  x-text="$store.configurator.addingToCart ? ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.addingToCart)||'Ajout en cours…') : ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.addToCart)||'Ajouter au panier')"></button>
        </div>

        <template x-if="$store.configurator.currentStep >= $store.configurator.totalSteps">
          <div class="ckc-sidebar-final-info-delivery-help">
            <div class="ckc-sidebar-final-info-delivery">
              <div class="ckc-sidebar-final-info-title-container">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
                  <rect width="40" height="40" rx="20" fill="#E8F4F6"/><path d="M28.9981 20.419C28.998 20.4176 28.9971 20.4163 28.9971 20.415C28.9964 20.4143 28.9959 20.4129 28.9951 20.4121C28.9631 20.377 28.9167 20.3307 28.8242 20.2383L24.7617 16.1758C24.6692 16.0832 24.622 16.0369 24.5869 16.0049C24.5857 16.0037 24.5841 16.003 24.583 16.002H24.5811C24.5336 15.9998 24.4678 16 24.3369 16H23V23.6973C23.6124 22.6804 24.7265 22 26 22C27.2714 22 28.3841 22.6782 28.9971 23.6924C28.9973 23.6368 29 23.5734 29 23.5V20.6631C29 20.5322 29.0002 20.4665 28.9981 20.419ZM15 23C13.8954 23 13 23.8954 13 25C13 26.1046 13.8954 27 15 27C16.1046 27 17 26.1046 17 25C17 23.8954 16.1046 23 15 23ZM26 24C25.1716 24 24.5 24.6716 24.5 25.5C24.5 26.3284 25.1716 27 26 27C26.8284 27 27.5 26.3284 27.5 25.5C27.5 24.6716 26.8284 24 26 24ZM31 23.5C31 23.7193 31.0007 23.9339 30.9883 24.1152C30.9753 24.3056 30.9445 24.5316 30.8477 24.7656C30.6447 25.2555 30.2555 25.6447 29.7656 25.8477C29.6654 25.8892 29.5658 25.9165 29.4707 25.9375C29.2553 27.6639 27.7847 29 26 29C24.2368 29 22.7788 27.6961 22.5361 26H18.874C18.43 27.7253 16.8638 29 15 29C13.0859 29 11.486 27.6555 11.0928 25.8594C9.87953 25.4748 9 24.3406 9 23V15.2002C9 14.6567 8.99893 14.1884 9.03028 13.8047C9.06259 13.4094 9.1344 13.016 9.32715 12.6377C9.61472 12.0735 10.0735 11.6147 10.6377 11.3272C11.016 11.1344 11.4094 11.0626 11.8047 11.0303C12.1884 10.9989 12.6567 11 13.2002 11H18.7998C19.3434 11 19.8116 10.9989 20.1953 11.0303C20.5906 11.0626 20.984 11.1344 21.3623 11.3272C21.9264 11.6147 22.3853 12.0734 22.6729 12.6377C22.8656 13.016 22.9374 13.4094 22.9697 13.8047C22.9749 13.8674 22.9779 13.9328 22.9815 14H24.3369C24.5338 14 24.7958 13.994 25.0527 14.0557C25.2057 14.0924 25.3535 14.1467 25.4932 14.2178L25.6309 14.2949L25.791 14.4063C25.9434 14.5244 26.0712 14.6571 26.1758 14.7617L30.2383 18.8242C30.3774 18.9634 30.567 19.1439 30.7051 19.3691L30.7822 19.5059C30.8536 19.6458 30.9076 19.7941 30.9443 19.9473H30.9453C31.0069 20.2041 31 20.4661 31 20.6631V23.5ZM11 23C11 23.2458 11.0892 23.4704 11.2363 23.6445C11.7917 22.1026 13.2668 21 15 21C16.8638 21 18.43 22.2747 18.874 24H21V15.2002C21 14.6236 20.9987 14.2511 20.9756 13.9678C20.9534 13.6962 20.9158 13.5953 20.8906 13.5459C20.7949 13.358 20.6421 13.2053 20.4541 13.1094C20.4047 13.0842 20.3039 13.0466 20.0322 13.0244C19.7489 13.0013 19.3764 13 18.7998 13H13.2002C12.6236 13 12.2511 13.0013 11.9678 13.0244C11.6962 13.0466 11.5953 13.0842 11.5459 13.1094C11.3579 13.2052 11.2052 13.3579 11.1094 13.5459C11.0842 13.5953 11.0466 13.6962 11.0244 13.9678C11.0013 14.2511 11 14.6236 11 15.2002V23Z" fill="#026473"/>
                </svg>
                <div class="ckc-sidebar-final-info-title">{l s='Livraison estimée' d='Shop.Theme.Customconfigurator'}</div>
              </div>
              <div class="ckc-sidebar-final-info-additional-info">{l s='Votre commande sera livrée sous 10 jours ouvrés' d='Shop.Theme.Customconfigurator'}</div>
            </div>
            <div class="ckc-sidebar-final-info-help">
              <div class="ckc-sidebar-final-info-title-container">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
                  <rect width="40" height="40" rx="20" fill="#E8F4F6"/><path d="M28.0001 20.9697V15.5693C28.0001 14.7129 27.9997 14.1307 27.963 13.6807C27.9272 13.2423 27.862 13.0177 27.7823 12.8613C27.5906 12.485 27.2845 12.179 26.9083 11.9873C26.7518 11.9076 26.5273 11.8425 26.089 11.8066C25.6389 11.7699 25.0567 11.7695 24.2003 11.7695H15.7999C14.9435 11.7695 14.3613 11.7699 13.9112 11.8066C13.4728 11.8425 13.2483 11.9076 13.0919 11.9873C12.7156 12.179 12.4096 12.4851 12.2179 12.8613C12.1382 13.0177 12.073 13.2422 12.0372 13.6807C12.0004 14.1307 12.0001 14.7129 12.0001 15.5693V21.7695C12.0001 22.7638 12.0089 23.0648 12.0684 23.2871C12.2534 23.9772 12.7923 24.5162 13.4825 24.7012C13.7048 24.7607 14.0058 24.7695 15.0001 24.7695C15.5524 24.7695 16.0001 25.2172 16.0001 25.7695V27.6885L18.0606 26.04C18.5166 25.6753 18.85 25.4025 19.2325 25.207C19.5533 25.0431 19.8951 24.924 20.2481 24.8516L20.4063 24.8232C20.7789 24.7662 21.1726 24.7695 21.6837 24.7695H24.2003C25.0567 24.7695 25.6389 24.7692 26.089 24.7324C26.5273 24.6966 26.7518 24.6315 26.9083 24.5518C27.2846 24.36 27.5906 24.054 27.7823 23.6777C27.862 23.5213 27.9272 23.2968 27.963 22.8584C27.9997 22.4084 28.0001 21.8261 28.0001 20.9697ZM20.1602 20.7695C20.7124 20.7697 21.1602 21.2173 21.1602 21.7695C21.1602 22.3217 20.7124 22.7694 20.1602 22.7695H20.1495C19.5974 22.7693 19.1495 22.3217 19.1495 21.7695C19.1495 21.2174 19.5974 20.7698 20.1495 20.7695H20.1602ZM19.8018 17.8213C19.803 17.8209 19.8067 17.8201 19.8116 17.8184C19.8215 17.8148 19.8384 17.8086 19.8614 17.7998C19.9083 17.7819 19.9797 17.7534 20.0665 17.7148C20.2428 17.6365 20.4703 17.5218 20.6905 17.375C21.1918 17.0408 21.3691 16.74 21.3692 16.5215V16.5195C21.3696 16.2248 21.266 15.9393 21.0763 15.7139C20.8865 15.4884 20.6227 15.337 20.3321 15.2871C20.0416 15.2373 19.7426 15.292 19.4884 15.4414C19.2343 15.5908 19.0413 15.8255 18.9434 16.1035C18.7602 16.6245 18.189 16.8981 17.6681 16.7148C17.1472 16.5315 16.8735 15.9604 17.0567 15.4395C17.3113 14.7161 17.8138 14.1064 18.4747 13.7178C19.136 13.3291 19.914 13.1867 20.67 13.3164C21.426 13.4461 22.1125 13.8389 22.6065 14.4258H22.6056C23.0995 15.0124 23.3701 15.7548 23.3692 16.5215L23.3585 16.7559C23.2522 17.9026 22.3835 18.65 21.7999 19.0391C21.4582 19.2668 21.1232 19.434 20.878 19.543C20.7542 19.598 20.6493 19.64 20.5733 19.6689C20.5354 19.6834 20.5045 19.695 20.4815 19.7031C20.4702 19.7072 20.4605 19.7104 20.4532 19.7129C20.4496 19.7141 20.446 19.715 20.4434 19.7158C20.4421 19.7163 20.4406 19.7174 20.4395 19.7178H20.4376C19.9137 19.8924 19.3466 19.6098 19.172 19.0859C19.0083 18.5949 19.2471 18.0666 19.7091 17.8584L19.8018 17.8213ZM30.0001 20.9697C30.0001 21.7932 30.0011 22.4711 29.9561 23.0215C29.9102 23.5836 29.8118 24.1006 29.5645 24.5859C29.1811 25.3385 28.569 25.9505 27.8165 26.334C27.3312 26.5813 26.8141 26.6797 26.252 26.7256C25.7017 26.7706 25.0238 26.7695 24.2003 26.7695H21.6837C21.0197 26.7695 20.8264 26.7744 20.6505 26.8105C20.4742 26.8467 20.3031 26.9063 20.1427 26.9883C19.9827 27.07 19.8282 27.1878 19.3097 27.6025L16.9249 29.5107C16.7324 29.6647 16.5343 29.8242 16.3614 29.9385C16.204 30.0426 15.8988 30.229 15.502 30.2295C15.1021 30.23 14.7212 30.0707 14.4415 29.792L14.3282 29.665C14.0806 29.355 14.0348 29.0005 14.0177 28.8125C13.9989 28.6062 14.0001 28.3518 14.0001 28.1055V26.7588C13.6083 26.7459 13.2724 26.7152 12.9649 26.6328C11.5846 26.2629 10.5067 25.185 10.1368 23.8047C9.99193 23.264 10.0001 22.6351 10.0001 21.7695V15.5693C10.0001 14.7458 9.99906 14.068 10.044 13.5176C10.09 12.9555 10.1884 12.4384 10.4356 11.9531C10.8191 11.2006 11.4312 10.5885 12.1837 10.2051C12.669 9.9578 13.1861 9.85941 13.7481 9.81348C14.2985 9.76851 14.9764 9.76953 15.7999 9.76953H24.2003C25.0238 9.76953 25.7017 9.76851 26.252 9.81348C26.8141 9.85941 27.3312 9.95783 27.8165 10.2051C28.569 10.5886 29.1811 11.2006 29.5645 11.9531C29.8118 12.4384 29.9102 12.9555 29.9561 13.5176C30.0011 14.068 30.0001 14.7458 30.0001 15.5693V20.9697Z" fill="#026473"/>
                </svg>
                <div class="ckc-sidebar-final-info-title">{l s='Besoin d\'aide ?' d='Shop.Theme.Customconfigurator'}</div>
              </div>
              <div class="ckc-sidebar-final-info-additional-info">{l s='Nos experts sont disponibles pour vous conseiller' d='Shop.Theme.Customconfigurator'}</div>
              <div class="ckc-sidebar-final-info-additional-info-phone">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                  <path d="M3.39967 1.25241C3.96723 0.965342 4.63745 0.965342 5.20501 1.25241C5.51435 1.40889 5.78036 1.68584 6.09758 2.00306L6.22714 2.13327C6.43614 2.34227 6.61693 2.5176 6.74081 2.70814C7.17166 3.37096 7.17168 4.22566 6.74081 4.88848C6.61693 5.07902 6.43614 5.25436 6.22714 5.46335C6.18887 5.50163 6.16917 5.52119 6.15553 5.53561L6.15422 5.54343C6.15642 5.5483 6.15925 5.55492 6.16334 5.56361C6.16974 5.5772 6.17781 5.59302 6.18808 5.61439C6.61993 6.51379 7.20865 7.35756 7.95566 8.10463C8.70254 8.85151 9.54605 9.44036 10.4452 9.8722C10.4668 9.88252 10.483 9.89049 10.4967 9.89694C10.5046 9.90068 10.5108 9.90326 10.5156 9.90541C10.517 9.90528 10.5188 9.90573 10.5208 9.90541C10.5219 9.9052 10.523 9.90433 10.524 9.9041C10.5384 9.89048 10.5586 9.87147 10.5969 9.83314L10.8912 9.54473C10.985 9.45792 11.0764 9.38146 11.1718 9.31947C11.8345 8.88871 12.6887 8.88892 13.3515 9.31947C13.5422 9.44338 13.718 9.62411 13.927 9.83314L14.2864 10.1925C14.5056 10.4155 14.6905 10.6233 14.8079 10.8553L14.8587 10.9627C15.0942 11.5061 15.077 12.1285 14.8079 12.6606C14.6514 12.9699 14.3739 13.2353 14.0566 13.5525L13.9518 13.658C13.6191 13.9907 13.3739 14.2429 13.037 14.4321C12.7163 14.6122 12.2729 14.7399 11.8853 14.768L11.7232 14.7739C11.3423 14.7728 11.068 14.6911 10.7037 14.5877C8.5749 13.9835 6.56626 12.8429 4.89185 11.1684C3.21743 9.49399 2.0768 7.48536 1.47258 5.35658C1.36919 4.9923 1.28752 4.71807 1.28639 4.33705C1.28513 3.91658 1.42227 3.38987 1.62818 3.02325C1.81736 2.68649 2.06962 2.44118 2.40227 2.10853L2.73691 1.7739C2.95992 1.55472 3.16768 1.36977 3.39967 1.25241ZM4.60344 2.44187C4.41426 2.34618 4.19042 2.34618 4.00123 2.44187C3.93011 2.47787 3.84156 2.55531 3.45045 2.94642L3.34498 3.05124C2.97157 3.42465 2.87076 3.53299 2.79029 3.67624C2.69709 3.84235 2.61915 4.14281 2.61972 4.33314C2.62023 4.50252 2.64501 4.60466 2.75514 4.99265C3.29887 6.90834 4.32502 8.71616 5.83456 10.2257C7.34407 11.7353 9.1519 12.7614 11.0676 13.3051C11.4556 13.4153 11.5578 13.4401 11.7271 13.4406C11.9174 13.4411 12.2179 13.3632 12.384 13.27V13.2693C12.5272 13.1889 12.6356 13.0888 13.009 12.7153L13.1139 12.6098C13.505 12.2188 13.5824 12.1301 13.6184 12.0591C13.7141 11.8698 13.7141 11.6459 13.6184 11.4568C13.5825 11.3857 13.5052 11.2974 13.1139 10.9061L12.9843 10.7758C12.7917 10.5832 12.7107 10.5054 12.664 10.4666L12.6256 10.438C12.4323 10.3123 12.1899 10.2962 11.9843 10.3904L11.8984 10.438C11.8547 10.4663 11.7966 10.5188 11.5396 10.7758C11.4776 10.8379 11.3966 10.9217 11.2975 10.9926C11.0133 11.1961 10.64 11.2792 10.2942 11.2218L10.1484 11.1886C10.0905 11.1717 10.0381 11.1515 9.99146 11.1313L9.86907 11.0747C8.83579 10.5786 7.86795 9.90233 7.01295 9.04733C6.15804 8.19236 5.48172 7.22456 4.9856 6.19121C4.95173 6.12067 4.90529 6.02722 4.87167 5.91192C4.75995 5.52862 4.83523 5.08748 5.06764 4.76283C5.13855 4.66379 5.22243 4.58265 5.28443 4.52064C5.54153 4.26354 5.59448 4.20573 5.62297 4.16192C5.76663 3.94096 5.76663 3.65567 5.62297 3.4347C5.59449 3.39089 5.54157 3.33312 5.28443 3.07598L5.15422 2.94642C4.76301 2.55521 4.67457 2.47786 4.60344 2.44187Z" fill="#026473"/>
                </svg>
                04 22 53 10 34
              </div>
            </div>
          </div>
        </template>
      </aside>

      </div>{* /ckc-layout *}

      {* ---- Footer ---- *}
      <footer class="ckc-footer">

        <div class="ckc-footer__nav">
          <template class="ckc-footer-fixed-total" x-if="$store.configurator.totalPrice">
            <div class="ckc-footer-fixed-total__container">
              <div class="ckc-footer-fixed-total__label">{l s='Total TTC' d='Shop.Theme.Customconfigurator'}</div>
              <div class="ckc-footer-fixed-total__text">
                <template x-if="$store.configurator.totalPriceBeforeDiscount && $store.configurator.totalPriceBeforeDiscount > $store.configurator.totalPrice">
                  <span class="ckc-footer-fixed-total__amount-before" x-text="$store.configurator.totalPriceBeforeDiscount.toFixed(2) + ' €'"></span>
                </template>
                <span class="ckc-price-from">{l s='À partir de' d='Shop.Theme.Customconfigurator'}</span>
                <span class="ckc-footer-fixed-total__amount_int" x-text="Math.floor($store.configurator.totalPrice)"></span>
                <span class="ckc-footer-fixed-total__amount_decimal" x-text="',' + ($store.configurator.totalPrice.toFixed(2).split('.')[1] || '00') + ' €'"></span>
              </div>
            </div>
          </template>
          <div>
            <span class="ckc-footer__etape"
                  x-text="$store.configurator.footerStepLabel"></span>
          </div>
          {* ---- Barre de progression ---- *}
          <div class="ckc-progress" role="progressbar"
               :aria-valuenow="$store.configurator.currentStep"
               :aria-valuemax="$store.configurator.totalSteps">
            <div class="ckc-progress__fill"
                 :style="'width:' + ($store.configurator.currentStep / $store.configurator.totalSteps * 100) + '%'">
            </div>
          </div>

          <button
            class="ckc-btn-prev"
            @click="$store.configurator.prev()"
            x-show="$store.configurator.currentStep > 1"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="9" viewBox="0 0 18 9" fill="none">
              <path d="M5.04983 0.21957C4.75553 -0.0747208 4.27626 -0.0731332 3.98001 0.223114L0.223253 3.97987C0.1881 4.01502 0.156681 4.05391 0.128461 4.09545C0.115559 4.11475 0.106665 4.13538 0.0956331 4.15547C0.0829856 4.17878 0.0687368 4.20103 0.0584921 4.22567C0.0474404 4.25165 0.0411997 4.27868 0.0333541 4.30572C0.027403 4.32606 0.0193131 4.34588 0.0152309 4.36702C-0.00461816 4.46524 -0.00495153 4.56581 0.0142473 4.6639C0.0184561 4.68528 0.0264162 4.70451 0.031965 4.72509C0.0396312 4.75207 0.0456932 4.77907 0.0565728 4.80497C0.0666545 4.82954 0.0807561 4.8517 0.0932497 4.87492C0.104149 4.89495 0.112907 4.91551 0.125681 4.93472C0.153626 4.97609 0.18479 5.01476 0.21971 5.04968L3.95166 8.78164C4.24595 9.07593 4.72523 9.07434 5.02148 8.77809C5.31772 8.48185 5.31931 8.00257 5.02502 7.70828L2.58006 5.26332L16.8011 5.21622C17.2189 5.21483 17.5586 4.87511 17.56 4.45735C17.5614 4.03959 17.2239 3.70211 16.8061 3.7035L2.58507 3.7506L5.04628 1.28939C5.34253 0.993139 5.34412 0.513862 5.04983 0.21957Z" fill="white"/>
            </svg>
            {l s='Précédent' d='Shop.Theme.Customconfigurator'}
          </button>


          <button class="ckc-footer__add-to-cart btn btn-primary"
                  x-show="$store.configurator.currentStep === $store.configurator.totalSteps"
                  :disabled="$store.configurator.currentStep < $store.configurator.totalSteps || $store.configurator.addingToCart"
                  @click="$store.configurator.addToCart()"
                  x-text="$store.configurator.addingToCart ? ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.addingToCart)||'Ajout en cours…') : ((window.CKC_CFG_I18N&&window.CKC_CFG_I18N.addToCart)||'Ajouter au panier')"></button>

          <template x-if="$store.configurator.currentStep < $store.configurator.totalSteps">
            <button
              class="ckc-btn-next"
              @click="$store.configurator.next()"
              :disabled="!$store.configurator.canProceed"
            >
              {l s='Suivant' d='Shop.Theme.Customconfigurator'}
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="9" viewBox="0 0 18 9" fill="none">
                <path d="M12.5102 8.78177C12.8045 9.07606 13.2838 9.07448 13.58 8.77823L17.3368 5.02147C17.372 4.98632 17.4034 4.94744 17.4316 4.90589C17.4445 4.88659 17.4534 4.86596 17.4644 4.84587C17.4771 4.82256 17.4913 4.80031 17.5016 4.77567C17.5126 4.7497 17.5189 4.72266 17.5267 4.69562C17.5327 4.67528 17.5407 4.65546 17.5448 4.63432C17.5647 4.5361 17.565 4.43553 17.5458 4.33744C17.5416 4.31606 17.5336 4.29683 17.5281 4.27626C17.5204 4.24927 17.5144 4.22228 17.5035 4.19637C17.4934 4.1718 17.4793 4.14965 17.4668 4.12642C17.4559 4.1064 17.4472 4.08583 17.4344 4.06662C17.4064 4.02526 17.3753 3.98658 17.3403 3.95166L13.6084 0.219705C13.3141 -0.0745862 12.8348 -0.0729991 12.5386 0.223247C12.2423 0.519495 12.2407 0.998772 12.535 1.29306L14.98 3.73803L0.75894 3.78513C0.341178 3.78651 0.00145966 4.12623 7.55738e-05 4.54399C-0.00130784 4.96176 0.336168 5.29923 0.753931 5.29785L14.975 5.25074L12.5138 7.71196C12.2175 8.0082 12.2159 8.48748 12.5102 8.78177Z" fill="white"/>
              </svg>
            </button>
          </template>
        </div>
      </footer>

    </div>{* /ckc-wrapper *}
  </template>

  {* ---- Bouton Besoin d'aide ---- *}
  <div class="ckc-help-wrap" x-data="{ open: false }">
    <div class="ckc-help-panel" x-show="open" x-transition>
      <p class="ckc-help-panel__title">{l s='Besoin d\'aide ?' d='Shop.Theme.Customconfigurator'}</p>
      <p style="font-size:.8rem;color:var(--ckc-muted);margin:0 0 .75rem;">
        {l s='Nos experts sont disponibles pour vous accompagner dans votre projet.' d='Shop.Theme.Customconfigurator'}
      </p>
      {if isset($phone_number) && $phone_number}
      <a href="tel:{$phone_number|escape:'htmlall'}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.57 19.79 19.79 0 01.01 5.02 2 2 0 012 2.84h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 10.1a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
        {$phone_number|escape:'htmlall'}
      </a>
      {/if}
    </div>
    <button class="ckc-help-btn" @click="open = !open">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <span>{l s='Besoin d\'aide ?' d='Shop.Theme.Customconfigurator'}</span>
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
           :style="open ? 'transform:rotate(180deg)' : ''"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <button class="ckc-scroll-btn">
      <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
  </div>

</div>{* /#ckc-configurator *}

{literal}
<script>
document.addEventListener('alpine:init', () => {
  Alpine.store('configurator', {
    _i18n() { return window.CKC_CFG_I18N || {}; },

    /** Translate DB/API FR strings via catalogue (EN FO fallback). */
    _t(s) {
      if (s == null || s === '') return s;
      const cat = window.CKC_CFG_CATALOGUE;
      if (!cat || typeof s !== 'string') return s;
      if (Object.prototype.hasOwnProperty.call(cat, s)) return cat[s];
      const trimmed = s.trim();
      if (trimmed !== s && Object.prototype.hasOwnProperty.call(cat, trimmed)) return cat[trimmed];
      if (s.indexOf('&') !== -1) {
        const el = document.createElement('textarea');
        el.innerHTML = s;
        const decoded = el.value;
        if (Object.prototype.hasOwnProperty.call(cat, decoded)) return cat[decoded];
        const decodedTrim = decoded.trim();
        if (decodedTrim !== decoded && Object.prototype.hasOwnProperty.call(cat, decodedTrim)) return cat[decodedTrim];
      }
      return s;
    },

    _localizeEntity(obj) {
      if (!obj) return obj;
      if (Array.isArray(obj)) return obj.map((item) => this._localizeEntity(item));
      if (typeof obj !== 'object') return obj;
      const out = { ...obj };
      ['title', 'subtitle', 'description', 'name', 'label', 'content', 'text', 'promo_message', 'fixation_label'].forEach((f) => {
        if (typeof out[f] === 'string') out[f] = this._t(out[f]);
      });
      if (out.info_items) out.info_items = this._localizeEntity(out.info_items);
      if (out.tech_specs) out.tech_specs = this._localizeEntity(out.tech_specs);
      if (out.expert_advice) out.expert_advice = this._localizeEntity(out.expert_advice);
      if (out.options) out.options = this._localizeEntity(out.options);
      return out;
    },

    _localizeAdvice(map) {
      if (!map || typeof map !== 'object') return map || {};
      const out = {};
      Object.keys(map).forEach((k) => { out[k] = this._localizeEntity(map[k]); });
      return out;
    },

    // ── État ──────────────────────────────────────────────────
    loading:        true,
    error:          null,
    tunnelId:       0,
    apiUrl:         '',
    showHelp:       false,

    // Tunnel metadata
    hasSoubassementStep: false,
    stepAdvice:     {},
    // infoItems removed — now per-entity (item.info_items)

    // Navigation
    currentStep:    1,
    multiTroncons:  false,

    // Tronçons
    troncons:       [],
    currentTroncon: 0,

    // Données API
    fenceTypes:     [],
    heights:        [],
    installTypes:   [],
    ranges:         [],
    notAvailablesRanges: [],
    colors:         [],
    postTypes:      [],
    options:        [],
    soubassements:  [],

    // Soubassement attribute data cache: { soubId: { groups: [...], combinations: [...] } }
    soubassementAttrData: {},

    // Options panel
    optionTypes:     [],
    activeOptionType: null,
    optionPanelOpen: false,
    optionPanelLoading: false,
    optionPanelOptionId: null,
    optionPanelGroups: [],
    optionPanelCombinations: [],
    optionPanelSelections: {},

    // Soubassement panel
    soubPanelOpen: false,
    soubPanelLoading: false,
    soubPanelSoubId: null,
    soubPanelGroups: [],
    soubPanelCombinations: [],
    soubPanelSelections: {},

    // Quantity overrides (key = "idProduct_idProductAttribute", value = adjusted qty)
    qtyOverrides: {},

    // Récap / panier
    summary:        null,
    addingToCart:   false,
    cartMessage:    '',
    cartOk:         true,
    totalPrice:     0,
    totalPriceBeforeDiscount: null,
    mlDiscountPercent: 0,
    _serverSidebar: null,

    // ── Getters ───────────────────────────────────────────────
    get selection() {
      return this.troncons[this.currentTroncon] || {};
    },

    // Feature 2: Sidebar — products resolved server-side with correct combinations
    get currentLengthStep() {
      const ft = this.selectedFenceType;
      if (ft && ft.height_mode === 'packs' && ft.pack_step) return ft.pack_step;
      return 0.5;
    },

    snapLength() {
      const t = this.currentTronconData();
      const ml = this.currentMinLength;
      const step = this.currentLengthStep;
      let v = parseFloat(t.longueur) || ml;
      if (v < ml) v = ml;
      // Snap to nearest valid step from min_length
      if (step > 0 && this.selectedFenceType?.height_mode === 'packs') {
        const n = Math.max(1, Math.round((v - ml) / step) + 1);
        v = Math.round((ml + (n - 1) * step) * 1000) / 1000;
      }
      t.longueur = v;
      this.computePrice();
    },

    get currentMinLength() {
      const t = this.currentTronconData();
      // Check if selected height has a specific min_length (pack mode)
      const h = this.heights.find(h => h.id === t.heightAttrId);
      if (h && h.min_length) return h.min_length;
      // Fallback to fence type min_length
      const ft = this.selectedFenceType;
      if (ft && ft.min_length) return ft.min_length;
      return 0.5;
    },

    get allSummaryProducts() {
      if (!this.summary || !this.summary.troncons) return [];
      const acc = {};
      for (const t of this.summary.troncons) {
        for (const p of (t.products || [])) {
          const key = p.id_product + '_' + (p.id_product_attribute || 0);
          if (acc[key]) {
            acc[key].qty += p.qty;
          } else {
            acc[key] = { ...p, key };
          }
        }
      }
      // Apply qty overrides
      const items = Object.values(acc).map(item => {
        const key = item.id_product + '_' + (item.id_product_attribute || 0);
        const qty = this.qtyOverrides[key] !== undefined ? this.qtyOverrides[key] : item.qty;
        return { ...item, qty };
      });
      return items.filter(i => i.qty > 0);
    },

    get selectedFenceType() {
      const t = this.currentTronconData();
      return this.fenceTypes.find(f => f.id === t.fenceTypeId) || null;
    },

    get mlDiscountTiers() {
      const ft = this.selectedFenceType;
      if (!ft || !Array.isArray(ft.ml_discount_tiers)) {
        // #region agent log
        if (this.currentStep > 1) {
          fetch('http://127.0.0.1:7660/ingest/e4eded95-e335-4205-a8d5-964d73fe922f',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'83d6ec'},body:JSON.stringify({sessionId:'83d6ec',runId:'post-fix',hypothesisId:'D',location:'theme/configurateur.tpl:mlDiscountTiers',message:'tiers empty for UI',data:{step:this.currentStep,fenceTypeId:this.currentTronconData()?.fenceTypeId||null,hasFt:!!ft},timestamp:Date.now()})}).catch(()=>{});
        }
        // #endregion
        return [];
      }
      return ft.ml_discount_tiers;
    },

    isMlTierActive(tier) {
      const ml = parseFloat(this.currentTronconData().longueur) || 0;
      const from = parseFloat(tier.from) || 0;
      const to = tier.to !== null && tier.to !== undefined ? parseFloat(tier.to) : null;
      if (ml < from) return false;
      if (to === null) return true;
      return ml <= to;
    },

    _syncMlDiscountDisplay(totalPrice, totalBefore) {
      this.totalPriceBeforeDiscount = totalBefore || null;
      if (totalBefore && totalBefore > totalPrice) {
        this.mlDiscountPercent = Math.round((1 - totalPrice / totalBefore) * 1000) / 10;
      } else {
        this.mlDiscountPercent = 0;
      }
    },

    get sidebarItems() {
      if (!this._serverSidebar || !this._serverSidebar.length) return [];
      const items = this._serverSidebar.map(item => {
        const key = item.id_product + '_' + (item.id_product_attribute || 0);
        const qty = this.qtyOverrides[key] !== undefined ? this.qtyOverrides[key] : item.qty;
        return { ...item, qty };
      });
      return items.filter(i => i.qty > 0);
    },

    // Numéros d'étapes dynamiques
    get stepSoubassement() { return this.hasSoubassementStep ? 5 : null; },
    get stepOptions()      { return this.hasSoubassementStep ? 6 : 5; },
    get stepMultiTroncons() {
      if (!this.multiTroncons) return null;
      return this.stepOptions + 1;
    },
    get totalSteps() {
      return this.stepOptions + (this.multiTroncons ? 1 : 0) + 1;
    },

    // Noms des étapes
    get currentStepName() {
      const I = this._i18n();
      if (this.currentStep === 1) return I.stepType || 'Type de clôture';
      if (this.currentStep === 2) return I.stepSpecs || 'Spécificités';
      if (this.currentStep === 3) return I.stepRange || 'Gamme';
      if (this.currentStep === 4) return I.stepDimensions || 'Dimensions';
      if (this.currentStep === this.stepSoubassement) return I.soubassement || 'Soubassement';
      if (this.currentStep === this.stepOptions) return I.stepOptions || 'Options';
      if (this.multiTroncons && this.currentStep === this.stepMultiTroncons) return I.stepSections || 'Sections';
      if (this.currentStep === this.totalSteps) return I.stepRecap || 'Récapitulatif';
      return this.footerStepLabel;
    },

    /** Footer "Step N" — always via catalogue so EN FO never keeps "Étape". */
    get footerStepLabel() {
      const I = this._i18n();
      const prefix = this._t(I.stepPrefix || 'Étape') || I.stepPrefix || 'Étape';
      return prefix + ' ' + this.currentStep;
    },

    // Validation pour activer le bouton "Continuer"
    get canProceed() {
      const t = this.currentTronconData();
      if (this.currentStep === 1) return !!this.selection.fenceTypeId;
      if (this.currentStep === 2) return !!t.installTypeId;
      if (this.currentStep === 3) return !!t.rangeId && !!t.postTypeId && !!t.colorAttrId;
      if (this.currentStep === 4) return (this.heights.length === 0 || !!t.heightAttrId) && parseFloat(t.longueur) > 0;
      return true;
    },


    // ── Init ──────────────────────────────────────────────────
    async boot({ tunnelId, apiUrl }) {
      this.tunnelId = tunnelId;
      this.apiUrl   = apiUrl;
      this.troncons = [this.newTronconData()];
      this.currentTroncon = 0;

      try {
        const res  = await fetch(apiUrl + '?action=bootstrap&tunnel=' + tunnelId);
        const data = await res.json();

        if (!data.ok) { this.error = data.error || (this._i18n().bootstrapError || 'Erreur bootstrap'); return; }

        this.fenceTypes = this._localizeEntity(data.fence_types || []);

        // #region agent log
        fetch('http://127.0.0.1:7660/ingest/e4eded95-e335-4205-a8d5-964d73fe922f',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'83d6ec'},body:JSON.stringify({sessionId:'83d6ec',runId:'post-fix',hypothesisId:'B',location:'theme/configurateur.tpl:boot',message:'THEME bootstrap fenceTypes ml tiers',data:{count:this.fenceTypes.length,types:(this.fenceTypes||[]).map(f=>({id:f.id,title:f.title,tiersLen:Array.isArray(f.ml_discount_tiers)?f.ml_discount_tiers.length:0}))},timestamp:Date.now()})}).catch(()=>{});
        // #endregion

        const tunnel = data.tunnel || {};
        // hasSoubassementStep is now set per fence_type in selectFenceType()
        this.hasSoubassementStep = false;
        this.stepAdvice = this._localizeAdvice(data.step_advice || {});
        // info_items are now per-entity (in each fence_type, install_type, range object)

      } catch (e) {
        this.error = e.message;
      } finally {
        this.loading = false;
      }
    },

    // ── Sections ──────────────────────────────────────────────
    newTronconData() {
      return {
        fenceTypeId:    null,
        heightAttrId:   null,
        installTypeId:  null,
        startOnWall:    false,
        endOnWall:      false,
        longueur:       5,
        nbAngles:       0,
        rangeId:        null,
        colorAttrId:    null,
        postTypeId: null,
        options:                {},
        optionSelections:       {},
        soubassements:          {},
        soubassementSelections: {},
        cachedPrice:            0,
      };
    },

    currentTronconData() {
      return this.troncons[this.currentTroncon] || this.newTronconData();
    },

    addTroncon() {
      const newData = this.newTronconData();
      // Copy fence type from first troncon (shared across all)
      if (this.troncons.length > 0) {
        newData.fenceTypeId = this.troncons[0].fenceTypeId;
      }
      this.troncons.push(newData);
      this.currentTroncon = this.troncons.length - 1;
      this.currentStep    = 2;
      this._serverSidebar = null;
      this.loadStep(2);
    },

    duplicateLastTroncon() {
      const last = this.troncons[this.troncons.length - 1];
      this.troncons.push({ ...last, options: { ...last.options }, optionSelections: JSON.parse(JSON.stringify(last.optionSelections || {})), soubassements: { ...last.soubassements }, soubassementSelections: JSON.parse(JSON.stringify(last.soubassementSelections || {})) });
      this.currentTroncon = this.troncons.length - 1;
      this.currentStep    = 2;
      this._serverSidebar = null;
    },

    removeTroncon(idx) {
      if (this.troncons.length <= 1) return;
      this.troncons.splice(idx, 1);
      this.currentTroncon = Math.min(this.currentTroncon, this.troncons.length - 1);
    },

    async editTroncon(idx) {
      this.currentTroncon = idx;
      this.currentStep    = 2;
      this._serverSidebar = null;
      // Restore hasSoubassementStep from this troncon's fence type
      const ftId = this.troncons[idx].fenceTypeId;
      const ft = ftId ? this.fenceTypes.find(f => f.id === ftId) : null;
      this.hasSoubassementStep = !!(ft && ft.has_soubassement);
      await this.loadStep(2);
    },

    // ── Navigation ────────────────────────────────────────────
    async goTo(step) {
      this.currentStep = step;
      await this.loadStep(step);
    },

    async next() {
      const next = this.currentStep + 1;
      if (next > this.totalSteps) return;
      this.currentStep = next;
      await this.loadStep(next);
      document.getElementById('ckc-configurator')?.scrollIntoView({ behavior: 'smooth' });
    },

    prev() {
      if (this.currentStep > 1) {
        this.currentStep--;
        document.getElementById('ckc-configurator')?.scrollIntoView({ behavior: 'smooth' });
      }
    },

    // Tri des hauteurs par valeur croissante
    sortHeights(arr) {
      return (arr || []).slice().sort((a, b) =>
        parseFloat(String(a.label).replace('m', '.')) - parseFloat(String(b.label).replace('m', '.'))
      );
    },

    // Charge les gammes pour la pose choisie (+ couleurs/poteaux/hauteurs si une gamme est déjà sélectionnée)
    async loadRanges() {
      const t = this.currentTronconData();
      if (!t.fenceTypeId || !t.installTypeId) return;
      const res  = await fetch(this.apiUrl + '?action=step3', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tunnel_id: this.tunnelId, fence_type_id: t.fenceTypeId, install_type_id: t.installTypeId, range_id: t.rangeId || 0 }),
      });
      const data = await res.json();
      if (data.ok) {
        this.ranges    = this._localizeEntity(data.ranges     || []);
        this.colors    = this._localizeEntity(data.colors     || []);
        this.postTypes = this._localizeEntity(data.post_types || []);
        this.heights   = this._localizeEntity(this.sortHeights(data.heights || []));
        this.notAvailablesRanges = this._localizeEntity(data.not_available_ranges || []);
        if (this.heights.length === 1 && !t.heightAttrId) {
          t.heightAttrId = this.heights[0].id;
        }
      }
    },

    // ── Chargement des données par étape ──────────────────────
    async loadStep(step) {
      const t = this.currentTronconData();

      // Étape 2 — Spécificités : type de pose et connexions murales
      if (step === 2) {
        if (!t.fenceTypeId) return;
        // Types de pose (dépend du type de clôture)
        const res  = await fetch(this.apiUrl + '?action=step2', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tunnel_id: this.tunnelId, fence_type_id: t.fenceTypeId }),
        });
        const data = await res.json();
        if (data.ok) {
          this.installTypes = this._localizeEntity(data.install_types || []);
        }
        // Recharge les gammes (+ couleurs/poteaux/hauteurs) si une pose est déjà choisie
        if (t.installTypeId) {
          await this.loadRanges();
        } else {
          this.ranges = [];
          this.colors = [];
          this.postTypes = [];
          this.heights = [];
        }
      }

      // Étape 3 — Gamme : les choix dépendent du type de pose
      if (step === 3 && t.installTypeId) {
        await this.loadRanges();
      }

      // Étape 4 — Dimensions : les hauteurs sont déjà chargées via la gamme

      if (this.hasSoubassementStep && step === this.stepSoubassement) {
        const res  = await fetch(this.apiUrl + '?action=step_soubassement', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tunnel_id: this.tunnelId, post_type_id: this.currentTronconData().postTypeId || 0 }),
        });
        const data = await res.json();
        if (data.ok) this.soubassements = this._localizeEntity(data.soubassements || []);
      }

      if (step === this.stepOptions) {
        if (!t.fenceTypeId) return;
        const res  = await fetch(this.apiUrl + '?action=step4', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ fence_type_id: t.fenceTypeId, range_id: t.rangeId }),
        });
        const data = await res.json();
        if (data.ok) {
          this.options = this._localizeEntity(data.options || []);
          this.optionTypes = (data.option_types || []).map((ot) => ({
            ...ot,
            label: this._t(ot.label),
            options: this._localizeEntity(ot.options || []),
          }));
          // Ouvrir automatiquement le premier type d'options à l'arrivée
          this.activeOptionType = this.optionTypes.length > 0 ? this.optionTypes[0].key : null;
        }
      }

      if (step === this.totalSteps) await this.loadSummary();
    },

    // ── Sélections ────────────────────────────────────────────
    async selectFenceType(id) {
      const t = this.troncons[this.currentTroncon];
      t.fenceTypeId    = id;
      // Set default longueur to min_length of selected fence type
      const ft = this.fenceTypes.find(f => f.id === id);
      if (ft && ft.min_length) {
        t.longueur = ft.min_length;
      }
      // Per fence_type soubassement toggle
      this.hasSoubassementStep = !!(ft && ft.has_soubassement);
      t.heightAttrId   = null;
      t.installTypeId  = null;
      t.rangeId        = null;
      t.colorAttrId    = null;
      t.postTypeId = null;
      t.options                = {};
      t.optionSelections       = {};
      this._serverSidebar = null;
      t.soubassements          = {};
      t.soubassementSelections = {};
      this.colors      = [];
      this.postTypes   = [];
      this.computePrice();
    },

    // Sélection du type de pose : conditionne les gammes → on réinitialise l'aval
    selectInstallType(id) {
      const t = this.troncons[this.currentTroncon];
      t.installTypeId = id;
      t.rangeId      = null;
      t.colorAttrId  = null;
      t.postTypeId   = null;
      t.heightAttrId = null;
      this.colors    = [];
      this.postTypes = [];
      this.heights   = [];
      this.computePrice();
      this.loadRanges();
    },

    async selectRange(id) {
      const t = this.troncons[this.currentTroncon];
      t.rangeId      = id;
      t.colorAttrId  = null;
      t.postTypeId   = null;
      t.heightAttrId = null;
      this.colors    = [];
      this.postTypes = [];
      this.heights   = [];
      this.computePrice();
      try {
        const res  = await fetch(this.apiUrl + '?action=step3', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tunnel_id: this.tunnelId, fence_type_id: t.fenceTypeId, install_type_id: t.installTypeId, range_id: id }),
        });
        const data = await res.json();
        if (data.ok) {
          this.colors    = data.colors || [];
          this.postTypes = data.post_types || [];
          this.heights   = this.sortHeights(data.heights || []);
          if (this.heights.length === 1 && !t.heightAttrId) {
            t.heightAttrId = this.heights[0].id;
            this.computePrice();
          }
        }
      } catch (e) { /* silent */ }
    },

    selectPostType(id) {

      if(this.troncons[this.currentTroncon].postTypeId == id){
        this.troncons[this.currentTroncon].postTypeId = 0;
      } else {
        this.troncons[this.currentTroncon].postTypeId = id;
      }

      this.computePrice();
    },

    // Toggle option/soubassement
    toggleOption(field, id) {
      const t = this.currentTronconData();
      t[field][id] = !t[field][id];
      if (field === 'options' && !t[field][id]) {
        delete t.optionSelections[id];
      }
      if (field === 'soubassements') {
        if (t[field][id]) {
          this.loadSoubassementAttributes(id);
        } else {
          delete t.soubassementSelections[id];
        }
      }
      this.computePrice();
    },

    // ── Soubassement attributes ──────────────────────────────
    async loadSoubassementAttributes(soubId) {
      if (this.soubassementAttrData[soubId]) return; // already cached
      try {
        const res = await fetch(this.apiUrl + '?action=entity_attributes', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ entity_type: 'soubassement_type', entity_id: soubId }),
        });
        const data = await res.json();
        if (data.ok) {
          this.soubassementAttrData[soubId] = {
            groups: data.attribute_groups || [],
            combinations: data.combinations || [],
          };
        }
      } catch (e) {
        console.error('Error loading soubassement attributes', e);
      }
    },

    soubHasAttributes(soubId) {
      const data = this.soubassementAttrData[soubId];
      if (!data || !data.groups.length) return false;
      const t = this.currentTronconData();
      const pt = this.postTypes.find(p => p.id === t.postTypeId);
      const fixAttr = pt ? parseInt(pt.fixation_type) || 0 : 0;
      let validCombos = data.combinations;
      if (fixAttr) {
        validCombos = data.combinations.filter(c => c.attribute_ids.includes(fixAttr));
      }
      const validAttrIds = new Set();
      for (const c of validCombos) { for (const a of c.attribute_ids) validAttrIds.add(a); }
      for (const group of data.groups) {
        if (fixAttr && group.attributes.some(a => a.id_attribute === fixAttr)) continue;
        const filtered = group.attributes.filter(a => validAttrIds.has(a.id_attribute));
        if (filtered.length > 1) return true;
      }
      return false;
    },

    get soubPanelSoubassement() {
      return this.soubassements.find(s => s.id === this.soubPanelSoubId) || null;
    },

    async openSoubassementPanel(soubId) {
      const t = this.currentTronconData();

      // Load attributes first to check if there are any to configure
      await this.loadSoubassementAttributes(soubId);
      const data = this.soubassementAttrData[soubId];

      // Determine visible groups
      const pt = this.postTypes.find(p => p.id === t.postTypeId);
      const fixAttr = pt ? parseInt(pt.fixation_type) || 0 : 0;
      let visibleGroups = [];

      if (data && data.groups.length) {
        let validCombos = data.combinations;
        if (fixAttr) {
          validCombos = data.combinations.filter(c => c.attribute_ids.includes(fixAttr));
        }
        const validAttrIds = new Set();
        for (const c of validCombos) { for (const a of c.attribute_ids) validAttrIds.add(a); }

        const allGroups = [];
        for (const group of data.groups) {
          if (fixAttr && group.attributes.some(a => a.id_attribute === fixAttr)) continue;
          const filtered = group.attributes.filter(a => validAttrIds.has(a.id_attribute));
          if (filtered.length > 0) {
            allGroups.push({ ...group, attributes: filtered });
          }
        }
        visibleGroups = allGroups.filter(g => g.attributes.length > 1);

        for (let soubkey in t.soubassements){
          if(soubId != soubkey){
            this.removeSoubassement(soubkey);
          }
        }

        // No attributes to choose → simple toggle
        if (visibleGroups.length === 0) {
          if (t.soubassements[soubId]) {
            // Already selected → deselect
            this.removeSoubassement(soubId);
          } else {
            // Select with auto-selections
            t.soubassements[soubId] = true;
            const autoSelections = {};
            for (const g of allGroups) {
              if (g.attributes.length === 1) {
                autoSelections[g.id_attribute_group] = g.attributes[0].id_attribute;
              }
            }
            const mustMatch = [...Object.values(autoSelections)];
            if (fixAttr) mustMatch.push(fixAttr);
            const combo = validCombos.find(c => mustMatch.every(a => c.attribute_ids.includes(a)));
            t.soubassementSelections[soubId] = {
              attributes: autoSelections,
              combinationId: combo ? combo.id_product_attribute : null,
              productId: combo ? combo.id_product : null,
            };
            this.computePrice();
          }
          return;
        }

        // Has attributes to configure → open panel
        this.soubPanelSoubId = soubId;
        this.soubPanelOpen = true;
        this.soubPanelSelections = {};
        this.soubPanelGroups = allGroups;
        this.soubPanelCombinations = validCombos;

        // Pre-fill if already has selections
        if (t.soubassementSelections[soubId]) {
          this.soubPanelSelections = { ...t.soubassementSelections[soubId].attributes };
        }

        // Auto-select groups with only one attribute
        for (const group of allGroups) {
          if (group.attributes.length === 1 && !this.soubPanelSelections[group.id_attribute_group]) {
            this.soubPanelSelections[group.id_attribute_group] = group.attributes[0].id_attribute;
          }
        }
      } else {
        // No attribute data at all → simple toggle
        if (t.soubassements[soubId]) {
          this.removeSoubassement(soubId);
        } else {
          t.soubassements[soubId] = true;
          t.soubassementSelections[soubId] = { attributes: {}, combinationId: null, productId: null };
          this.computePrice();
        }
      }
      this.soubPanelLoading = false;
    },

    selectSoubPanelAttribute(groupId, attrId) {
      this.soubPanelSelections[groupId] = attrId;
    },

    get soubPanelComplete() {
      const visibleGroups = this.soubPanelGroups.filter(g => g.attributes.length > 1);
      return visibleGroups.every(g => !!this.soubPanelSelections[g.id_attribute_group]);
    },

    validateSoubPanel() {
      if (!this.soubPanelComplete) return;
      const t = this.currentTronconData();
      const soubId = this.soubPanelSoubId;
      t.soubassements[soubId] = true;

      const selectedAttrs = Object.values(this.soubPanelSelections);
      // Include fixation_type for matching
      const pt = this.postTypes.find(p => p.id === t.postTypeId);
      const fixAttr = pt ? parseInt(pt.fixation_type) || 0 : 0;
      const mustMatch = [...selectedAttrs];
      if (fixAttr) mustMatch.push(fixAttr);

      const combo = this.soubPanelCombinations.find(c =>
        mustMatch.length > 0 && mustMatch.every(a => c.attribute_ids.includes(a))
      );
      t.soubassementSelections[soubId] = {
        attributes: { ...this.soubPanelSelections },
        combinationId: combo ? combo.id_product_attribute : null,
        productId: combo ? combo.id_product : null,
      };
      this.soubPanelOpen = false;
      this.computePrice();
    },

    removeSoubassement(soubId) {
      const t = this.currentTronconData();
      t.soubassements[soubId] = false;
      delete t.soubassementSelections[soubId];
      this.computePrice();
    },

    selectSoubassementAttribute(soubId, groupId, attrId) {
      const t = this.currentTronconData();
      if (!t.soubassementSelections[soubId]) {
        t.soubassementSelections[soubId] = { attributes: {} };
      }
      t.soubassementSelections[soubId].attributes[groupId] = attrId;

      // Find matching combination considering fixation_type attribute
      const data = this.soubassementAttrData[soubId];
      if (data) {
        const selectedAttrs = Object.values(t.soubassementSelections[soubId].attributes);
        const mustMatch = [...selectedAttrs];
        // Include fixation_type attribute ID if set
        const pt = this.postTypes.find(p => p.id === t.postTypeId);
        const fixAttr = pt ? parseInt(pt.fixation_type) || 0 : 0;
        if (fixAttr) mustMatch.push(fixAttr);

        const combo = data.combinations.find(c =>
          mustMatch.every(aid => c.attribute_ids.includes(aid))
        );
        t.soubassementSelections[soubId].combinationId = combo ? combo.id_product_attribute : null;
        t.soubassementSelections[soubId].productId = combo ? combo.id_product : null;
      }
      this.computePrice();
    },

    // Get available colors for a soubassement, filtered by postTypeId
    getSoubassementColors(soubId) {
      const data = this.soubassementAttrData[soubId];
      if (!data || !data.groups.length) return [];

      const t = this.currentTronconData();
      // Get the fixation_type attribute ID from the selected post type
      const pt = this.postTypes.find(p => p.id === t.postTypeId);
      const fixationAttrId = pt ? parseInt(pt.fixation_type) || 0 : 0;

      // Filter combinations by fixation attribute if set
      let validCombos = data.combinations;
      if (fixationAttrId) {
        validCombos = data.combinations.filter(c => c.attribute_ids.includes(fixationAttrId));
      }

      // Collect distinct color attributes from valid combinations
      const result = [];
      for (const group of data.groups) {
        // Skip fixation group (it's auto-correlated with post type)
        const isFixationGroup = fixationAttrId && group.attributes.some(a => a.id_attribute === fixationAttrId);
        if (isFixationGroup) continue;

        const validAttrIds = new Set();
        for (const combo of validCombos) {
          for (const attrId of combo.attribute_ids) {
            validAttrIds.add(attrId);
          }
        }

        const filteredAttrs = group.attributes.filter(a => validAttrIds.has(a.id_attribute));
        if (filteredAttrs.length > 0) {
          result.push({ ...group, attributes: filteredAttrs });
        }
      }
      return result;
    },

    // ── Option panel ─────────────────────────────────────────
    removeOption(optId) {
      const t = this.currentTronconData();
      t.options[optId] = false;
      delete t.optionSelections[optId];
      this.computePrice();
    },

    async openOptionPanel(optId) {
      const t = this.currentTronconData();
      this.optionPanelOptionId = optId;
      this.optionPanelLoading = true;
      this.optionPanelOpen = false;
      this.optionPanelSelections = {};
      this.optionPanelGroups = [];
      this.optionPanelCombinations = [];
      // Pre-fill if already has selections
      if (t.optionSelections[optId]) {
        this.optionPanelSelections = { ...t.optionSelections[optId].attributes };
      }
      try {
        const res = await fetch(this.apiUrl + '?action=entity_attributes', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ entity_type: 'option_type', entity_id: optId }),
        });
        const data = await res.json();
        if (data.ok) {
          this.optionPanelGroups = data.attribute_groups || [];
          this.optionPanelCombinations = data.combinations || [];

          // Auto-select groups with only one attribute
          for (const group of this.optionPanelGroups) {
            if (group.attributes.length === 1 && !this.optionPanelSelections[group.id_attribute_group]) {
              this.optionPanelSelections[group.id_attribute_group] = group.attributes[0].id_attribute;
            }
          }

          // If no attribute groups, or all groups auto-selected → validate directly without opening
          const visibleGroups = this.optionPanelGroups.filter(g => g.attributes.length > 1);
          if (!this.optionPanelGroups.length || visibleGroups.length === 0) {
            t.options[optId] = true;
            const selectedAttrs = Object.values(this.optionPanelSelections);
            const combo = this.optionPanelCombinations.find(c =>
              selectedAttrs.length > 0 && selectedAttrs.every(a => c.attribute_ids.includes(a))
            );
            t.optionSelections[optId] = {
              attributes: { ...this.optionPanelSelections },
              combinationId: combo ? combo.id_product_attribute : null,
              productId: combo ? combo.id_product : null,
            };
            this.computePrice();
          } else {
            // Real choice to make → open panel now
            this.optionPanelOpen = true;
          }
        }
      } catch (e) {
        console.error('Error loading option attributes', e);
      }
      this.optionPanelLoading = false;
    },

    selectOptionAttribute(groupId, attrId) {
      this.optionPanelSelections[groupId] = attrId;
    },

    get activeOptionTypeOptions() {
      if (!this.activeOptionType) return [];
      const ot = this.optionTypes.find(t => t.key === this.activeOptionType);
      return ot ? ot.options : [];
    },

    get optionPanelComplete() {
      const visibleGroups = this.optionPanelGroups.filter(g => g.attributes.length > 1);
      return visibleGroups.every(g => !!this.optionPanelSelections[g.id_attribute_group]);
    },

    validateOptionPanel() {
      if (!this.optionPanelComplete) return;
      const t = this.currentTronconData();
      const optId = this.optionPanelOptionId;
      const selectedAttrs = Object.values(this.optionPanelSelections);
      const combo = this.optionPanelCombinations.find(c =>
        selectedAttrs.length > 0 && selectedAttrs.every(attrId => c.attribute_ids.includes(attrId))
      );
      t.options[optId] = true;
      t.optionSelections[optId] = {
        attributes: { ...this.optionPanelSelections },
        combinationId: combo ? combo.id_product_attribute : null,
        productId: combo ? combo.id_product : null,
      };
      this.optionPanelOpen = false;
      this.computePrice();
    },

    get optionPanelOption() {
      return this.options.find(o => o.id === this.optionPanelOptionId) || null;
    },

    // ── Steppers ──────────────────────────────────────────────
    stepperInc(field, step = 0.5, max = 9999) {
      const t  = this.currentTronconData();
      t[field] = Math.min(max, +(parseFloat(t[field] || 0) + step).toFixed(2));
      this.computePrice();
    },

    stepperDec(field, step = 0.5, min = 0) {
      const t  = this.currentTronconData();
      t[field] = Math.max(min, +(parseFloat(t[field] || 0) - step).toFixed(2));
      this.computePrice();
    },

    // ── Calcul prix ───────────────────────────────────────────
    computePrice() {
      // Debounced server-side price computation for accuracy
      clearTimeout(this._priceTimer);
      this._priceTimer = setTimeout(() => this._fetchPrice(), 300);
    },

    async _fetchPrice() {
      const troncons = this.troncons.map(t => ({
        fence_type_id:    t.fenceTypeId,
        height_attr_id:   t.heightAttrId,
        install_type_id:  t.installTypeId,
        range_id:         t.rangeId,
        color_attr_id:    t.colorAttrId,
        post_type_id:     t.postTypeId,
        longueur:         t.longueur,
        nb_angles:        t.nbAngles || 0,
        start_on_wall:    t.startOnWall ? 1 : 0,
        end_on_wall:      t.endOnWall ? 1 : 0,
        options:          Object.entries(t.options || {}).filter(([,v]) => v).map(([k]) => parseInt(k)),
        option_selections:       t.optionSelections || {},
        soubassements:           Object.entries(t.soubassements || {}).filter(([,v]) => v).map(([k]) => parseInt(k)),
        soubassement_selections: t.soubassementSelections || {},
      }));
      try {
        const res = await fetch(this.apiUrl + '?action=compute_price', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ troncons, current_troncon: this.currentTroncon }),
        });
        const data = await res.json();
        if (data.ok) {
          this.totalPrice = data.total_price;
          this._syncMlDiscountDisplay(data.total_price, data.total_price_before_discount);
          // #region agent log
          fetch('http://127.0.0.1:7660/ingest/e4eded95-e335-4205-a8d5-964d73fe922f',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'83d6ec'},body:JSON.stringify({sessionId:'83d6ec',runId:'post-fix',hypothesisId:'E',location:'theme/configurateur.tpl:_fetchPrice',message:'THEME price response',data:{total:data.total_price,before:data.total_price_before_discount,mlPct:this.mlDiscountPercent,tiersLen:this.mlDiscountTiers.length,fenceTypeId:this.currentTronconData()?.fenceTypeId||null,longueur:this.currentTronconData()?.longueur||null},timestamp:Date.now()})}).catch(()=>{});
          // #endregion
          if (data.sidebar) {
            this._serverSidebar = data.sidebar;
          }
        }
      } catch (e) { /* silent */ }
    },

    // ── Quantités sidebar ────────────────────────────────────
    _findCurrentItem(idProduct, idProductAttribute) {
      const match = i => i.id_product === idProduct && (i.id_product_attribute || 0) === (idProductAttribute || 0);
      return this.allSummaryProducts.find(match) || this.sidebarItems.find(match) || null;
    },

    incQty(idProduct, idProductAttribute) {
      const key = idProduct + '_' + (idProductAttribute || 0);
      const current = this._findCurrentItem(idProduct, idProductAttribute);
      const qty = this.qtyOverrides[key] !== undefined ? this.qtyOverrides[key] : (current ? current.qty : 1);
      this.qtyOverrides[key] = qty + 1;
      this.computeSidebarTotal();
    },

    decQty(idProduct, idProductAttribute) {
      const key = idProduct + '_' + (idProductAttribute || 0);
      const current = this._findCurrentItem(idProduct, idProductAttribute);
      const qty = this.qtyOverrides[key] !== undefined ? this.qtyOverrides[key] : (current ? current.qty : 1);
      if (qty > 0) this.qtyOverrides[key] = qty - 1;
      this.computeSidebarTotal();
    },

    calcProductQty(p) {
      const t = this.currentTronconData();
      const L = parseFloat(t.longueur) || 5;
      const heightLabel = (this.heights.find(h => h.id === t.heightAttrId)?.label) || '';
      const H = this._parseHeightLabel(heightLabel);
      const A = t.nbAngles || 0;
      const M = t.startOnWall ? 1 : 0;
      const F = t.endOnWall ? 1 : 0;
      const params = p.qty_formula_params || {};
      const panelW = parseFloat(params.panel_width) || 2.5;
      const P = panelW > 0 ? Math.ceil(L / panelW) : 1;
      const qtyBase = p.qty_base || 1;
      const qtyMult = p.qty_multiplier || 0;
      const formula = p.qty_formula || 'base_mult';

      if (formula === 'conditional_height') {
        const rules = Array.isArray(params.rules) ? params.rules : [];
        let picked = null;
        for (const r of rules) {
          const maxH = (r.max_h === '' || r.max_h === null || r.max_h === undefined) ? null : parseFloat(r.max_h);
          if (maxH === null || H <= maxH) { picked = r; break; }
        }
        if (!picked) return 0;
        return this._evalQtyFormula(picked.formula || 'base_mult', picked.params || {}, L, H, A, M, F, P, qtyBase, qtyMult);
      }
      return this._evalQtyFormula(formula, params, L, H, A, M, F, P, qtyBase, qtyMult);
    },

    _parseHeightLabel(label) {
      const s = String(label || '').trim();
      const m = s.match(/^(\d+)m(\d+)$/);
      if (m) return parseFloat(m[1] + '.' + m[2]);
      if (/cm/i.test(s)) return parseFloat(s.replace(/cm/i, '')) / 100;
      return parseFloat(s.replace(/m/gi, '').replace(',', '.')) || 0;
    },

    _evalQtyFormula(formula, params, L, H, A, M, F, P, qtyBase, qtyMult) {
      const x = parseFloat(params.x) || 1;
      const y = parseFloat(params.y) || 0;
      switch (formula) {
        case 'per_panel':        return Math.max(0, Math.ceil(P * x));
        case 'per_panel_plus':   return Math.max(0, Math.ceil(P + x));
        case 'length_div':       return Math.max(0, Math.ceil(L / Math.max(0.01, x)));
        case 'length_div_plus':  return Math.max(0, Math.ceil(L / Math.max(0.01, x)) + Math.round(y));
        case 'wall_angles':      return Math.max(0, M + F + A);
        case 'fixed':            return Math.max(0, Math.round(x));
        case 'custom':
          try {
            var expr = (params.expression || '1')
              .replace(/L/g, L).replace(/H/g, H).replace(/A/g, A)
              .replace(/M/g, M).replace(/F/g, F).replace(/P/g, P)
              .replace(/X/g, x).replace(/Y/g, y)
              .replace(/(\d)\s*x\s*(\d)/g, '$1*$2');
            var result = new Function('return Math.ceil(' + expr + ')')();
            return Math.max(0, Math.round(result));
          } catch(e) { return 1; }
        case 'base_mult':
        default:
          return Math.max(0, Math.ceil(qtyBase + qtyMult * L));
      }
    },

    computeSidebarTotal() {
      // Use summary products on recap step, sidebar items otherwise
      const items = (this.currentStep >= this.totalSteps && this.summary)
        ? this.allSummaryProducts
        : this.sidebarItems;
      let total = 0;
      for (const item of items) {
        total += (item.unit_price || 0) * item.qty;
      }
      this.totalPrice = Math.round(total * 100) / 100;
    },

    // ── Récapitulatif ─────────────────────────────────────────
    async loadSummary() {
      this.summary = null;
      try {
        const res  = await fetch(this.apiUrl + '?action=summary', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            troncons: this.troncons.map(t => ({
              fence_type_id:    t.fenceTypeId,
              height_attr_id:   t.heightAttrId,
              install_type_id:  t.installTypeId,
              range_id:         t.rangeId,
              color_attr_id:    t.colorAttrId,
              post_type_id:     t.postTypeId,
              longueur:         t.longueur,
              nb_angles:        t.nbAngles || 0,
              start_on_wall:    t.startOnWall ? 1 : 0,
              end_on_wall:      t.endOnWall ? 1 : 0,
              options:            Object.entries(t.options || {}).filter(([,v]) => v).map(([k]) => parseInt(k)),
              option_selections:       t.optionSelections || {},
              soubassements:           Object.entries(t.soubassements || {}).filter(([,v]) => v).map(([k]) => parseInt(k)),
              soubassement_selections: t.soubassementSelections || {},
            })),
          }),
        });
        if (!res.ok) {
          const text = await res.text();
          console.error('Summary HTTP error:', res.status, text.substring(0, 500));
          this.error = (this._i18n().serverError || 'Erreur serveur') + ' (' + res.status + ')';
          return;
        }
        const data = await res.json();
        if (data.ok) {
          this.summary    = data;
          this.totalPrice = data.total_price;
          this.qtyOverrides = {};
        } else {
          console.error('Summary error:', data.error);
          this.error = data.error || (this._i18n().summaryError || 'Erreur lors du chargement du récapitulatif.');
        }
      } catch (e) {
        console.error('Summary exception:', e);
        this.error = (this._i18n().summaryErrorPrefix || 'Erreur lors du chargement du récapitulatif :') + ' ' + e.message;
      }
    },

    // ── Ajout au panier ───────────────────────────────────────
    async addToCart() {
      this.addingToCart = true;
      this.cartMessage  = '';
      try {
        const res  = await fetch(this.apiUrl + '?action=add_to_cart', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            troncons: this.troncons.map(t => ({
              fence_type_id:    t.fenceTypeId,
              range_id:         t.rangeId,
              longueur:         t.longueur,
              install_type_id:  t.installTypeId,
              height_attr_id:   t.heightAttrId,
              color_attr_id:    t.colorAttrId,
              post_type_id:     t.postTypeId,
              nb_angles:        t.nbAngles || 0,
              start_on_wall:    t.startOnWall ? 1 : 0,
              end_on_wall:      t.endOnWall ? 1 : 0,
              options:            Object.entries(t.options || {}).filter(([,v]) => v).map(([k]) => parseInt(k)),
              option_selections:       t.optionSelections || {},
              soubassements:           Object.entries(t.soubassements || {}).filter(([,v]) => v).map(([k]) => parseInt(k)),
              soubassement_selections: t.soubassementSelections || {},
            })),
            qty_overrides: this.qtyOverrides || {},
          }),
        });
        const data = await res.json();
        if (data.ok) {
          this.cartOk      = true;
          this.cartMessage = this._i18n().cartSuccess || 'Produits ajoutés au panier avec succès !';
          if (data.cart_url) setTimeout(() => { window.location.href = data.cart_url; }, 1500);
        } else {
          this.cartOk      = false;
          this.cartMessage = data.error || (this._i18n().cartError || 'Erreur lors de l\'ajout au panier.');
        }
      } catch (e) {
        this.cartOk      = false;
        this.cartMessage = e.message;
      } finally {
        this.addingToCart = false;
      }
    },
  });
});
</script>
{/literal}
{/block}
