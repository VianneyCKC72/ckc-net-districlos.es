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
    
    // Fonction pour obtenir le montant total à partir du DOM
    function getTotalAmount() {
        const totalElement = document.querySelector('.cart-summary-line.cart-total .value');
        if (totalElement) {
            // Extraire le texte du total et le convertir en nombre
            const totalText = totalElement.textContent.replace(/[^\d,.-]/g, '').replace(',', '.');
            return parseFloat(totalText);
        }
        return 0;
    }

    // Fonction qui met à jour les options de financement
    function updateFinancingOptions(totalAmount) {
        const desktopOptions = document.querySelectorAll('#paiement-select-desktop .financement-option');

        // Mise à jour des options pour la version desktop
        if (desktopOptions) {
            desktopOptions.forEach(option => {
                const minAmount = parseInt(option.getAttribute('data-min'), 10);
                option.style.display = totalAmount >= minAmount ? 'block' : 'none';
            });
        }
    }

    // Mise à jour des options pour la version desktop
    function updateFinancingDisplay(totalAmount) {
        if (totalAmount >= 300 && totalAmount <= 15000) {
            document.getElementById('financing-discover').style.display = 'block'
            document.getElementById('financing-choice').style.display = 'block'
        }else{
            document.getElementById('financing-discover').style.display = 'none'
            document.getElementById('financing-choice').style.display = 'none'
        }
    }

    // Fonction qui attache les événements nécessaires
    function attachEventHandlers() {
        // Desktop handler
        const desktopSelect = document.getElementById('paiement-select-desktop');
        if (desktopSelect) {
            desktopSelect.addEventListener('change', function () {
                const options = [
                    'paymentOption3x', 'paymentOption4x', 'paymentOption10x',
                    'paymentOption12x', 'paymentOption24x', 'paymentOption36x',
                    'paymentOption48x'
                ];

                // Masquer toutes les options de paiement
                options.forEach(option => {
                    const elem = document.getElementById(option);
                    if (elem) {
                        elem.style.display = 'none';
                    }
                });

                // Afficher l'option sélectionnée
                if (this.value) {
                    const selectedOption = `paymentOption${this.value.slice(4)}`;
                    const selectedElem = document.getElementById(selectedOption);
                    updateSimulationPayments()
                    if (selectedElem) {
                        selectedElem.style.display = 'block';
                    }
                }
            });
        }
    }

    // Fonction qui attache les événements et met à jour les options de financement
    window.attachEventsAndUpdateOptions = function () {
        // Mise à jour du montant total et des options de financement
        const totalAmount = getTotalAmount();
        updateFinancingOptions(totalAmount);
        updateFinancingDisplay(totalAmount); // Mise à jour de l'affichage des éléments de financement
        attachEventHandlers();  // Attacher les événements à chaque mise à jour

        // Actualiser les simulations de paiement avec le nouveau montant total
        updateSimulationPayments(totalAmount);
    };
    
    function debounce(fn, delay) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }
    
    const debouncedAttach = debounce(attachEventsAndUpdateOptions, 200);

    // Fonction qui met à jour les simulations de paiement
    function updateSimulationPayments() {
        let totalAmount = getTotalAmount();
        const desktopSelect = document.getElementById('paiement-select-desktop');
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
                selectedOption.dataset.free,
                selectedOption.dataset.method,
                methodType
            );
        }
    }


    // Appel initial lors du chargement de la page
    attachEventsAndUpdateOptions();

    function showSimulationPayment(paymentMode, placeholderId, isFree, paymentMethod, paymentMethodType) {
        var options = {
            country: "FR",
            language: "FR",
            merchant_guid: "a3b3970f2f3849618e71ef98ecdac55a",
            payment_amount: getProductPriceTotal(),
            filter_by: "filters",
            filters: [{
                "payment_method": paymentMethod,
                "payment_method_type": paymentMethodType,
                "payment_mode": paymentMode,
                "is_free": isFree
            }],
            checkout_placeholder: placeholderId,
            merchant_name: "DISTRICLOS",
            merchant_orias_number: "En cours d'immatriculation",
            merchant_address: "1200 route des Lucioles, 06560 VALBONNE",
            hide_logo: true,
            errorCallback: function (status, response) {
                //console.log(status, response);
            },
            successCallback: function outputSuccess() {
                
            },
        };
        
        // Charger le widget sans appeler la fonction de manière récursive
        loadOneyWidget(function () {
            oneyMerchantApp.loadCheckoutSection({
                options
            });
        });
    }

    function getProductPriceTotal() {
        if ($(".cart-total .value").length) {
            monTotalCart = parseFloat(
                $(".cart-total .value")
                    .html()
                    .replace(/,/g, ".")
                    .replace(/€/g, "")
                    .replace(/\s+/g, "")
            );

            return monTotalCart;
        }
        return 0;
    }
});
