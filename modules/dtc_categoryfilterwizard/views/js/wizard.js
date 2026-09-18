/**
 * Category Filter Wizard — JavaScript
 * Navigation par étapes + filtrage côté navigateur de la liste produits PrestaShop / CE.
 */
(function () {
  'use strict';

  /**
   * Cherche le conteneur de listing produits PrestaShop le plus proche
   * (compatible ps_facetedsearch, Creative Elements listing widget et le thème classique).
   */
  function findProductListing() {
    var selectors = [
      '#js-product-list',           // ps_facetedsearch + thème Classic
      '.products-selection',        // thème Classic fallback
      '.elementor-widget-ps-widget-products', // widget CE products
      '#products',                  // fallback générique
    ];
    for (var i = 0; i < selectors.length; i++) {
      var el = document.querySelector(selectors[i]);
      if (el) return el;
    }
    return null;
  }

  /**
   * Initialise un wizard.
   * @param {HTMLElement} wizard
   */
  function initWizard(wizard) {
    var steps      = Array.from(wizard.querySelectorAll('.dtc-wizard__step'));
    var dots       = Array.from(wizard.querySelectorAll('.dtc-wizard__dot'));
    var currentStepEl = wizard.querySelector('.dtc-wizard__current-step');

    var currentStep = 0;
    // selections[stepIndex] = { mode: 'custom'|'native', label, productIds?: number[] }
    var selections  = {};
    var liveTimer = null;

    /* ---- navigation ---- */

    function showStep(idx) {
      steps.forEach(function (s, i) {
        s.classList.toggle('dtc-wizard__step--hidden', i !== idx);
      });
      dots.forEach(function (d, i) {
        d.classList.remove('dtc-wizard__dot--active', 'dtc-wizard__dot--done');
        if (i === idx) d.classList.add('dtc-wizard__dot--active');
        else if (i < idx) d.classList.add('dtc-wizard__dot--done');
      });
      currentStep = idx;

      if (currentStepEl) {
        currentStepEl.textContent = String(idx + 1);
      }
    }

    function parseProductIdsCsv(csv) {
      if (!csv) return [];
      if (typeof csv !== 'string') csv = String(csv);
      var parts = csv.split(',');
      var out = [];
      for (var i = 0; i < parts.length; i++) {
        var n = parseInt(parts[i].trim(), 10);
        if (n > 0) out.push(n);
      }
      // unique
      return Array.from(new Set(out));
    }

    function setProductListVisibility(allowedIds) {
      var listing = findProductListing();
      if (!listing) return;

      var allowAll = (allowedIds == null);
      var allowed = {};
      if (!allowAll) {
        (allowedIds || []).forEach(function (id) { allowed[String(id)] = true; });
      }

      listing.querySelectorAll('[data-id-product]').forEach(function (el) {
        var card = el.closest('.js-product') || el;
        if (allowAll) {
          card.style.display = '';
          return;
        }
        var id = el.getAttribute('data-id-product');
        card.style.display = allowed[id] ? '' : 'none';
      });
    }

    function computeAllowedIdsFromSelections() {
      var selList = Object.keys(selections).map(function (k) {
        return selections[parseInt(k, 10)];
      }).filter(function (s) { return !!s; });

      if (selList.length === 0) {
        return null; // tout afficher
      }

      // Intersect uniquement les sélections qui possèdent effectivement une liste de `productIds`.
      // Cela évite que des étapes en mode `native` (sans productIds côté front) cassent le filtrage des étapes précédentes.
      var productLists = selList
          .filter(function (s) { return Array.isArray(s.productIds); })
          .map(function (s) { return s.productIds || []; });

      if (productLists.length === 0) {
        return null; // aucune info produit => afficher tout
      }

      var result = null;
      for (var j = 0; j < productLists.length; j++) {
        var ids = productLists[j] || [];
        if (result === null) {
          result = ids.slice();
          continue;
        }
        var set = {};
        result.forEach(function (id) { set[String(id)] = true; });
        result = ids.filter(function (id) { return set[String(id)]; });
      }

      return result || [];
    }

    /* ---- options cliquables ---- */

    wizard.addEventListener('click', function (e) {
      var btn = e.target.closest('.dtc-wizard__option');
      if (!btn) return;

      var step = btn.closest('.dtc-wizard__step');
      // Désélectionne les autres options de cette étape
      step.querySelectorAll('.dtc-wizard__option').forEach(function (o) {
        o.classList.remove('dtc-wizard__option--selected');
        o.setAttribute('aria-pressed', 'false');
      });
      btn.classList.add('dtc-wizard__option--selected');
      btn.setAttribute('aria-pressed', 'true');

      var stepIdx  = parseInt(step.dataset.step, 10);
      var mode = btn.dataset.mode || 'native';
      var base = { label: btn.dataset.label || '' };

      if (mode === 'custom') {
        var csvIds = btn.dataset.productIds || '';
        var productIds = parseProductIdsCsv(csvIds);
        selections[stepIdx] = {
          mode       : 'custom',
          optionIndex: parseInt(btn.dataset.optionIndex, 10),
          label      : base.label,
          productIds : productIds,
        };
      } else {
        selections[stepIdx] = {
          mode    : 'native',
          type    : step.dataset.sourceType,
          sourceId: step.dataset.sourceId,
          valueId : btn.dataset.value,
          label   : base.label,
        };
      }

      // Active le bouton Suivant / Voir les produits
      var nextBtn   = step.querySelector('.dtc-wizard__btn--next');
      var submitBtn = step.querySelector('.dtc-wizard__btn--submit');
      if (nextBtn)   nextBtn.disabled   = false;
      if (submitBtn) submitBtn.disabled = false;

      // Filtrage "live" dès qu'on sélectionne une option
      // (comme tu veux "je mets les produits liés à l'option").
      if (liveTimer) clearTimeout(liveTimer);
      liveTimer = setTimeout(function () {
        submitWizard(true);
      }, 150);
    });

    /* ---- bouton Suivant ---- */

    wizard.addEventListener('click', function (e) {
      if (!e.target.closest('.dtc-wizard__btn--next')) return;
      showStep(currentStep + 1);
    });

    /* ---- bouton Retour ---- */

    wizard.addEventListener('click', function (e) {
      if (!e.target.closest('.dtc-wizard__btn--prev')) return;
      showStep(currentStep - 1);
    });

    /* ---- bouton Voir les produits ---- */

    wizard.addEventListener('click', function (e) {
      if (!e.target.closest('.dtc-wizard__btn--submit')) return;
      submitWizard();
    });

    /* ---- bouton Recommencer (reset all) ---- */

    wizard.addEventListener('click', function (e) {
      if (!e.target.closest('.dtc-wizard__btn--reset-all')) return;
      resetWizard();
    });

    /* ---- soumission ---- */

    function submitWizard(isLive) {
      // Filtrage côté client (pas d'AJAX => évite les 500)
      var allowedIds = computeAllowedIdsFromSelections();
      setProductListVisibility(allowedIds);

      // Au clic sur "Voir les produits", on scroll vers la liste filtrée.
      // En mode live, on évite de scroller (UX pénible).
      if (!isLive) {
        var listing = findProductListing();
        if (listing) {
          // Offset pour éviter d'arriver trop bas (header sticky / marges thème).
          var top = listing.getBoundingClientRect().top + window.pageYOffset - 140;
          if (top < 0) top = 0;
          window.scrollTo({ top: top, behavior: 'smooth' });
        }
      }
    }

    /* ---- reset ---- */

    function resetWizard() {
      selections = {};
      setProductListVisibility(null);

      steps.forEach(function (step) {
        step.querySelectorAll('.dtc-wizard__option').forEach(function (o) {
          o.classList.remove('dtc-wizard__option--selected');
          o.removeAttribute('aria-pressed');
        });
        var nextBtn   = step.querySelector('.dtc-wizard__btn--next');
        var submitBtn = step.querySelector('.dtc-wizard__btn--submit');
        if (nextBtn)   nextBtn.disabled   = true;
        if (submitBtn) submitBtn.disabled = true;
      });

      showStep(0);
    }
  }

  /* ---- Initialisation au chargement ---- */

  function init() {
    document.querySelectorAll('.dtc-wizard').forEach(initWizard);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
