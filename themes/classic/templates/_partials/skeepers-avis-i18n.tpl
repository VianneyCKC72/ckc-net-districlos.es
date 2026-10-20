<script>
  window.CKC_AVIS_I18N = {
    verifiedReviews: '{l s='Avis vérifiés' d='Shop.Theme.Customckc' js=1}',
    reviews: '{l s='avis' d='Shop.Theme.Customckc' js=1}'
  };
  (function () {
    if (window.__CKC_AVIS_I18N_BOUND__) {
      return;
    }
    window.__CKC_AVIS_I18N_BOUND__ = true;
    var i18n = window.CKC_AVIS_I18N || {};
    var labelReviews = i18n.reviews || 'avis';
    var labelVerified = i18n.verifiedReviews || 'Avis vérifiés';
    function apply() {
      document.querySelectorAll('[data-lang="SKEEPERS_AVIS"]').forEach(function (el) {
        el.textContent = labelReviews;
      });
      document.querySelectorAll('.avis-verifie-logo > span').forEach(function (el) {
        if (/avis v[eé]rifi[eé]s/i.test(el.textContent || '')) {
          el.textContent = labelVerified;
        }
      });
      document.querySelectorAll('.avis_verifies_inserted, .rate-total').forEach(function (el) {
        if (el.querySelector('[data-lang="SKEEPERS_AVIS"]')) return;
        if (/\bavis\b/i.test(el.textContent || '')) {
          el.innerHTML = el.innerHTML.replace(/\bavis\b/gi, labelReviews);
        }
      });
      var avisLinks = document.querySelectorAll('.nav.nav-tabs[role="tablist"] a.avisVerfies');
      for (var i = 1; i < avisLinks.length; i++) {
        var li = avisLinks[i].closest('li.nav-item');
        if (li) li.parentNode.removeChild(li);
      }
    }
    function bind() {
      apply();
      if (typeof MutationObserver === 'undefined') {
        return;
      }
      var busy = false;
      var obs = new MutationObserver(function () {
        if (busy) return;
        busy = true;
        obs.disconnect();
        apply();
        obs.observe(document.body, { childList: true, subtree: true });
        busy = false;
      });
      obs.observe(document.body, { childList: true, subtree: true });
      setTimeout(function () { obs.disconnect(); }, 15000);
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bind);
    } else {
      bind();
    }
  })();
</script>
