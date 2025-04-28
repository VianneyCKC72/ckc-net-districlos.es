$(document).ready(function () {
    // Initialisation de DataTable
    $('#customers').DataTable({
        "bLengthChange": false, // Cache le menu de changement de nombre d'éléments affichés
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json',
        },
        responsive: true,
        pageLength: 10,
        "order": [[0, "desc"]], // Trie la première colonne (ID) en ordre décroissant
    });

    // Fonction pour afficher un message et rediriger
    function showMessageAndRedirect(message, backgroundColor, customerId, action) {
        var $infoMessage = $('<div></div>', {
            text: message,
            css: {
                position: 'fixed',
                bottom: '20px',
                left: '50%',
                transform: 'translateX(-50%)',
                backgroundColor: backgroundColor,
                color: 'white',
                padding: '10px',
                borderRadius: '5px',
                zIndex: 1000
            }
        }).appendTo('body');

        // Préparation de l'URL de redirection basée sur l'action
        var currentUrl = window.location.href.split('?')[0];
        var newUrl = currentUrl + "?controller=AdminEspacePro&configure=ckc_espacepro&token=" + token;
        newUrl += "&id_customer=" + customerId;

        // Ajout du paramètre spécifique à l'action
        if (action === 'activate') {
            newUrl += "&active=1";
        } else if (action === 'deactivate') {
            newUrl += "&active=0";
        } else if (action === 'accept') {
            newUrl += "&refuse=0"; // Pour accepter, on met refuse à 0
        } else if (action === 'refuse') {
            newUrl += "&refuse=1";
        }

        console.log(newUrl); // Utilisez console.log au lieu d'alert pour un débogage moins intrusif

        setTimeout(function () {
            window.location.href = newUrl;
            $infoMessage.remove();
        }, 1000);
    }

    // Fonction pour obtenir la valeur d'un paramètre GET par nom
    function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    $('.btn-activer, .btn-desactiver, .btn-accepte, .btn-refuse').on('click', function () {
        var customerId = $(this).data('id');
        var action = $(this).hasClass('btn-activer') ? 'activate' :
            $(this).hasClass('btn-desactiver') ? 'deactivate' :
                $(this).hasClass('btn-accepte') ? 'accept' :
                    'refuse'; // Simplifié pour une meilleure lisibilité
        var message = action === 'activate' ? "Activation du client en cours..." :
            action === 'deactivate' ? "Désactivation du client en cours..." :
                action === 'refuse' ? "Traitement du refus du client..." :
                    "Déblocage du contact client en cours...";
        var backgroundColor = action === 'activate' || action === 'accept' ? "rgba(0, 123, 255, 0.9)" :
            "rgba(220, 53, 69, 0.9)";

        showMessageAndRedirect(message, backgroundColor, customerId, action);
    });
});
