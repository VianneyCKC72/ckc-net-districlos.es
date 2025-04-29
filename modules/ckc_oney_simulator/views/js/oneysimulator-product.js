document.addEventListener('click', function (e) {
    const target = e.target.closest('#oneySimulation');
    if (target) {
        e.preventDefault();

        var options = {
            country: "FR",
            language: "FR",
            merchant_guid: "a3b3970f2f3849618e71ef98ecdac55a",
            payment_amount: getProductPrice(),
            filter_by: "filters",
            merchant_name: "DISTRICLOS",
            merchant_orias_number: "En cours d'immatriculation",
            merchant_address: "1200 route des Lucioles, 06560 VALBONNE",
            filters: [
                {"payment_method":"bnpl","payment_method_type":"split","payment_mode":"3x","is_free":true},
                {"payment_method":"bnpl","payment_method_type":"split","payment_mode":"4x","is_free":true},
                {"payment_method":"bnpl","payment_mode":"10x","is_free":true},
                {"payment_method":"bnpl","payment_mode":"12x","is_free":true},
                {"payment_method":"longtermcredit","payment_method_type":"longtermcredit","payment_mode":"24x","is_free":false},
                {"payment_method":"longtermcredit","payment_method_type":"longtermcredit","payment_mode":"36x","is_free":false},
                {"payment_method":"longtermcredit","payment_method_type":"longtermcredit","payment_mode":"48x","is_free":false}
            ],
            errorCallback: function (status, response) {
                //console.log(status, response);
            },
            successCallback: function outputSuccess() {
                let elem = document.getElementById("widgetSuccess");
                if (elem) {
                    elem.classList.remove("hidden");
                    let str = "Widget successfully initialised at " + new Date().toLocaleTimeString();
                    elem.innerHTML = str;
                }
            },
        };

        loadOneyWidget(function () {
            oneyMerchantApp.loadSimulationPopin({
                options
            });
        });
    }

    function getProductPrice() {
        numberbarre = parseFloat(
            $(".price-integer").first().text().replace(/,/g, ".")
            .replace(/€/g, "")
            .replace(/\s+/g, "") + $(".price-decimal").first().text().replace(/,/g, ".")
        );

        return numberbarre;
    }
});
