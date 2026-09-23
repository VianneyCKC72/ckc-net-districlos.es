/**
 * Synchronise les jours du planning vers le champ hidden avant envoi du formulaire.
 */
(function () {
    'use strict';

    function collectScheduleDays(form) {
        var days = [];
        for (var d = 1; d <= 7; d++) {
            var cb = form.querySelector('input[data-dtc-closy-schedule-day="' + d + '"]');
            if (cb && cb.checked) {
                days.push(String(d));
            }
        }
        return days.join(',');
    }

    function syncScheduleDaysField(form) {
        var value = collectScheduleDays(form);
        var hidden = form.querySelector('#dtc-closy-schedule-days-value');
        if (hidden) {
            hidden.value = value;
        }
        var display = document.getElementById('dtc-closy-schedule-days-display');
        if (display) {
            display.textContent = value;
        }
    }

    function init() {
        if (typeof window.hideOtherLanguage !== 'function') {
            window.hideOtherLanguage = function (id) {
                var fields = document.querySelectorAll('.dtc-closy-admin-config .translatable-field');
                fields.forEach(function (el) {
                    el.style.display = el.classList.contains('lang-' + id) ? 'block' : 'none';
                });
            };
        }
        var form = document.querySelector('form.dtc-closy-admin-config');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function () {
            syncScheduleDaysField(form);
        });

        form.querySelectorAll('input[data-dtc-closy-schedule-day]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                syncScheduleDaysField(form);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

