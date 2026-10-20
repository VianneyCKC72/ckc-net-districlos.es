/**
 * Product Guide - Frontend JavaScript
 * Gestion de l'ouverture/fermeture du panneau latéral
 */

(function() {
    'use strict';

    // Initialiser dès que possible
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductGuide);
    } else {
        // DOM déjà chargé
        initProductGuide();
    }

    // Réinitialiser après une mise à jour de produit (variantes)
    if (typeof prestashop !== 'undefined') {
        prestashop.on('updatedProduct', function() {
            setTimeout(initProductGuide, 300);
        });
    }

    function initProductGuide() {
        var openBtn = document.getElementById('open-guide-btn');
        var closeBtn = document.getElementById('close-guide-btn');
        var panel = document.getElementById('product-guide-panel');
        var overlay = document.getElementById('guide-overlay');

        if (!openBtn || !panel || !overlay) {
            console.log('[ProductGuide] Éléments non trouvés');
            return; // Le guide n'est pas présent sur cette page
        }

        // Déplacer le panneau et l'overlay dans le body pour éviter les problèmes de z-index
        // Cela les sort du contexte de stacking du Swiper
        if (panel.parentNode !== document.body) {
            document.body.appendChild(panel);
        }
        if (overlay.parentNode !== document.body) {
            document.body.appendChild(overlay);
        }

        // Supprimer les anciens événements pour éviter les doublons
        var newOpenBtn = openBtn.cloneNode(true);
        openBtn.parentNode.replaceChild(newOpenBtn, openBtn);
        openBtn = newOpenBtn;

        // Ouvrir le panneau
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openGuide();
        });

        // Fermer le panneau via le bouton
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeGuide();
            });
        }

        // Fermer le panneau via l'overlay
        overlay.addEventListener('click', function() {
            closeGuide();
        });

        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && panel.classList.contains('open')) {
                closeGuide();
            }
        });
    }

    function openGuide() {
        var panel = document.getElementById('product-guide-panel');
        var overlay = document.getElementById('guide-overlay');

        panel.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Ajouter un event tracking si Google Analytics est présent
        if (typeof gtag !== 'undefined') {
            gtag('event', 'open_product_guide', {
                'event_category': 'engagement',
                'event_label': 'Product Guide Opened'
            });
        }
    }

    function closeGuide() {
        var panel = document.getElementById('product-guide-panel');
        var overlay = document.getElementById('guide-overlay');

        panel.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Exposer les fonctions globalement pour usage externe si nécessaire
    window.productGuide = {
        open: openGuide,
        close: closeGuide
    };

})();

