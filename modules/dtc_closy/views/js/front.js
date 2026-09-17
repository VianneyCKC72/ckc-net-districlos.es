/**
 * dtc_closy — bulles questions fiche produit
 */
(function () {
    'use strict';

    function usePanel() {
        return window.DtcClosy && typeof window.DtcClosy.isEnabled === 'function' && window.DtcClosy.isEnabled();
    }

    /**
     * @param {string|null} message Texte bulle, ou null pour CTA.
     */
    function openChatWithMessage(message) {
        if (!usePanel()) {
            return;
        }
        if (message == null) {
            window.DtcClosy.open();
        } else {
            window.DtcClosy.openWithMessage(message, { mode: 'bubble' });
        }
    }

    function bind() {
        document.querySelectorAll('.dtc-closy-questions__bubble[data-question]').forEach(function (bubble) {
            if (bubble.getAttribute('data-dtc-closy-bound') === '1') {
                return;
            }
            bubble.setAttribute('data-dtc-closy-bound', '1');
            bubble.addEventListener('click', function (e) {
                e.preventDefault();
                openChatWithMessage(this.getAttribute('data-question') || '');
            });
        });

        document.querySelectorAll('.dtc-closy-questions__bubble[data-action="open-chat"]').forEach(function (cta) {
            if (cta.getAttribute('data-dtc-closy-bound') === '1') {
                return;
            }
            cta.setAttribute('data-dtc-closy-bound', '1');
            cta.addEventListener('click', function (e) {
                e.preventDefault();
                openChatWithMessage(null);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }

    if (window.prestashop && typeof window.prestashop.on === 'function') {
        window.prestashop.on('updatedProduct', bind);
    }
})();
