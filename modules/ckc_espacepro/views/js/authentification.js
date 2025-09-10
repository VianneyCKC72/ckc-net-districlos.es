$(document).ready(function () {


    var espaceProElements = document.querySelectorAll('.espace_pro .ets_solo_social_wrapper');
    espaceProElements.forEach(function (element) {
        element.parentNode.removeChild(element);
    });

    var select = $("#nafCodeSelect").hide();
    var inputHtml = '<input class="form-control pad20" placeholder="Code NAF*" type="text" name="nafCodeInput" id="nafCodeInput">';
    select.after(inputHtml);
    var input = $("#nafCodeInput");

    var selectOptions = select.children('option').map(function () {
        return {label: $(this).text(), value: $(this).val()};
    }).get();

    // Initialisation de l'autocomplete
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

    var tvaSelect = $('#customer-tva');
    var vatNumberInput = $('#customer-vat_number');

    function ajusterChampTva() {
        var isRequired = tvaSelect.val() === "1";
        vatNumberInput.prop('required', isRequired).attr('placeholder', isRequired ? 'Numéro de TVA*' : 'Numéro de TVA');
    }

    tvaSelect.change(ajusterChampTva).trigger('change');

    ajusterChampTva();
    var blockRegister = document.querySelector('.block-register-espace-pro');
    var passwordInput = document.querySelector('section.register-form input[name="password"]');
    var registerSection = document.querySelector('section.register-form');

    if (passwordInput !== null && blockRegister !== null && registerSection !== null) {
        var passwordFormGroup = passwordInput.closest('.form-group');
        blockRegister.parentNode.removeChild(blockRegister);
        passwordFormGroup.parentNode.insertBefore(blockRegister, passwordFormGroup.nextSibling);
    }

    ['file-upload', 'rib-upload'].forEach(id => {
        if (document.getElementById(id) !== null) {
            document.getElementById(id).addEventListener('change', function () {
                var label = document.querySelector(`label[for="${id}"]`);
                label.textContent = this.files[0].name;
            });
        }
    });

    var form = document.getElementById('customer-form');
    var paysSelect = document.getElementById('customer-pays');
    var siretInput = document.getElementById('customer-siret');
    var fileInput = document.getElementById('file-upload'); // Référence à l'input de fichier
    var genderInputs = document.querySelectorAll('input[name="id_gender"]');
    var nafCodeInput = document.getElementById('nafCodeInput'); // Référence à l'input de code NAF

    function verifierSiret() {
        if (siretInput) {
            if (paysSelect && paysSelect.value === "8") { // la France
                siretInput.setAttribute('required', '');
                siretInput.placeholder = 'Siret*'; // Indique que le champ est obligatoire pour la France
            } else {
                siretInput.removeAttribute('required');
                siretInput.placeholder = 'Siret';
            }
        }
    }

    function validerFormulaire(event) {
        var siretValue = siretInput.value;
        var isGenderSelected = Array.from(genderInputs).some(input => input.checked);
        var isFileSelected = fileInput.files.length > 0; // Vérifie si un fichier est sélectionné
        var isRibSelected = document.getElementById('rib-upload').files.length > 0; // Vérifie si le fichier RIB est sélectionné
        var isNafCodeFilled = nafCodeInput.value.trim() !== ''; // Vérifie si le champ code NAF est rempli
        // Récupération de la valeur du code postal (ajoutez l'ID correct pour le champ code postal)
        var codePostalInput = document.getElementById('customer-cp');
        var codePostalValue = codePostalInput ? codePostalInput.value : '';

        if (!isGenderSelected) {
            event.preventDefault();
            alert('Veuillez sélectionner une option de civilité.');
            return;
        }

        if (!isRibSelected) {
            event.preventDefault();
            alert('Veuillez joindre votre RIB.');
            return;
        }

        if (!isFileSelected) {
            event.preventDefault();
            alert('Veuillez joindre un fichier (ex: KBIS).');
            return;
        }

        if (!isNafCodeFilled) {
            event.preventDefault();
            alert('Veuillez remplir le champ Code NAF.');
            nafCodeInput.focus();
            return;
        }

        // Vérification du code postal
        if (!validerCodePostal(codePostalValue)) {
            event.preventDefault();
            alert('Veuillez entrer un code postal valide.');
            codePostalInput.focus();
            return;
        }

        // Vérification spécifique pour le champ Numéro de TVA si "Soumis à la TVA" est coché sur "Oui"
        var isTvaRequired = $('#customer-tva').val() === "1";
        var isVatNumberFilled = vatNumberInput.val().trim() !== '';
        if (isTvaRequired && !isVatNumberFilled) {
            validationMessages.push('Le Numéro de TVA est obligatoire lorsque soumis à la TVA.');
            isFormValid = false;
        }

        if (paysSelect.value === "8") {
            var isSiretValid = siretValue.length === 14 && !isNaN(siretValue) && siretValue.trim() !== '';

            if (!isSiretValid) {
                event.preventDefault();
                alert('Le SIRET doit être composé de 14 chiffres.');
                siretInput.focus();
                return;
            }
        }

        sauvegarderChamps(); // Imaginez que cette fonction existe déjà pour sauvegarder les champs
    }

    if (paysSelect) {
        paysSelect.addEventListener('change', verifierSiret);
    }
    if (form) {
        form.addEventListener('submit', validerFormulaire);
    }

    verifierSiret();

// Fonction pour sauvegarder les valeurs des champs dans le localStorage
    function sauvegarderChamps() {
        document.querySelectorAll('input, select').forEach(function (input) {
            if (
                input.id !== 'nafCodeInput' &&
                input.type !== 'submit' &&
                input.type !== 'password' &&
                input.name !== 'firstname' &&
                input.name !== 'lastname' &&
                input.name !== 'email' &&
                input.name !== 'token' &&
                input.name !== 'alias' &&
                !input.classList.contains('g-recaptcha-response') // Exclut les champs reCAPTCHA
            ) {
                localStorage.setItem(input.name, input.value);
            }
        });
    }

    function restaurerChamps() {
        document.querySelectorAll('input, select').forEach(function (input) {
            if (
                input.type !== 'submit' &&
                input.type !== 'password' &&
                input.type !== 'file' &&
                input.name !== 'firstname' &&
                input.name !== 'lastname' &&
                input.name !== 'email' &&
                input.name !== 'token' &&
                input.name !== 'alias' &&
                !input.classList.contains('g-recaptcha-response') // Exclut les champs reCAPTCHA
            ) {
                var savedValue = localStorage.getItem(input.name);
                if (savedValue) {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        // Pour les radio et checkboxes, vérifiez si la valeur sauvegardée correspond à la valeur de l'input
                        input.checked = input.value === savedValue;
                    } else {
                        // Pour les autres types d'input, restaurez simplement la valeur
                        input.value = savedValue;
                    }
                }
            }
        });
    }

    restaurerChamps();

    var submitButton = document.querySelector('.form-control-submit');

    if (submitButton) {
        // Ajoutez un écouteur d'événements sur le bouton pour sauvegarder les champs au survol
        submitButton.addEventListener('click', function (event) {
            sauvegarderChamps(); // Sauvegarde les champs à chaque survol du bouton de soumission
        });
    }


    function validerCodePostal(codePostal) {
        // Exemple de regex pour les codes postaux français (5 chiffres)
        var regex = /^[0-9]{5}$/;
        return regex.test(codePostal);
    }

    // localStorage.clear()
});
