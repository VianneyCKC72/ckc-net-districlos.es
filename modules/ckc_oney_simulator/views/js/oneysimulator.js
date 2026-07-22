document.addEventListener('DOMContentLoaded', function () {
    (function(open) {
        XMLHttpRequest.prototype.open = function(method, url) {
            this.addEventListener('readystatechange', function() {
                if (this.readyState === 4 && (url.includes('panier') || url.includes('cart'))) {
                    debouncedAttach();
                }
            });
            open.apply(this, arguments);
        };
    })(XMLHttpRequest.prototype.open);

    function getTotalAmount() {
        const totalElement = document.querySelector('.cart-summary-line.cart-total .value, .cart-total .value');
        if (totalElement) {
            const totalText = totalElement.textContent.replace(/[^\d,.-]/g, '').replace(',', '.');
            const parsed = parseFloat(totalText);
            return isNaN(parsed) ? 0 : parsed;
        }

        if (typeof prestashop !== 'undefined' && prestashop.cart && prestashop.cart.totals && prestashop.cart.totals.total) {
            const raw = prestashop.cart.totals.total.amount;
            if (typeof raw === 'number') {
                return raw;
            }
            if (typeof raw === 'string') {
                const parsed = parseFloat(raw.replace(',', '.'));
                return isNaN(parsed) ? 0 : parsed;
            }
        }

        return 0;
    }

    function updateFinancingOptions(totalAmount) {
        const desktopOptions = document.querySelectorAll('#paiement-select-desktop .financement-option');

        if (desktopOptions) {
            desktopOptions.forEach(option => {
                const minAmount = parseInt(option.getAttribute('data-min'), 10);
                const maxAmount = parseInt(option.getAttribute('data-max'), 10);
                option.style.display = totalAmount >= minAmount && totalAmount <= maxAmount ? 'block' : 'none';
            });
        }
    }

    function updateFinancingDisplay(totalAmount) {
        if (document.getElementById('financing-discover')) {
            if (totalAmount >= 300 && totalAmount <= 15000) {
                document.getElementById('financing-discover').style.display = 'block';
                document.getElementById('financing-choice').style.display = 'block';
            } else {
                document.getElementById('financing-discover').style.display = 'none';
                document.getElementById('financing-choice').style.display = 'none';
            }
        }
    }

    function attachEventHandlers() {
        const desktopSelect = document.getElementById('paiement-select-desktop');
        if (desktopSelect && !desktopSelect.dataset.oneyBound) {
            desktopSelect.dataset.oneyBound = '1';
            desktopSelect.addEventListener('change', function () {
                const options = [
                    'paymentOption3x', 'paymentOption4x', 'paymentOption10x',
                    'paymentOption12x', 'paymentOption24x', 'paymentOption36x',
                    'paymentOption48x'
                ];

                options.forEach(option => {
                    const elem = document.getElementById(option);
                    if (elem) {
                        elem.style.display = 'none';
                    }
                });

                if (this.value) {
                    const selectedOption = `paymentOption${this.value.slice(4)}`;
                    const selectedElem = document.getElementById(selectedOption);
                    updateSimulationPayments();
                    if (selectedElem) {
                        selectedElem.style.display = 'block';
                    }
                }
            });
        }
    }

    window.attachEventsAndUpdateOptions = function () {
        const totalAmount = getTotalAmount();
        updateFinancingOptions(totalAmount);
        updateFinancingDisplay(totalAmount);
        attachEventHandlers();
        updateSimulationPayments();
    };

    function debounce(fn, delay) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    const debouncedAttach = debounce(attachEventsAndUpdateOptions, 200);

    if (typeof prestashop !== 'undefined' && typeof prestashop.on === 'function') {
        prestashop.on('updateCart', function () {
            attachEventsAndUpdateOptions();
        });
    }

    function isCheckoutPaymentPage() {
        return !!(
            document.getElementById('checkout') ||
            document.body.classList.contains('page-order') ||
            document.querySelector('#checkout-payment-step')
        );
    }

    function updateSimulationPayments() {
        const desktopSelect = document.getElementById('paiement-select-desktop');

        if (desktopSelect) {
            const selectedOption = desktopSelect.selectedOptions[0];

            if (desktopSelect.value && selectedOption) {
                let mode = desktopSelect.value.replace('oney', '');
                let methodType = 'split';

                if (selectedOption.dataset.method === 'longtermcredit') {
                    methodType = 'longtermcredit';
                }

                showSimulationPayment(
                    mode,
                    selectedOption.dataset.placeholder,
                    selectedOption.dataset.free === 'true' || selectedOption.dataset.free === true,
                    selectedOption.dataset.method,
                    methodType
                );
            }
        }

        if (isCheckoutPaymentPage()) {
            loadCheckoutOneyPlaceholders(false);
        }
    }

    function loadCheckoutOneyPlaceholders(force) {
        const placeholders = document.querySelectorAll(
            '.js-oney-checkout-placeholder, #paymentOption10x, #paymentOption12x, #paymentOption24x, #paymentOption36x, #paymentOption48x'
        );

        placeholders.forEach(function (el) {
            if (!el || !el.id) {
                return;
            }
            if (!force && el.querySelector('.oney-checkoutSection')) {
                return;
            }

            const mode = el.dataset.oneyMode || el.id.replace('paymentOption', '');
            if (!mode) {
                return;
            }

            const method = el.dataset.oneyMethod || (
                (mode === '24x' || mode === '36x' || mode === '48x') ? 'longtermcredit' : 'bnpl'
            );
            const methodType = method === 'longtermcredit' ? 'longtermcredit' : 'split';
            const isFree = el.dataset.oneyFree === 'true' || (
                method !== 'longtermcredit' && el.dataset.oneyFree !== 'false'
            );

            showSimulationPayment(mode, '#' + el.id, isFree, method, methodType);
        });
    }

    attachEventsAndUpdateOptions();

    // Le conteneur est souvent ps-hidden au chargement : recharger à la sélection mh_oney
    document.addEventListener('change', function (e) {
        const input = e.target;
        if (!input || input.name !== 'payment-option') {
            return;
        }
        if (input.dataset.moduleName !== 'mh_oney') {
            return;
        }

        setTimeout(function () {
            loadCheckoutOneyPlaceholders(true);
        }, 200);
    });

    function showSimulationPayment(paymentMode, placeholderId, isFree, paymentMethod, paymentMethodType) {
        if (!placeholderId) {
            return;
        }

        var placeholder = document.querySelector(placeholderId);
        if (!placeholder) {
            return;
        }

        var amount = getProductPriceTotal();
        if (!amount || amount <= 0 || isNaN(amount)) {
            console.warn('[oney] Montant panier invalide pour', placeholderId, amount);
            return;
        }

        var options = {
            country: "FR",
            language: "FR",
            merchant_guid: "a3b3970f2f3849618e71ef98ecdac55a",
            payment_amount: amount,
            filter_by: "filters",
            filters: [{
                "payment_method": paymentMethod,
                "payment_method_type": paymentMethodType,
                "payment_mode": paymentMode,
                "is_free": !!isFree
            }],
            checkout_placeholder: placeholderId,
            merchant_name: "DISTRICLOS",
            merchant_orias_number: "En cours d'immatriculation",
            merchant_address: "1200 route des Lucioles, 06560 VALBONNE",
            hide_logo: true,
            errorCallback: function (status, response) {
                console.warn('[oney] Widget error', placeholderId, status, response);
            },
            successCallback: function outputSuccess() {}
        };

        if (typeof loadOneyWidget !== 'function') {
            console.warn('[oney] loadOneyWidget indisponible');
            return;
        }

        loadOneyWidget(function () {
            if (typeof oneyMerchantApp === 'undefined' || !oneyMerchantApp.loadCheckoutSection) {
                console.warn('[oney] oneyMerchantApp.loadCheckoutSection indisponible');
                return;
            }
            oneyMerchantApp.loadCheckoutSection({
                options: options
            });
        });
    }

    function getProductPriceTotal() {
        return getTotalAmount();
    }
});
