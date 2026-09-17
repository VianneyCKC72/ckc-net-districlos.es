$(document).ready(function () {
    function dedupeAvisTabs() {
        var $links = $('.nav.nav-tabs[role="tablist"] a.avisVerfies');
        if ($links.length > 1) {
            $links.slice(1).closest('li.nav-item').remove();
        }
    }

    // Verrou global : évite un double onglet si le script est évalué 2x
    // ou si plusieurs widgets .skeepers_product__stars mutent en parallèle.
    if (window.__CKC_AVIS_TAB_LOCKED__) {
        dedupeAvisTabs();
        return;
    }

    var skeepers = $('.skeepers_product__stars');
    if (!skeepers.length) {
        return;
    }

    window.__CKC_AVIS_TAB_LOCKED__ = true;
    var inserted = false;

    var starSvg =
        '<svg class="star-svg" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 31.575 30.402">' +
        '<defs><linearGradient id="skeepers-31-linearGradient-100">' +
        '<stop offset="0%" stop-color="var(--skeepers-widget-star-color)"></stop>' +
        '<stop offset="100%" stop-color="var(--skeepers-widget-star-color)"></stop>' +
        '</linearGradient></defs>' +
        '<path d="m977.223 685.241-7.775 7.8 2.031 10.988-9.836-5.168-9.678 5.211 1.7-11-8.012-7.767 10.885-1.628 4.726-10.011 5.031 9.99z" transform="translate(-945.648 -673.671)" fill="url(#skeepers-31-linearGradient-100)"></path>' +
        '</svg>';

    var logoSvg =
        '<svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="40px" height="27px" viewBox="0 0 131.000000 130.000000" preserveAspectRatio="xMidYMid meet">' +
        '<g transform="translate(0.000000,130.000000) scale(0.100000,-0.100000)" fill="#0073ff" stroke="none">' +
        '<path d="M618 1223 c-34 -5 -34 -11 -4 -100 l24 -71 -176 -4 c-154 -3 -183 -6 -223 -24 -84 -39 -151 -125 -165 -213 l-7 -41 236 0 235 0 44 131 c24 72 48 131 53 131 5 0 29 -59 53 -131 l44 -131 143 0 143 0 -48 150 c-26 82 -56 162 -66 178 -54 84 -186 142 -286 125z"/>' +
        '<path d="M1105 822 c-27 -19 -108 -78 -180 -131 -71 -52 -131 -95 -132 -96 -1 0 16 -58 37 -129 42 -135 45 -146 33 -146 -5 0 -56 36 -116 80 -59 44 -110 80 -113 80 -3 0 -54 -36 -113 -80 -93 -68 -131 -91 -131 -77 0 2 20 63 45 137 25 73 44 134 42 135 -1 0 -53 38 -115 84 -62 46 -114 82 -116 80 -1 -2 -24 -70 -49 -150 -52 -161 -57 -216 -30 -294 19 -57 92 -136 140 -152 l31 -11 23 79 c12 43 27 79 32 79 4 0 62 -38 128 -85 168 -119 169 -120 269 -120 74 0 91 3 130 27 25 14 56 38 69 52 28 30 33 23 -61 88 -27 18 -47 38 -45 43 2 6 55 47 118 92 63 45 131 100 151 122 46 49 73 133 65 205 -6 56 -35 126 -51 125 -6 0 -33 -17 -61 -37z"/>' +
        '</g></svg>';

    function insertAvisTab(note) {
        dedupeAvisTabs();

        if (inserted || window.__CKC_AVIS_TAB_INSERTED__ || $('.nav.nav-tabs[role="tablist"] a.avisVerfies').length > 0) {
            inserted = true;
            window.__CKC_AVIS_TAB_INSERTED__ = true;
            return;
        }

        // Verrou avant le append pour bloquer tout second appel concurrent.
        inserted = true;
        window.__CKC_AVIS_TAB_INSERTED__ = true;

        var i18n = window.CKC_AVIS_I18N || {};
        var labelVerified = i18n.verifiedReviews || 'Avis vérifiés';

        var avisLi = $(
            '<li class="nav-item">' +
                '<a class="nav-link avisVerfies" data-toggle="tab" href="#netreviews_reviews_tab" role="tab" aria-controls="avisverifies">' +
                    '<div class="avis-verifie-logo">' +
                        logoSvg +
                        '<span>' + labelVerified + '</span>' +
                    '</div>' +
                    '<span class="avis_verifies_inserted">' +
                        starSvg + starSvg + starSvg + starSvg + starSvg +
                        note +
                    '</span>' +
                '</a>' +
            '</li>'
        );

        var navTabs = $('.nav.nav-tabs[role="tablist"]').first();
        if (navTabs.length) {
            navTabs.append(avisLi);
        }
        dedupeAvisTabs();
    }

    function tryInsert() {
        if (inserted || window.__CKC_AVIS_TAB_INSERTED__) {
            dedupeAvisTabs();
            return true;
        }
        var rateElement = skeepers.find('.rate-total');
        if (!rateElement.length) {
            return false;
        }
        var note = rateElement.first().text().trim();
        if (!note.length) {
            return false;
        }
        insertAvisTab(note);
        return inserted;
    }

    if (tryInsert()) {
        return;
    }

    var observer = new MutationObserver(function () {
        if (tryInsert()) {
            observer.disconnect();
        }
    });

    // Observer tous les widgets stars (header + sticky prix), pas seulement le premier.
    skeepers.each(function () {
        observer.observe(this, { childList: true, subtree: true });
    });

    // Filet de sécurité si le widget injecte l'onglet ailleurs / en retard.
    setTimeout(dedupeAvisTabs, 2000);
    setTimeout(dedupeAvisTabs, 5000);
});
