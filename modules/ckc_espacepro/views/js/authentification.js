$(document).ready(function () {

    // On agit uniquement dans l'espace pro
    if (!$('.espace_pro').length) return;

    console.log("🔧 Script espace pro chargé");

    /* ----------------------------------------------------
       1️⃣ Suppression et gestion des éléments spécifiques
    ---------------------------------------------------- */
    var espaceProElements = document.querySelectorAll('.espace_pro .ets_solo_social_wrapper');
    espaceProElements.forEach(function (element) {
        element.parentNode.removeChild(element);
    });

    /* ----------------------------------------------------
       2️⃣ Champ NAF avec autocomplete
    ---------------------------------------------------- */
    var select = $("#nafCodeSelect").hide();
    var inputHtml = '<input class="form-control pad20" placeholder="Code NAF*" type="text" name="nafCodeInput" id="nafCodeInput">';
    select.after(inputHtml);
    var input = $("#nafCodeInput");

    var selectOptions = select.children('option').map(function () {
        return { label: $(this).text(), value: $(this).val() };
    }).get();

    input.autocomplete({
        source: function (request, response) {
            var filteredOptions = $.ui.autocomplete.filter(selectOptions, request.term);
            response(filteredOptions.slice(0, 10));
        },
        select: function (event, ui) {
            select.val(ui.item.value);
        },
        minLength: 0,
    }).focus(function () {
        $(this).autocomplete("search", "");
    });

    /* ----------------------------------------------------
       3️⃣ Ajustement TVA / Numéro de TVA
    ---------------------------------------------------- */
    var tvaSelect = $('#customer-tva');
    var vatNumberInput = $('#customer-vat_number');

    function ajusterChampTva() {
        var isRequired = tvaSelect.val() === "1";
        vatNumberInput.prop('required', isRequired).attr('placeholder', isRequired ? 'Numéro de TVA*' : 'Numéro de TVA');
    }

    tvaSelect.change(ajusterChampTva).trigger('change');

    /* ----------------------------------------------------
       4️⃣ Réorganisation du formulaire (comme création compte)
    ---------------------------------------------------- */
    function reorderForm() {
        const form = document.querySelector(".identity-form form") || document.querySelector("#customer-form");
        if (!form) return;

        const order = [
            ".form-group-id_gender",
            ".form-group-firstname",
            ".form-group-lastname",
            "#customer-company",
            "#customer-siret",
            "#nafCodeInput",
            "#customer-tva",
            "#customer-vat_number",
            "#file-upload",
            "#rib-upload",
            "#customer-adresse",
            "#customer-adresse2",
            "#customer-cp",
            "#customer-ville",
            "#customer-pays",
            "#customer-tel",
            ".form-group-email",
            ".form-group-password",
            ".form-group-new_password",
            ".form-group-psgdpr",
            ".form-group-newsletter",
            "input[name='sms_optin']",
            "#captcha-box",
            ".actions-container-register"
        ];

        const getFieldWrapper = (selector) => {
            const el = form.querySelector(selector);
            if (!el) return null;
            if (el.closest(".form-group")) return el.closest(".form-group");
            if (el.closest(".form-group.row")) return el.closest(".form-group.row");
            if (el.closest(".field-password-policy")) return el.closest(".field-password-policy");
            return el;
        };

        const fragment = document.createDocumentFragment();
        order.forEach((selector) => {
            const field = getFieldWrapper(selector);
            if (field) fragment.appendChild(field);
        });

        form.innerHTML = "";
        form.appendChild(fragment);

        console.log("✅ Champs réordonnés dans .identity-form form / #customer-form");
    }

    reorderForm();

    /* ----------------------------------------------------
       5️⃣ Gestion upload fichiers (label dynamique)
    ---------------------------------------------------- */
    ['file-upload', 'rib-upload'].forEach(id => {
        if (document.getElementById(id) !== null) {
            document.getElementById(id).addEventListener('change', function () {
                var label = document.querySelector(`label[for="${id}"]`);
                if (label && this.files.length > 0) {
                    label.textContent = this.files[0].name;
                }
            });
        }
    });

    /* ----------------------------------------------------
       6️⃣ Vérification des champs à la soumission
    ---------------------------------------------------- */
    var form = document.getElementById('customer-form');
    var paysSelect = document.getElementById('customer-pays');
    var siretInput = document.getElementById('customer-siret');
    var fileInput = document.getElementById('file-upload');
    var genderInputs = document.querySelectorAll('input[name="id_gender"]');
    var nafCodeInput = document.getElementById('nafCodeInput');

    function verifierSiret() {
        if (siretInput) {
            if (paysSelect && paysSelect.value === "8") {
                siretInput.setAttribute('required', '');
                siretInput.placeholder = 'Siret*';
            } else {
                siretInput.removeAttribute('required');
                siretInput.placeholder = 'Siret';
            }
        }
    }

    function validerCodePostal(codePostal) {
        var regex = /^[0-9]{5}$/;
        return regex.test(codePostal);
    }

    function validerFormulaire(event) {
        var siretValue = siretInput.value;
        var isGenderSelected = Array.from(genderInputs).some(input => input.checked);
        var isFileSelected = fileInput.files.length > 0;
        var isRibSelected = document.getElementById('rib-upload').files.length > 0;
        var isNafCodeFilled = nafCodeInput.value.trim() !== '';
        var codePostalInput = document.getElementById('customer-cp');
        var codePostalValue = codePostalInput ? codePostalInput.value : '';

        if (!isGenderSelected) { alert('Veuillez sélectionner une civilité.'); event.preventDefault(); return; }
        if (!isRibSelected) { alert('Veuillez joindre votre RIB.'); event.preventDefault(); return; }
        if (!isFileSelected) { alert('Veuillez joindre un fichier (ex: KBIS).'); event.preventDefault(); return; }
        if (!isNafCodeFilled) { alert('Veuillez remplir le champ Code NAF.'); event.preventDefault(); nafCodeInput.focus(); return; }
        if (!validerCodePostal(codePostalValue)) { alert('Veuillez entrer un code postal valide.'); event.preventDefault(); codePostalInput.focus(); return; }

        if (paysSelect.value === "8") {
            var isSiretValid = siretValue.length === 14 && !isNaN(siretValue);
            if (!isSiretValid) {
                alert('Le SIRET doit comporter 14 chiffres.');
                event.preventDefault();
                siretInput.focus();
                return;
            }
        }
    }

    if (paysSelect) paysSelect.addEventListener('change', verifierSiret);
    if (form) form.addEventListener('submit', validerFormulaire);

    verifierSiret();
});
