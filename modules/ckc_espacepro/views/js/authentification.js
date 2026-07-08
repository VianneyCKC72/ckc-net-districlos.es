$(document).ready(function () {
    // On agit uniquement dans l'espace pro
    if (!$('.espace_pro').length) return;

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
    var selectedNaf = $("#nafCodeSelect").find(":selected").val()
    if(typeof selectedNaf != 'undefined'){
        var inputHtml = '<input class="form-control pad20" placeholder="Code NAF*" type="text" name="nafCodeInput" value="'+selectedNaf+'" id="nafCodeInput">';
    } else {
        var inputHtml = '<input class="form-control pad20" placeholder="Code NAF*" type="text" name="nafCodeInput" id="nafCodeInput">';
    }

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
    function ajusterChampTva() {
        var isRequired = $('#customer-tva').val() === "1";
        $('#customer-vat_number')
            .prop('required', isRequired)
            .attr('placeholder', isRequired ? 'Numéro de TVA*' : 'Numéro de TVA');
    }

    function bindTvaHandlers() {
        $('#customer-tva').off('change.tvaPro').on('change.tvaPro', ajusterChampTva).trigger('change');
    }

    document.addEventListener('ckc:sirene-tva', function (e) {
        var detail = e.detail || {};
        if (detail.tva !== undefined) {
            $('#customer-tva').val(String(detail.tva)).trigger('change');
        }
        if (detail.vat_number) {
            $('#customer-vat_number').val(detail.vat_number);
        }
        ajusterChampTva();
    });

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

    }

    reorderForm();

    /* ----------------------------------------------------
       Formulaire inscription pro en 3 étapes (uniquement page registration)
    ---------------------------------------------------- */
    var STEP_SELECTORS = {
        1: ['#customer-company', '#customer-siret', '#nafCodeInput', '#nafCodeSelect', '#customer-tva', '#customer-vat_number', '#file-upload', '#rib-upload'],
        2: ['#customer-adresse', '#customer-adresse2', '#customer-cp', '#customer-ville', '#customer-pays']
    };

    function getStepForNode(node) {
        if (!node || !node.querySelector) return 3;
        for (var s = 1; s <= 2; s++) {
            for (var i = 0; i < STEP_SELECTORS[s].length; i++) {
                if (node.querySelector(STEP_SELECTORS[s][i])) return s;
            }
        }
        return 3;
    }

    function initRegistrationSteps() {
        if (!$('body#registration.registration-pro').length) return;
        var form = document.getElementById('customer-form');
        if (!form) return;

        var children = Array.from(form.children);
        if (!children.length) return;

        var stepTitles = { 1: 'Votre société', 2: 'Coordonnées', 3: 'Informations personnelles' };
        var steps = [];
        var stepDiv;

        for (var s = 1; s <= 3; s++) {
            stepDiv = document.createElement('div');
            stepDiv.className = 'registration-step registration-step-' + s;
            stepDiv.setAttribute('data-step', String(s));
            stepDiv.insertAdjacentHTML('afterbegin',
                '<div class="registration-step-header">' +
                '<span class="registration-step-header-num">' + s + '</span>' +
                '<span class="registration-step-header-title">' + stepTitles[s] + '</span></div>');
            steps.push(stepDiv);
        }

        children.forEach(function (node) {
            steps[getStepForNode(node) - 1].appendChild(node);
        });

        var indicatorParts = [];
        for (var i = 1; i <= 3; i++) {
            if (i > 1) indicatorParts.push('<span class="registration-step-connector" aria-hidden="true"></span>');
            indicatorParts.push('<div class="registration-step-item' + (i === 1 ? ' active' : '') + '" data-step="' + i + '">' +
                '<span class="registration-step-num">' + i + '</span><span class="registration-step-label">' + stepTitles[i] + '</span></div>');
        }

        var stepIndicator = document.createElement('div');
        stepIndicator.className = 'registration-steps-indicator';
        stepIndicator.innerHTML = indicatorParts.join('');

        var btnNext = '<button type="button" class="btn btn-primary js-registration-next">Prochaine étape</button>';
        var btnPrev = '<button type="button" class="btn btn-link js-registration-prev">Précédent</button>';

        function createActionsEl(html, isPrevOnly) {
            var el = document.createElement('div');
            el.className = 'registration-step-actions' + (isPrevOnly ? ' registration-step-actions--prev' : '');
            el.innerHTML = html;
            return el;
        }

        steps[0].appendChild(createActionsEl(btnNext));
        steps[1].appendChild(createActionsEl(btnPrev + ' ' + btnNext));
        var registerContainer = steps[2].querySelector('.actions-container-register');
        if (registerContainer) {
            var prevWrap = createActionsEl(btnPrev, true);
            prevWrap.style.marginBottom = '0';
            registerContainer.insertBefore(prevWrap, registerContainer.firstChild);
        }

        form.innerHTML = '';
        form.appendChild(stepIndicator);
        steps.forEach(function (el) { form.appendChild(el); });

        steps[1].classList.add('registration-step-hidden');
        steps[2].classList.add('registration-step-hidden');

        var currentStep = 1;

        function updateIndicator() {
            stepIndicator.querySelectorAll('.registration-step-item').forEach(function (el, i) {
                el.classList.toggle('active', (i + 1) === currentStep);
            });
        }

        function showStep(stepNum) {
            currentStep = stepNum;
            steps.forEach(function (el, i) {
                el.classList.toggle('registration-step-hidden', (i + 1) !== stepNum);
            });
            updateIndicator();
        }

        function validateStep(stepNum) {
            var stepEl = steps[stepNum - 1];
            var firstInvalid = Array.prototype.find.call(stepEl.querySelectorAll('[required]'), function (el) {
                var input = el.tagName === 'INPUT' || el.tagName === 'SELECT' ? el : el.querySelector('input, select');
                return input && (!input.value || (input.type === 'file' && !input.files.length));
            });
            if (firstInvalid) {
                var inputToFocus = firstInvalid.tagName === 'INPUT' || firstInvalid.tagName === 'SELECT' ? firstInvalid : firstInvalid.querySelector('input, select');
                if (inputToFocus) inputToFocus.focus();
                return false;
            }
            return true;
        }

        form.addEventListener('click', function (e) {
            if (e.target.closest('.js-registration-next') && currentStep < 3 && validateStep(currentStep)) showStep(currentStep + 1);
            if (e.target.closest('.js-registration-prev') && currentStep > 1) showStep(currentStep - 1);
        });
    }

    initRegistrationSteps();
    bindTvaHandlers();

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
        if (!isRibSelected && $("body#registration").length) { alert('Veuillez joindre votre RIB.'); event.preventDefault(); return; }
        if (!isFileSelected && $("body#registration").length) { alert('Veuillez joindre un fichier (ex: KBIS).'); event.preventDefault(); return; }
        if (!isNafCodeFilled && $("body#registration").length) { alert('Veuillez remplir le champ Code NAF.'); event.preventDefault(); nafCodeInput.focus(); return; }
        if (!validerCodePostal(codePostalValue) && $("body#registration").length) { alert('Veuillez entrer un code postal valide.'); event.preventDefault(); codePostalInput.focus(); return; }

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
