$(document).ready(function () {

    $('.exitCart').click(function (e) {
        e.preventDefault()
        $('#confirmModal').modal('show')
    })

    if ($('body').hasClass('page-order')) {

        // Supprimer le doublon des boutons sociaux
        $('#checkout-guest-form .ets_solo_social_wrapper:not(:first)').remove();

        // Déplacer "Recevoir les bons plans et promotions par SMS" après la checkbox newsletter
        if ($('input[name="sms_optin"]').length && $('input[name="newsletter"]').length) {
            $('input[name="sms_optin"]').closest('.form-group.row')
                .insertAfter($('input[name="newsletter"]').closest('.form-group'));
        }

        // Vérifier et déplacer les boutons Facebook / Google si nécessaire
        if ($('.ets_solo_social_wrapper').length && $('.nav.nav-inline').length) {
            if (!$('.nav.nav-inline').next().hasClass('ets_solo_social_wrapper')) {
                $('.ets_solo_social_wrapper').insertAfter('.nav.nav-inline');
            }
        }

        // Masquer le titre "Civilité"
        if ($('label[for="field-id_gender"]').length) {
            $('label[for="field-id_gender"]').hide();
        }

        // Masquer les textes "Optionnel"
        $('.form-control-comment').each(function () {
            if ($(this).text().trim() === "Optionnel") {
                $(this).hide();
            }
        });

        // Mise à jour du texte "Connectez-vous" ou "Créez un compte"
        function updateAccountSwitch() {
            let activeTab = $(".tab-pane.active").attr("id");
            let switchContainer = $(".account-switch");

            if (activeTab === "checkout-login-form") {
                switchContainer.html(`
                <h2 class="sous-titre">Connexion</h2>
                <p>
                    Pas encore de compte ?
                    <a href="#checkout-guest-form" data-toggle="tab" role="tab" aria-controls="checkout-guest-form">
                        Créez un compte →
                    </a>
                </p>
            `);
            } else {
                switchContainer.html(`
                <h2 class="sous-titre">Créer un compte</h2>
                <p>
                    Vous avez déjà un compte ?
                    <a href="#checkout-login-form" data-toggle="tab" role="tab" aria-controls="checkout-login-form">
                        Connectez-vous →
                    </a>
                </p>
            `);
            }

            $('a[data-toggle="tab"]').off("click").on("click", function () {
                setTimeout(updateAccountSwitch, 300);
            });
        }

        $('a[data-toggle="tab"]').on("shown.bs.tab", function () {
            updateAccountSwitch();
        });

        updateAccountSwitch();

        if (typeof default_address_id != 'undefined') {
            $("article.address-item").each(function () {
                if ($(this).find("input").val() == default_address_id) {
                    $(this).addClass('selected')
                    $(this).find("input").prop('checked', true)
                } else {
                    $(this).removeClass('selected')
                    $(this).find("input").prop('checked', false)
                }
            })
        }

        $(".payment-options .payment-option__title").each(function () {
            var title = $(this).text().trim().toLowerCase();
            var $logos = $(this).closest('label').find('.payment-option__logos');
            if (!$logos.length || $logos.data('payment-logo-set')) {
                return;
            }

            // Pas de logo pour l'acompte sur facture
            if (title.indexOf('acompte') !== -1) {
                $logos.empty();
                $logos.data('payment-logo-set', true);
                return;
            }

            var logoSrc = '';
            if (title.indexOf('apple pay') !== -1 || title.indexOf('applepay') !== -1) {
                logoSrc = '/themes/classic/assets/img/payment/ApplePay-logos.png';
            } else if (title.indexOf('paypal') !== -1) {
                logoSrc = '/themes/classic/assets/img/payment/paypal-logos.png';
            } else if (
                (title.indexOf('3x') !== -1 || title.indexOf('4x') !== -1) &&
                title.indexOf('carte') !== -1
            ) {
                logoSrc = '/themes/classic/assets/img/payment/oney-logos.png';
            } else if (
                title.indexOf('carte') !== -1 &&
                (title.indexOf('crédit') !== -1 || title.indexOf('credit') !== -1 || title.indexOf('bancaire') !== -1)
            ) {
                logoSrc = '/themes/classic/assets/img/payment/cb-logos.png';
            }

            if (logoSrc) {
                $logos.html('<img src="' + logoSrc + '" alt="" loading="lazy">');
                $logos.data('payment-logo-set', true);
            }
        });

        // Mettre en gras les échéances (3x, 4x, 10x…) dans les titres Oney
        function boldPaymentInstallments() {
            $('.payment-options .payment-option__title, .payment-options .oney-group__sub-title').each(function () {
                var $el = $(this);
                if ($el.data('installment-bold')) {
                    return;
                }
                var html = $el.html();
                if (!html || html.indexOf('<strong>') !== -1) {
                    $el.data('installment-bold', true);
                    return;
                }
                var newHtml = html.replace(/(\d+\s*x)/gi, '<strong>$1</strong>');
                if (newHtml !== html) {
                    $el.html(newHtml);
                }
                $el.data('installment-bold', true);
            });
        }

        // Formulaire paiement sans contenu visible → pas d'espace à la sélection
        // (ne pas toucher aux formulaires modules avec contenu / widgets)
        function markEmptyPaymentForms() {
            $('.payment-options .js-payment-option-form').each(function () {
                var $wrap = $(this);

                if ($wrap.find(
                    'iframe, .oney-checkoutSection, [id*="hipay"], .paypal-buttons, canvas, .form-group, .alert, table, ul, ol, select, textarea'
                ).length) {
                    $wrap.removeClass('js-payment-option-form--empty');
                    return;
                }

                var $form = $wrap.children('form').first();
                if (!$form.length || $wrap.children().not('form').length) {
                    // HTML module custom (ou pas uniquement un form PS) → ne pas collapser
                    if ($.trim($wrap.text()) || $wrap.find('div, p, span, img').filter(function () {
                        return $.trim($(this).text()) || $(this).is('img');
                    }).length) {
                        $wrap.removeClass('js-payment-option-form--empty');
                        return;
                    }
                }

                var $fields = $form.find('input, select, textarea, button').filter(function () {
                    var $el = $(this);
                    if ($el.is('input[type="hidden"]')) {
                        return false;
                    }
                    var style = (($el.attr('style') || '') + '').replace(/\s/g, '').toLowerCase();
                    if (style.indexOf('display:none') !== -1) {
                        return false;
                    }
                    return true;
                });

                if ($fields.length === 0) {
                    $wrap.addClass('js-payment-option-form--empty');
                } else {
                    $wrap.removeClass('js-payment-option-form--empty');
                }
            });
        }

        function syncPaymentCardSelection() {
            $('.payment-option-card').each(function () {
                var $card = $(this);
                if (
                    $card.hasClass('payment-option-card--paypal-group') ||
                    $card.hasClass('--paypal-source') ||
                    $card.hasClass('payment-option-card--oney-group') ||
                    $card.hasClass('--oney-source') ||
                    $card.hasClass('payment-option-card--oney-nx-group') ||
                    $card.hasClass('--oney-nx-source') ||
                    $card.hasClass('payment-option-card--oney-long-group') ||
                    $card.hasClass('--oney-long-source')
                ) {
                    return;
                }
                var optionId = $card.data('payment-option');
                var $input = optionId ? $('#' + optionId) : $();
                $card.toggleClass('-selected', $input.length > 0 && $input.is(':checked'));
            });

            var $paypalGroup = $('.payment-option-card--paypal-group');
            if ($paypalGroup.length) {
                var $checkedPaypalSource = $paypalGroup.find('.payment-option-card.--paypal-source input[name="payment-option"]:checked');
                var paypalSelected = $checkedPaypalSource.length > 0;
                $paypalGroup.toggleClass('-selected', paypalSelected);
                $paypalGroup.find('.paypal-group__parent-input').prop('checked', paypalSelected);
                $paypalGroup.find('.paypal-group__subs').toggleClass('is-open', paypalSelected);

                $paypalGroup.find('.payment-option-card.--paypal-source').each(function () {
                    var optionId = $(this).data('payment-option');
                    var $input = optionId ? $('#' + optionId) : $();
                    $(this).toggleClass('-selected', $input.length > 0 && $input.is(':checked'));
                });
            }

            var $oneyGroup = $('.payment-option-card--oney-group');
            if ($oneyGroup.length) {
                var $checkedOneySource = $oneyGroup.find('.payment-option-card.--oney-source input[name="payment-option"]:checked');
                var oneySelected = $checkedOneySource.length > 0;
                $oneyGroup.toggleClass('-selected', oneySelected);
                $oneyGroup.find('.oney-group__parent-input').prop('checked', oneySelected);
                $oneyGroup.find('.oney-group__subs').toggleClass('is-open', oneySelected);

                $oneyGroup.find('.payment-option-card.--oney-source').each(function () {
                    var optionId = $(this).data('payment-option');
                    var $input = optionId ? $('#' + optionId) : $();
                    $(this).toggleClass('-selected', $input.length > 0 && $input.is(':checked'));
                });
            }

            var $oneyNxGroup = $('.payment-option-card--oney-nx-group');
            if ($oneyNxGroup.length) {
                var $checkedNxSource = $oneyNxGroup.find('.payment-option-card.--oney-nx-source input[name="payment-option"]:checked');
                var nxSelected = $checkedNxSource.length > 0;
                $oneyNxGroup.toggleClass('-selected', nxSelected);
                $oneyNxGroup.find('.oney-nx-group__parent-input').prop('checked', nxSelected);
                $oneyNxGroup.find('.oney-group__subs').toggleClass('is-open', nxSelected);

                $oneyNxGroup.find('.payment-option-card.--oney-nx-source').each(function () {
                    var optionId = $(this).data('payment-option');
                    var $input = optionId ? $('#' + optionId) : $();
                    $(this).toggleClass('-selected', $input.length > 0 && $input.is(':checked'));
                });
            }

            var $oneyLongGroup = $('.payment-option-card--oney-long-group');
            if ($oneyLongGroup.length) {
                var $checkedLongSource = $oneyLongGroup.find('.payment-option-card.--oney-long-source input[name="payment-option"]:checked');
                var longSelected = $checkedLongSource.length > 0;
                $oneyLongGroup.toggleClass('-selected', longSelected);
                $oneyLongGroup.find('.oney-long-group__parent-input').prop('checked', longSelected);
                $oneyLongGroup.find('.oney-group__subs').toggleClass('is-open', longSelected);

                $oneyLongGroup.find('.payment-option-card.--oney-long-source').each(function () {
                    var optionId = $(this).data('payment-option');
                    var $input = optionId ? $('#' + optionId) : $();
                    $(this).toggleClass('-selected', $input.length > 0 && $input.is(':checked'));
                });
            }
        }

        function formatEuro(amount) {
            return amount.toLocaleString('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' €';
        }

        function getCheckoutTotalAmount() {
            var total = null;
            if (typeof prestashop !== 'undefined' && prestashop.cart && prestashop.cart.totals && prestashop.cart.totals.total) {
                var raw = prestashop.cart.totals.total.amount;
                if (typeof raw === 'number') {
                    total = raw;
                } else if (typeof raw === 'string') {
                    total = parseFloat(raw.replace(',', '.'));
                }
            }
            if (total === null || isNaN(total)) {
                var $total = $('.cart-summary-line.cart-total .value, .cart-total .value').first();
                if ($total.length) {
                    var text = $total.text().replace(/[^\d,.-]/g, '').replace(',', '.');
                    total = parseFloat(text);
                }
            }
            return (!total || isNaN(total)) ? null : total;
        }

        function buildPayPalInstallmentHtml() {
            var total = getCheckoutTotalAmount();
            if (!total) {
                return '';
            }
            var installment = Math.round((total / 4) * 100) / 100;
            return (
                '<div class="paypal-group__installments">' +
                    '<div class="paypal-group__installments-row paypal-group__installments-row--total">' +
                        '<span>Montant total financé</span>' +
                        '<strong>' + formatEuro(total) + '</strong>' +
                    '</div>' +
                    '<div class="paypal-group__installments-row">' +
                        '<span>Apport de</span>' +
                        '<strong>' + formatEuro(installment) + '</strong>' +
                    '</div>' +
                    '<div class="paypal-group__installments-row">' +
                        '<span>Suivi de 3 mensualités de</span>' +
                        '<strong>' + formatEuro(installment) + '</strong>' +
                    '</div>' +
                '</div>'
            );
        }

        function initPayPalPaymentGroup() {
            var $container = $('.payment-options');
            if (!$container.length || $container.find('.payment-option-card--paypal-group').length) {
                return;
            }

            var $cashCard = $container.find('.payment-option-card').has('input[data-module-name="paypal"]').first();
            var $bnplCard = $container.find('.payment-option-card').has('input[data-module-name="paypal_bnpl"]').first();

            if (!$cashCard.length && !$bnplCard.length) {
                return;
            }

            // Une seule option PayPal : pas de regroupement, juste le logo / libellés existants
            if (!$cashCard.length || !$bnplCard.length) {
                return;
            }

            var $cashInput = $cashCard.find('input[name="payment-option"]').first();
            var $bnplInput = $bnplCard.find('input[name="payment-option"]').first();
            var cashId = $cashInput.attr('id');
            var bnplId = $bnplInput.attr('id');
            var groupSelected = $cashInput.is(':checked') || $bnplInput.is(':checked');
            var activeSub = $bnplInput.is(':checked') ? 'bnpl' : 'cash';

            var $group = $(
                '<div class="payment-option-card payment-option-card--paypal-group' + (groupSelected ? ' -selected' : '') + '">' +
                    '<div class="payment-option paypal-group__header">' +
                        '<span class="custom-radio">' +
                            '<input class="ps-shown-by-js paypal-group__parent-input" type="radio" name="paypal-group-parent" value="1"' + (groupSelected ? ' checked' : '') + '>' +
                            '<span></span>' +
                        '</span>' +
                        '<label class="paypal-group__parent-label">' +
                            '<span class="payment-option__row">' +
                                '<span class="payment-option__text">' +
                                    '<span class="payment-option__title">Payer avec PayPal</span>' +
                                    '<span class="payment-option__desc">Au comptant ou quatre fois sans frais</span>' +
                                '</span>' +
                                '<span class="payment-option__logos">' +
                                    '<img src="/themes/classic/assets/img/payment/paypal-logos.png" alt="PayPal" loading="lazy">' +
                                '</span>' +
                            '</span>' +
                        '</label>' +
                    '</div>' +
                    '<div class="paypal-group__subs' + (groupSelected ? ' is-open' : '') + '">' +
                        '<label class="paypal-group__sub' + (activeSub === 'cash' && groupSelected ? ' is-active' : '') + '" data-paypal-sub="cash">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="paypal-group-sub" value="cash"' + (activeSub === 'cash' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="paypal-group__sub-label">Au comptant</span>' +
                        '</label>' +
                        '<label class="paypal-group__sub paypal-group__sub--bnpl' + (activeSub === 'bnpl' && groupSelected ? ' is-active' : '') + '" data-paypal-sub="bnpl">' +
                            '<span class="custom-radio">' +
                                '<input type="radio" name="paypal-group-sub" value="bnpl"' + (activeSub === 'bnpl' && groupSelected ? ' checked' : '') + '>' +
                                '<span></span>' +
                            '</span>' +
                            '<span class="paypal-group__sub-content">' +
                                '<span class="paypal-group__badge">4x</span>' +
                                '<span class="paypal-group__bnpl-details">' + buildPayPalInstallmentHtml() + '</span>' +
                            '</span>' +
                        '</label>' +
                        '<div class="paypal-group__sources"></div>' +
                    '</div>' +
                '</div>'
            );

            $cashCard.addClass('--paypal-source').attr('data-paypal-role', 'cash');
            $bnplCard.addClass('--paypal-source').attr('data-paypal-role', 'bnpl');

            $group.insertBefore($cashCard);
            $group.find('.paypal-group__sources').append($cashCard).append($bnplCard);

            function selectPayPalSub(sub) {
                var $target = sub === 'bnpl' ? $bnplInput : $cashInput;
                if (!$target.is(':checked')) {
                    $target.prop('checked', true).trigger('change');
                }
                $group.find('.paypal-group__sub').removeClass('is-active');
                $group.find('.paypal-group__sub[data-paypal-sub="' + sub + '"]').addClass('is-active');
                $group.find('input[name="paypal-group-sub"][value="' + sub + '"]').prop('checked', true);
                syncPaymentCardSelection();
            }

            $group.on('click', '.paypal-group__parent-label, .paypal-group__parent-input', function (e) {
                e.preventDefault();
                var currentSub = $group.find('input[name="paypal-group-sub"]:checked').val() || 'cash';
                selectPayPalSub(currentSub);
            });

            $group.on('change', 'input[name="paypal-group-sub"]', function () {
                selectPayPalSub($(this).val());
            });

            // Si une option PayPal source est cochée autrement, synchroniser l'UI groupe
            $(document).on('change.paypalGroup', '#' + cashId + ', #' + bnplId, function () {
                if (!$(this).is(':checked')) {
                    return;
                }
                var sub = $(this).data('module-name') === 'paypal_bnpl' ? 'bnpl' : 'cash';
                $group.find('.paypal-group__sub').removeClass('is-active');
                $group.find('.paypal-group__sub[data-paypal-sub="' + sub + '"]').addClass('is-active');
                $group.find('input[name="paypal-group-sub"][value="' + sub + '"]').prop('checked', true);
            });
        }

        function findHiPayOneyCard(installments) {
            var productCode = installments === 3 ? '3xcb-no-fees' : '4xcb-no-fees';
            var $form = $('#' + productCode + '-hipay');
            if ($form.length) {
                return $form.closest('.payment-option-card');
            }

            var needle = installments + 'x';
            return $('.payment-option-card').filter(function () {
                var $input = $(this).find('input[data-module-name="local_payment_hipay"]');
                if (!$input.length) {
                    return false;
                }
                var title = $(this).find('.payment-option__title').text().toLowerCase();
                return title.indexOf(needle) !== -1;
            }).first();
        }

        function relocateOneyWidgetFromSource($source, role, $slot) {
            if ($slot.data('oney-widget-moved')) {
                return true;
            }

            var productCode = role === '3x' ? '3xcb-no-fees' : '4xcb-no-fees';
            var $container = $source.find('#hipay-oney-' + productCode + '-widget-container');
            if (!$container.length) {
                $container = $source.find('[id*="hipay-oney"][id*="' + productCode + '"]').first();
            }
            if (!$container.length || (!$container.find('.oney-checkoutSection').length && $container.is('[hidden]'))) {
                return false;
            }

            $container.removeAttr('hidden').appendTo($slot);
            $slot.find('.oney-checkoutSection__legal-notice, .oney-checkout-legal-notice').remove();
            $slot.data('oney-widget-moved', true);
            return true;
        }

        function watchOneyWidgets($group, $card3x, $card4x) {
            var $slot3x = $group.find('.oney-group__widget-slot[data-oney-widget="3x"]');
            var $slot4x = $group.find('.oney-group__widget-slot[data-oney-widget="4x"]');
            var attempts = 0;

            var timer = setInterval(function () {
                attempts += 1;
                var done3 = relocateOneyWidgetFromSource($card3x, '3x', $slot3x);
                var done4 = relocateOneyWidgetFromSource($card4x, '4x', $slot4x);

                if ((done3 && done4) || attempts >= 40) {
                    clearInterval(timer);
                }
            }, 250);
        }

        function initOneyPaymentGroup() {
            var $container = $('.payment-options');
            if (!$container.length || $container.find('.payment-option-card--oney-group').length) {
                return;
            }

            var $card3x = findHiPayOneyCard(3);
            var $card4x = findHiPayOneyCard(4);

            if (!$card3x.length && !$card4x.length) {
                return;
            }

            if (!$card3x.length || !$card4x.length) {
                return;
            }

            var $input3x = $card3x.find('input[name="payment-option"]').first();
            var $input4x = $card4x.find('input[name="payment-option"]').first();
            var id3x = $input3x.attr('id');
            var id4x = $input4x.attr('id');
            var groupSelected = $input3x.is(':checked') || $input4x.is(':checked');
            var activeSub = $input4x.is(':checked') ? '4x' : '3x';

            var $group = $(
                '<div class="payment-option-card payment-option-card--oney-group' + (groupSelected ? ' -selected' : '') + '">' +
                    '<div class="payment-option oney-group__header">' +
                        '<span class="custom-radio">' +
                            '<input class="ps-shown-by-js oney-group__parent-input" type="radio" name="oney-group-parent" value="1"' + (groupSelected ? ' checked' : '') + '>' +
                            '<span></span>' +
                        '</span>' +
                        '<label class="oney-group__parent-label">' +
                            '<span class="payment-option__row">' +
                                '<span class="payment-option__text">' +
                                    '<span class="payment-option__title">Payer par <strong>3x</strong> ou <strong>4x</strong> sans frais</span>' +
                                    '<span class="payment-option__desc">Avec votre carte bancaire</span>' +
                                '</span>' +
                                '<span class="payment-option__logos">' +
                                    '<img src="/themes/classic/assets/img/payment/oney-logos.png" alt="Oney" loading="lazy">' +
                                '</span>' +
                            '</span>' +
                        '</label>' +
                    '</div>' +
                    '<div class="oney-group__subs' + (groupSelected ? ' is-open' : '') + '">' +
                        '<p class="oney-group__legal">UN CRÉDIT VOUS ENGAGE ET DOIT ÊTRE REMBOURSÉ. VÉRIFIEZ VOS CAPACITÉS DE REMBOURSEMENT AVANT DE VOUS ENGAGER.</p>' +
                        '<p class="oney-group__redirect">Vous allez être redirigé vers une page de paiement externe afin de finaliser la transaction.</p>' +
                        '<div class="oney-group__subs-grid">' +
                            '<div class="oney-group__sub' + (activeSub === '3x' && groupSelected ? ' is-active' : '') + '" data-oney-sub="3x">' +
                                '<label class="oney-group__sub-header">' +
                                    '<span class="custom-radio">' +
                                        '<input type="radio" name="oney-group-sub" value="3x"' + (activeSub === '3x' && groupSelected ? ' checked' : '') + '>' +
                                        '<span></span>' +
                                    '</span>' +
                                    '<span class="oney-group__sub-title">Payer par <strong>3x</strong><br>sans frais</span>' +
                                '</label>' +
                                '<div class="oney-group__widget-slot" data-oney-widget="3x"></div>' +
                            '</div>' +
                            '<div class="oney-group__sub' + (activeSub === '4x' && groupSelected ? ' is-active' : '') + '" data-oney-sub="4x">' +
                                '<label class="oney-group__sub-header">' +
                                    '<span class="custom-radio">' +
                                        '<input type="radio" name="oney-group-sub" value="4x"' + (activeSub === '4x' && groupSelected ? ' checked' : '') + '>' +
                                        '<span></span>' +
                                    '</span>' +
                                    '<span class="oney-group__sub-title">Payer par <strong>4x</strong><br>sans frais</span>' +
                                '</label>' +
                                '<div class="oney-group__widget-slot" data-oney-widget="4x"></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="oney-group__sources"></div>' +
                    '</div>' +
                '</div>'
            );

            $card3x.addClass('--oney-source').attr('data-oney-role', '3x');
            $card4x.addClass('--oney-source').attr('data-oney-role', '4x');

            $group.insertBefore($card3x);
            $group.find('.oney-group__sources').append($card3x).append($card4x);

            $group.find('.--oney-source .js-payment-option-form').removeClass('ps-hidden');
            watchOneyWidgets($group, $card3x, $card4x);

            function selectOneySub(sub) {
                var $target = sub === '4x' ? $input4x : $input3x;
                if (!$target.is(':checked')) {
                    $target.prop('checked', true).trigger('change');
                }
                $group.find('.oney-group__sub').removeClass('is-active');
                $group.find('.oney-group__sub[data-oney-sub="' + sub + '"]').addClass('is-active');
                $group.find('input[name="oney-group-sub"][value="' + sub + '"]').prop('checked', true);
                syncPaymentCardSelection();
            }

            $group.on('click', '.oney-group__parent-label, .oney-group__parent-input', function (e) {
                e.preventDefault();
                var currentSub = $group.find('input[name="oney-group-sub"]:checked').val() || '3x';
                selectOneySub(currentSub);
            });

            $group.on('click', '.oney-group__sub-header', function (e) {
                if ($(e.target).closest('a').length) {
                    return;
                }
                e.preventDefault();
                selectOneySub($(this).closest('.oney-group__sub').data('oney-sub'));
            });

            $group.on('change', 'input[name="oney-group-sub"]', function () {
                selectOneySub($(this).val());
            });

            $(document).on('change.oneyGroup', '#' + id3x + ', #' + id4x, function () {
                if (!$(this).is(':checked')) {
                    return;
                }
                var sub = $(this).attr('id') === id4x ? '4x' : '3x';
                $group.find('.oney-group__sub').removeClass('is-active');
                $group.find('.oney-group__sub[data-oney-sub="' + sub + '"]').addClass('is-active');
                $group.find('input[name="oney-group-sub"][value="' + sub + '"]').prop('checked', true);
            });
        }

        function findMhOneyNxCard(mode) {
            var $placeholder = $('#paymentOption' + mode);
            if ($placeholder.length) {
                return $placeholder.closest('.payment-option-card');
            }

            return $('.payment-option-card').filter(function () {
                var $input = $(this).find('input[data-module-name="mh_oney"]');
                if (!$input.length) {
                    return false;
                }
                var title = ($(this).find('.payment-option__title').text() || '').toLowerCase();
                var logo = ($(this).find('img').attr('src') || '').toLowerCase();
                if (mode === '10x') {
                    return title.indexOf('10') !== -1 || logo.indexOf('10x') !== -1 || logo.indexOf('11x') !== -1;
                }
                if (mode === '12x') {
                    return title.indexOf('12') !== -1 || logo.indexOf('12x') !== -1 || logo.indexOf('13x') !== -1;
                }
                return false;
            }).first();
        }

        function relocateMhOneyNxWidget($source, mode, $slot) {
            if ($slot.data('oney-widget-moved')) {
                return true;
            }

            var $placeholder = $source.find('#paymentOption' + mode);
            if (!$placeholder.length) {
                $placeholder = $source.find('.js-oney-checkout-placeholder[data-oney-mode="' + mode + '"]').first();
            }
            if (!$placeholder.length) {
                return false;
            }

            $source.find('.js-additional-information').removeClass('ps-hidden');
            $placeholder.appendTo($slot);

            if (typeof window.attachEventsAndUpdateOptions === 'function') {
                window.attachEventsAndUpdateOptions();
            }

            if ($placeholder.find('.oney-checkoutSection').length) {
                $slot.data('oney-widget-moved', true);
                return true;
            }

            return false;
        }

        function watchMhOneyNxWidgets($group, $card10x, $card12x) {
            var $slot10x = $group.find('.oney-group__widget-slot[data-oney-widget="10x"]');
            var $slot12x = $group.find('.oney-group__widget-slot[data-oney-widget="12x"]');
            var attempts = 0;

            var timer = setInterval(function () {
                attempts += 1;
                var done10 = relocateMhOneyNxWidget($card10x, '10x', $slot10x);
                var done12 = relocateMhOneyNxWidget($card12x, '12x', $slot12x);

                if ((done10 && done12) || attempts >= 40) {
                    clearInterval(timer);
                }
            }, 250);
        }

        function initMhOneyNxPaymentGroup() {
            var $container = $('.payment-options');
            if (!$container.length || $container.find('.payment-option-card--oney-nx-group').length) {
                return;
            }

            var $card10x = findMhOneyNxCard('10x');
            var $card12x = findMhOneyNxCard('12x');

            if (!$card10x.length || !$card12x.length) {
                return;
            }

            var $input10x = $card10x.find('input[name="payment-option"]').first();
            var $input12x = $card12x.find('input[name="payment-option"]').first();
            var id10x = $input10x.attr('id');
            var id12x = $input12x.attr('id');
            var groupSelected = $input10x.is(':checked') || $input12x.is(':checked');
            var activeSub = $input12x.is(':checked') ? '12x' : '10x';

            var $group = $(
                '<div class="payment-option-card payment-option-card--oney-nx-group' + (groupSelected ? ' -selected' : '') + '">' +
                    '<div class="payment-option oney-group__header">' +
                        '<span class="custom-radio">' +
                            '<input class="ps-shown-by-js oney-nx-group__parent-input" type="radio" name="oney-nx-group-parent" value="1"' + (groupSelected ? ' checked' : '') + '>' +
                            '<span></span>' +
                        '</span>' +
                        '<label class="oney-group__parent-label">' +
                            '<span class="payment-option__row">' +
                                '<span class="payment-option__text">' +
                                    '<span class="payment-option__title">Payer par <strong>10x</strong> ou <strong>12x</strong> sans frais</span>' +
                                    '<span class="payment-option__desc">Avec Oney</span>' +
                                '</span>' +
                                '<span class="payment-option__logos">' +
                                    '<img src="/themes/classic/assets/img/payment/oney-logos.png" alt="Oney" loading="lazy">' +
                                '</span>' +
                            '</span>' +
                        '</label>' +
                    '</div>' +
                    '<div class="oney-group__subs' + (groupSelected ? ' is-open' : '') + '">' +
                        '<p class="oney-group__legal">UN CRÉDIT VOUS ENGAGE ET DOIT ÊTRE REMBOURSÉ. VÉRIFIEZ VOS CAPACITÉS DE REMBOURSEMENT AVANT DE VOUS ENGAGER.</p>' +
                        '<p class="oney-group__redirect">Vous allez être redirigé vers une page de paiement externe afin de finaliser la transaction.</p>' +
                        '<div class="oney-group__subs-grid">' +
                            '<div class="oney-group__sub' + (activeSub === '10x' && groupSelected ? ' is-active' : '') + '" data-oney-nx-sub="10x">' +
                                '<label class="oney-group__sub-header">' +
                                    '<span class="custom-radio">' +
                                        '<input type="radio" name="oney-nx-group-sub" value="10x"' + (activeSub === '10x' && groupSelected ? ' checked' : '') + '>' +
                                        '<span></span>' +
                                    '</span>' +
                                    '<span class="oney-group__sub-title">Payer par <strong>10x</strong><br>sans frais</span>' +
                                '</label>' +
                                '<div class="oney-group__widget-slot" data-oney-widget="10x"></div>' +
                            '</div>' +
                            '<div class="oney-group__sub' + (activeSub === '12x' && groupSelected ? ' is-active' : '') + '" data-oney-nx-sub="12x">' +
                                '<label class="oney-group__sub-header">' +
                                    '<span class="custom-radio">' +
                                        '<input type="radio" name="oney-nx-group-sub" value="12x"' + (activeSub === '12x' && groupSelected ? ' checked' : '') + '>' +
                                        '<span></span>' +
                                    '</span>' +
                                    '<span class="oney-group__sub-title">Payer par <strong>12x</strong><br>sans frais</span>' +
                                '</label>' +
                                '<div class="oney-group__widget-slot" data-oney-widget="12x"></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="oney-nx-group__sources"></div>' +
                    '</div>' +
                '</div>'
            );

            $card10x.addClass('--oney-nx-source').attr('data-oney-nx-role', '10x');
            $card12x.addClass('--oney-nx-source').attr('data-oney-nx-role', '12x');

            $group.insertBefore($card10x);
            $group.find('.oney-nx-group__sources').append($card10x).append($card12x);

            $group.find('.--oney-nx-source .js-additional-information').removeClass('ps-hidden');
            watchMhOneyNxWidgets($group, $card10x, $card12x);

            function selectOneyNxSub(sub) {
                var $target = sub === '12x' ? $input12x : $input10x;
                if (!$target.is(':checked')) {
                    $target.prop('checked', true).trigger('change');
                }
                $group.find('.oney-group__sub').removeClass('is-active');
                $group.find('.oney-group__sub[data-oney-nx-sub="' + sub + '"]').addClass('is-active');
                $group.find('input[name="oney-nx-group-sub"][value="' + sub + '"]').prop('checked', true);
                syncPaymentCardSelection();
            }

            $group.on('click', '.oney-group__parent-label, .oney-nx-group__parent-input', function (e) {
                e.preventDefault();
                var currentSub = $group.find('input[name="oney-nx-group-sub"]:checked').val() || '10x';
                selectOneyNxSub(currentSub);
            });

            $group.on('click', '.oney-group__sub-header', function (e) {
                if ($(e.target).closest('a').length) {
                    return;
                }
                e.preventDefault();
                selectOneyNxSub($(this).closest('.oney-group__sub').data('oney-nx-sub'));
            });

            $group.on('change', 'input[name="oney-nx-group-sub"]', function () {
                selectOneyNxSub($(this).val());
            });

            $(document).on('change.oneyNxGroup', '#' + id10x + ', #' + id12x, function () {
                if (!$(this).is(':checked')) {
                    return;
                }
                var sub = $(this).attr('id') === id12x ? '12x' : '10x';
                $group.find('.oney-group__sub').removeClass('is-active');
                $group.find('.oney-group__sub[data-oney-nx-sub="' + sub + '"]').addClass('is-active');
                $group.find('input[name="oney-nx-group-sub"][value="' + sub + '"]').prop('checked', true);
            });
        }

        function findMhOneyLongCard(mode) {
            var $placeholder = $('#paymentOption' + mode);
            if ($placeholder.length) {
                return $placeholder.closest('.payment-option-card');
            }

            return $('.payment-option-card').filter(function () {
                var $input = $(this).find('input[data-module-name="mh_oney"]');
                if (!$input.length) {
                    return false;
                }
                var title = ($(this).find('.payment-option__title').text() || '').toLowerCase();
                var logo = ($(this).find('img').attr('src') || '').toLowerCase();
                if (mode === '24x') {
                    return title.indexOf('24') !== -1 || logo.indexOf('24x') !== -1 || logo.indexOf('25x') !== -1;
                }
                if (mode === '36x') {
                    return title.indexOf('36') !== -1 || logo.indexOf('36x') !== -1 || logo.indexOf('37x') !== -1;
                }
                if (mode === '48x') {
                    return title.indexOf('48') !== -1 || logo.indexOf('48x') !== -1 || logo.indexOf('49x') !== -1;
                }
                return false;
            }).first();
        }

        function watchMhOneyLongWidgets($group, $card24x, $card36x, $card48x) {
            var $slot24x = $group.find('.oney-group__widget-slot[data-oney-widget="24x"]');
            var $slot36x = $group.find('.oney-group__widget-slot[data-oney-widget="36x"]');
            var $slot48x = $group.find('.oney-group__widget-slot[data-oney-widget="48x"]');
            var attempts = 0;

            var timer = setInterval(function () {
                attempts += 1;
                var done24 = relocateMhOneyNxWidget($card24x, '24x', $slot24x);
                var done36 = relocateMhOneyNxWidget($card36x, '36x', $slot36x);
                var done48 = relocateMhOneyNxWidget($card48x, '48x', $slot48x);

                if ((done24 && done36 && done48) || attempts >= 40) {
                    clearInterval(timer);
                }
            }, 250);
        }

        function initMhOneyLongPaymentGroup() {
            var $container = $('.payment-options');
            if (!$container.length || $container.find('.payment-option-card--oney-long-group').length) {
                return;
            }

            var $card24x = findMhOneyLongCard('24x');
            var $card36x = findMhOneyLongCard('36x');
            var $card48x = findMhOneyLongCard('48x');

            if (!$card24x.length || !$card36x.length || !$card48x.length) {
                return;
            }

            var $input24x = $card24x.find('input[name="payment-option"]').first();
            var $input36x = $card36x.find('input[name="payment-option"]').first();
            var $input48x = $card48x.find('input[name="payment-option"]').first();
            var id24x = $input24x.attr('id');
            var id36x = $input36x.attr('id');
            var id48x = $input48x.attr('id');
            var groupSelected = $input24x.is(':checked') || $input36x.is(':checked') || $input48x.is(':checked');
            var activeSub = '24x';
            if ($input48x.is(':checked')) {
                activeSub = '48x';
            } else if ($input36x.is(':checked')) {
                activeSub = '36x';
            }

            var $group = $(
                '<div class="payment-option-card payment-option-card--oney-long-group' + (groupSelected ? ' -selected' : '') + '">' +
                    '<div class="payment-option oney-group__header">' +
                        '<span class="custom-radio">' +
                            '<input class="ps-shown-by-js oney-long-group__parent-input" type="radio" name="oney-long-group-parent" value="1"' + (groupSelected ? ' checked' : '') + '>' +
                            '<span></span>' +
                        '</span>' +
                        '<label class="oney-group__parent-label">' +
                            '<span class="payment-option__row">' +
                                '<span class="payment-option__text">' +
                                    '<span class="payment-option__title">Payer en <strong>24x</strong>, <strong>36x</strong> ou <strong>48x</strong></span>' +
                                    '<span class="payment-option__desc">Crédit long avec Oney</span>' +
                                '</span>' +
                                '<span class="payment-option__logos">' +
                                    '<img src="/themes/classic/assets/img/payment/oney-logos.png" alt="Oney" loading="lazy">' +
                                '</span>' +
                            '</span>' +
                        '</label>' +
                    '</div>' +
                    '<div class="oney-group__subs' + (groupSelected ? ' is-open' : '') + '">' +
                        '<p class="oney-group__legal">UN CRÉDIT VOUS ENGAGE ET DOIT ÊTRE REMBOURSÉ. VÉRIFIEZ VOS CAPACITÉS DE REMBOURSEMENT AVANT DE VOUS ENGAGER.</p>' +
                        '<p class="oney-group__redirect">Vous allez être redirigé vers une page de paiement externe afin de finaliser la transaction.</p>' +
                        '<div class="oney-group__subs-grid oney-group__subs-grid--3">' +
                            '<div class="oney-group__sub' + (activeSub === '24x' && groupSelected ? ' is-active' : '') + '" data-oney-long-sub="24x">' +
                                '<label class="oney-group__sub-header">' +
                                    '<span class="custom-radio">' +
                                        '<input type="radio" name="oney-long-group-sub" value="24x"' + (activeSub === '24x' && groupSelected ? ' checked' : '') + '>' +
                                        '<span></span>' +
                                    '</span>' +
                                    '<span class="oney-group__sub-title">Payer par <strong>24x</strong></span>' +                                '</label>' +
                                '<div class="oney-group__widget-slot" data-oney-widget="24x"></div>' +
                            '</div>' +
                            '<div class="oney-group__sub' + (activeSub === '36x' && groupSelected ? ' is-active' : '') + '" data-oney-long-sub="36x">' +
                                '<label class="oney-group__sub-header">' +
                                    '<span class="custom-radio">' +
                                        '<input type="radio" name="oney-long-group-sub" value="36x"' + (activeSub === '36x' && groupSelected ? ' checked' : '') + '>' +
                                        '<span></span>' +
                                    '</span>' +
                                    '<span class="oney-group__sub-title">Payer par <strong>36x</strong></span>' +
                                '</label>' +
                                '<div class="oney-group__widget-slot" data-oney-widget="36x"></div>' +
                            '</div>' +
                            '<div class="oney-group__sub' + (activeSub === '48x' && groupSelected ? ' is-active' : '') + '" data-oney-long-sub="48x">' +
                                '<label class="oney-group__sub-header">' +
                                    '<span class="custom-radio">' +
                                        '<input type="radio" name="oney-long-group-sub" value="48x"' + (activeSub === '48x' && groupSelected ? ' checked' : '') + '>' +
                                        '<span></span>' +
                                    '</span>' +
                                    '<span class="oney-group__sub-title">Payer par <strong>48x</strong></span>' +
                                '</label>' +
                                '<div class="oney-group__widget-slot" data-oney-widget="48x"></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="oney-long-group__sources"></div>' +
                    '</div>' +
                '</div>'
            );

            $card24x.addClass('--oney-long-source').attr('data-oney-long-role', '24x');
            $card36x.addClass('--oney-long-source').attr('data-oney-long-role', '36x');
            $card48x.addClass('--oney-long-source').attr('data-oney-long-role', '48x');

            $group.insertBefore($card24x);
            $group.find('.oney-long-group__sources').append($card24x).append($card36x).append($card48x);

            $group.find('.--oney-long-source .js-additional-information').removeClass('ps-hidden');
            watchMhOneyLongWidgets($group, $card24x, $card36x, $card48x);

            function selectOneyLongSub(sub) {
                var $target = $input24x;
                if (sub === '36x') {
                    $target = $input36x;
                } else if (sub === '48x') {
                    $target = $input48x;
                }
                if (!$target.is(':checked')) {
                    $target.prop('checked', true).trigger('change');
                }
                $group.find('.oney-group__sub').removeClass('is-active');
                $group.find('.oney-group__sub[data-oney-long-sub="' + sub + '"]').addClass('is-active');
                $group.find('input[name="oney-long-group-sub"][value="' + sub + '"]').prop('checked', true);
                syncPaymentCardSelection();
            }

            $group.on('click', '.oney-group__parent-label, .oney-long-group__parent-input', function (e) {
                e.preventDefault();
                var currentSub = $group.find('input[name="oney-long-group-sub"]:checked').val() || '24x';
                selectOneyLongSub(currentSub);
            });

            $group.on('click', '.oney-group__sub-header', function (e) {
                if ($(e.target).closest('a').length) {
                    return;
                }
                e.preventDefault();
                selectOneyLongSub($(this).closest('.oney-group__sub').data('oney-long-sub'));
            });

            $group.on('change', 'input[name="oney-long-group-sub"]', function () {
                selectOneyLongSub($(this).val());
            });

            $(document).on('change.oneyLongGroup', '#' + id24x + ', #' + id36x + ', #' + id48x, function () {
                if (!$(this).is(':checked')) {
                    return;
                }
                var clickedId = $(this).attr('id');
                var sub = '24x';
                if (clickedId === id36x) {
                    sub = '36x';
                } else if (clickedId === id48x) {
                    sub = '48x';
                }
                $group.find('.oney-group__sub').removeClass('is-active');
                $group.find('.oney-group__sub[data-oney-long-sub="' + sub + '"]').addClass('is-active');
                $group.find('input[name="oney-long-group-sub"][value="' + sub + '"]').prop('checked', true);
            });
        }

        initPayPalPaymentGroup();
        initOneyPaymentGroup();
        initMhOneyNxPaymentGroup();
        initMhOneyLongPaymentGroup();

        boldPaymentInstallments();
        markEmptyPaymentForms();

        $(document).on('change', 'input[name="payment-option"]', function () {
            syncPaymentCardSelection();
            markEmptyPaymentForms();
        });
        syncPaymentCardSelection();

        // Sélectionne un moyen de paiement par défaut si aucun n'est coché
        function selectDefaultPaymentOption() {
            var $checked = $('input[name="payment-option"]:checked');
            if ($checked.length) {
                return;
            }

            var $options = $('input[name="payment-option"]').filter(function () {
                var $input = $(this);
                if (!$input.is(':visible') && !$input.closest('.payment-option-card').is(':visible')) {
                    // Sources regroupées (PayPal/Oney) restent sélectionnables
                    if (
                        !$input.closest('.--paypal-source, .--oney-source, .--oney-nx-source, .--oney-long-source').length
                    ) {
                        return false;
                    }
                }
                var module = ($input.data('module-name') || '').toString().toLowerCase();
                var title = ($input.closest('.payment-option-card').find('.payment-option__title').text() || '').toLowerCase();
                // Ne pas précocher devis / téléphone
                if (
                    module === 'opartdevis' ||
                    title.indexOf('devis') !== -1 ||
                    module === 'ckc_paywithphone' ||
                    title.indexOf('éléphone') !== -1
                ) {
                    return false;
                }
                return true;
            });

            if (!$options.length) {
                return;
            }

            // Priorité : carte bancaire PayPlug / CB
            var $preferred = $options.filter(function () {
                var module = ($(this).data('module-name') || '').toString().toLowerCase();
                var title = ($(this).closest('.payment-option-card').find('.payment-option__title').text() || '').toLowerCase();
                return (
                    (module === 'payplug' && (title.indexOf('carte') !== -1 || title.indexOf('card') !== -1)) ||
                    module === 'credit_card' ||
                    title.indexOf('carte bancaire') !== -1 ||
                    title.indexOf('carte de crédit') !== -1
                );
            }).first();

            var $target = $preferred.length ? $preferred : $options.first();
            $target.prop('checked', true).trigger('change');
            syncPaymentCardSelection();
        }

        selectDefaultPaymentOption();
        setTimeout(selectDefaultPaymentOption, 200);

        // CGV + PASSER COMMANDE : restent sous les paiements + miroir dans le résumé
        function getOriginalPaymentActions() {
            return $('.js-payment-checkout-actions')
                .not('.js-payment-checkout-actions-clone')
                .filter(function () {
                    return !$(this).closest('.js-cart-summary-payment-actions').length;
                })
                .first();
        }

        function syncPaymentActionsToSummary() {
            var $actions = getOriginalPaymentActions();
            var $slot = $('.js-cart-summary-payment-actions');
            if (!$actions.length || !$slot.length) {
                return;
            }

            var $paymentStep = $('#checkout-payment-step');
            var isPaymentCurrent = $paymentStep.hasClass('-current') || $paymentStep.hasClass('js-current-step');

            if (!isPaymentCurrent) {
                $slot.empty().removeClass('is-visible');
                return;
            }

            var $clone = $actions.clone(false, false);
            $clone
                .removeClass('js-payment-checkout-actions')
                .addClass('js-payment-checkout-actions-clone payment-checkout-actions--summary');

            // Évite les doublons d'id / name qui cassent la vraie checkbox.
            // Important: retirer js-conditions-to-approve du clone — PrestaShop
            // (toggleOrderButton) sélectionne TOUTES les checkbox sous cette classe.
            // Sinon cocher la CGV sous les paiements laisse le clone non coché
            // et le bouton reste disabled + l'alerte visible.
            $clone.find('[id]').removeAttr('id');
            $clone.find('form').each(function () {
                var $form = $(this);
                var classes = ($form.attr('class') || '')
                    .split(/\s+/)
                    .filter(function (c) {
                        return c && c !== 'js-conditions-to-approve';
                    });
                classes.push('js-conditions-to-approve-summary');
                $form.replaceWith(
                    $('<div class="' + classes.join(' ') + '"></div>').append($form.contents())
                );
            });
            $clone.find('.js-conditions-to-approve')
                .removeClass('js-conditions-to-approve')
                .addClass('js-conditions-to-approve-summary');
            $clone.find('.payment-confirmation__prev').remove();

            $clone.find('input[type="checkbox"]').each(function (index) {
                var $input = $(this);
                var $original = $actions.find('input[type="checkbox"]').eq(index);
                var summaryId = 'conditions-summary-' + index;
                $input.attr({
                    id: summaryId,
                    name: ''
                }).removeAttr('form').removeAttr('required')
                    .removeClass('ps-shown-by-js');
                $input.prop('checked', $original.is(':checked'));

                var $label = $input.closest('li').find('label.condition-label');
                if ($label.length) {
                    $label.attr('for', summaryId);
                }
            });

            var $originalBtn = $actions.find('.payment-confirmation__submit');
            var $cloneBtn = $clone.find('.payment-confirmation__submit');
            $cloneBtn
                .removeAttr('id')
                .attr('type', 'button')
                .toggleClass('disabled', $originalBtn.hasClass('disabled') || $originalBtn.prop('disabled'));

            $slot.empty().append($clone).addClass('is-visible');
        }

        // Checkbox résumé → original
        $(document).on('change', '.js-payment-checkout-actions-clone input[type="checkbox"]', function () {
            var index = $(this).closest('li').index();
            var $original = getOriginalPaymentActions().find('input[type="checkbox"]').eq(index);
            if ($original.length) {
                $original.prop('checked', $(this).is(':checked')).trigger('change');
            }
        });

        // Bouton résumé → déclenche le vrai bouton
        $(document).on('click', '.js-payment-checkout-actions-clone .payment-confirmation__submit', function (e) {
            e.preventDefault();
            if ($(this).hasClass('disabled')) {
                return;
            }
            var $realBtn = getOriginalPaymentActions().find('.payment-confirmation__submit');
            if ($realBtn.length) {
                $realBtn.trigger('click');
            }
        });

        // Sync état clone (sans re-cloner)
        function syncSummaryCheckboxState() {
            var $actions = getOriginalPaymentActions();
            var $clone = $('.js-payment-checkout-actions-clone');
            if (!$actions.length || !$clone.length) {
                return;
            }
            var $realBtn = $actions.find('.payment-confirmation__submit');
            $clone.find('.payment-confirmation__submit')
                .toggleClass('disabled', $realBtn.hasClass('disabled') || $realBtn.prop('disabled'));
            $actions.find('input[type="checkbox"]').each(function (i) {
                $clone.find('input[type="checkbox"]').eq(i).prop('checked', $(this).is(':checked'));
            });
        }

        $(document).on(
            'change',
            'input[name="payment-option"], #conditions-to-approve input[type="checkbox"]',
            function () {
                setTimeout(syncSummaryCheckboxState, 50);
            }
        );

        syncPaymentActionsToSummary();
        $(document).on('click', '.checkout-step .js-step-title', function () {
            setTimeout(function () {
                selectDefaultPaymentOption();
                syncPaymentActionsToSummary();
            }, 50);
            setTimeout(function () {
                selectDefaultPaymentOption();
                syncPaymentActionsToSummary();
            }, 300);
        });
        if (typeof prestashop !== 'undefined') {
            prestashop.on('updatedCart', function () {
                setTimeout(syncPaymentActionsToSummary, 100);
            });
        }
    }

    // Gérer le déplacement du bloc stock en mobile sur la page checkout
    if ($('body').hasClass('page-order')) {
        function moveStockBlockForMobile() {
            var $stockBlock = $('#stock-summary-content');
            var $stockMobileContainer = $('.cart-grid-stock-mobile');
            
            if ($stockBlock.length && $stockMobileContainer.length) {
                var $existingClone = $stockMobileContainer.find('#stock-summary-content-mobile');
                
                if ($(window).width() <= 991) {
                    // Créer le clone seulement s'il n'existe pas
                    if ($existingClone.length === 0) {
                        var $stockClone = $stockBlock.clone(true, true);
                        $stockClone.attr('id', 'stock-summary-content-mobile');
                        $stockClone.attr('class', $stockBlock.attr('class'));
                        $stockMobileContainer.append($stockClone);
                    }
                    // Si le clone existe déjà, ne rien faire (optimisation)
                } else {
                    // En desktop, supprimer le clone mobile s'il existe
                    if ($existingClone.length > 0) {
                        $existingClone.remove();
                    }
                }
            }
        }
        
        setTimeout(function() {
            moveStockBlockForMobile();
        }, 100);
        
        var resizeTimeout;
        $(window).on('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                moveStockBlockForMobile();
            }, 150);
        });
    }

    if ($('body').hasClass('page-cart')) {

        var $cartSummary = $('.cart-summary');
        if ($cartSummary.length === 0) return;

        var $noItems = $('.cart-overview .no-items');
        if ($noItems.length > 0 && $noItems.is(':visible')) return;

        var isSticky = false;
        var stickyOffsetTop = null;
        var $stickyPlaceholder = null; // Placeholder pour éviter le saut visuel lors du passage en sticky
        var $blockPromo = null;
        var $h3 = null;

        // Fonction pour nettoyer les doublons de bloc promo
        function cleanDuplicateBlockPromo() {
            var $blockCheckout = $cartSummary.find('.block-checkout');
            var $blockPromos = $blockCheckout.find('.block-promo');
            if ($blockPromos.length > 1) {
                // Garder seulement le premier et supprimer les autres
                $blockPromos.slice(1).remove();
            }
        }

        // Fonction pour nettoyer les doublons de cart-detailed-totals
        function cleanDuplicateCartDetailedTotals() {
            var $blockCheckout = $cartSummary.find('.block-checkout');
            
            // Il ne doit y avoir qu'un seul .js-cart-detailed-totals (celui avec les subtotaux)
            var $jsCartDetailedTotals = $blockCheckout.find('.js-cart-detailed-totals');
            if ($jsCartDetailedTotals.length > 1) {
                // Garder seulement le premier et supprimer les autres
                $jsCartDetailedTotals.slice(1).remove();
            }
            
            // S'assurer qu'il n'y a qu'un seul cart-detailed-totals avec js- dans block-checkout
            var $allCartDetailedTotals = $blockCheckout.find('.cart-detailed-totals');
            var $jsTotals = $allCartDetailedTotals.filter('.js-cart-detailed-totals');
            var $totalTTC = $allCartDetailedTotals.filter(function() {
                return $(this).find('.cart-total').length > 0 && !$(this).hasClass('js-cart-detailed-totals');
            });
            
            // Il doit y avoir exactement 1 js-cart-detailed-totals et 1 avec cart-total (sans js-)
            if ($jsTotals.length > 1) {
                $jsTotals.slice(1).remove();
            }
            if ($totalTTC.length > 1) {
                // Garder seulement le premier bloc avec cart-total
                $totalTTC.slice(1).remove();
            }
            
        }

        function isDesktop() {
            return $(window).width() >= 992;
        }

        function pageIsTallEnough() {
            var windowHeight = $(window).height();
            var documentHeight = $(document).height();
            return documentHeight > (windowHeight + 200);
        }

        function updateStickyOffset() {
            // Supprimer le placeholder si présent lors du recalcul
            if ($stickyPlaceholder && $stickyPlaceholder.length) {
                $stickyPlaceholder.remove();
                $stickyPlaceholder = null;
            }
            $cartSummary.removeClass('sticky-summary');
            stickyOffsetTop = $cartSummary.offset().top;
        }

        updateStickyOffset();

        $(window).on('resize', function () {
            if (!isDesktop()) {
                removeSticky();
            } else {
                updateStickyOffset();
            }
        });

        $(window).on('scroll', function () {
            if (!isDesktop() || !pageIsTallEnough()) return;

            var currentScroll = $(this).scrollTop();

            if (currentScroll > stickyOffsetTop + 100 && !isSticky) {
                applySticky();
            } else if (currentScroll <= stickyOffsetTop + 100 && isSticky) {
                removeSticky();
            }
        });

        function applySticky() {
            if (isSticky) return; // Éviter les appels multiples
            isSticky = true;
            
            // Créer un placeholder invisible pour maintenir l'espace et éviter le saut visuel
            var originalHeight = $cartSummary.outerHeight();
            var originalWidth = $cartSummary.outerWidth();
            
            // Créer le placeholder avant de rendre sticky pour éviter le freeze
            $stickyPlaceholder = $('<div class="cart-summary-placeholder"></div>')
                .css({
                    'height': originalHeight + 'px',
                    'width': originalWidth + 'px',
                    'visibility': 'hidden',
                    'pointer-events': 'none',
                    'flex-shrink': '0'
                });
            
            // Insérer le placeholder juste après le cart-summary
            $cartSummary.after($stickyPlaceholder);
            
            // Ajouter la classe sticky-summary
            $cartSummary.addClass('sticky-summary');
            
            // Ajouter une classe au body pour masquer moyen_de_paiement via CSS
            $('body').addClass('cart-summary-sticky');

            // Ne détacher que le bloc promo qui est dans block-checkout, pas celui dans cart-detailed-totals
            var $blockPromoInCheckout = $cartSummary.find('.block-checkout .block-promo').first();
            if ($blockPromoInCheckout.length) {
                $blockPromo = $blockPromoInCheckout.detach();
            }
            $h3 = $cartSummary.find('h3').detach();
        }

        function removeSticky() {
            if (!isSticky) return; // Éviter les appels multiples
            isSticky = false;
            
            // Supprimer le placeholder avant de retirer le sticky
            if ($stickyPlaceholder && $stickyPlaceholder.length) {
                $stickyPlaceholder.remove();
                $stickyPlaceholder = null;
            }
            
            $cartSummary.removeClass('sticky-summary');
            
            // Retirer la classe du body
            $('body').removeClass('cart-summary-sticky');

            var $inside = $cartSummary.find('.cart-summary-inside');
            var $blockCheckout = $inside.find('.block-checkout');

            // Nettoyer les doublons avant de réinsérer
            cleanDuplicateBlockPromo();

            if ($h3 && $h3.length) {
                $inside.prepend($h3);
                $h3 = null;
            }
            // Réinsérer le bloc promo dans block-checkout, AVANT le total TTC, seulement s'il n'existe pas déjà
            if ($blockPromo && $blockPromo.length) {
                var $existingBlockPromo = $blockCheckout.find('.block-promo');
                if ($existingBlockPromo.length === 0) {
                    // Trouver le bloc total TTC (celui avec cart-total mais sans js-cart-detailed-totals)
                    var $totalTTC = $blockCheckout.find('.cart-detailed-totals').filter(function() {
                        return $(this).find('.cart-total').length > 0 && !$(this).hasClass('js-cart-detailed-totals');
                    });
                    if ($totalTTC.length) {
                        // Insérer le bloc promo AVANT le total TTC (ordre correct selon le template)
                        $totalTTC.first().before($blockPromo);
                    } else {
                        // Si pas de total TTC, chercher le dernier cart-detailed-totals (js-cart-detailed-totals)
                        var $lastCartTotals = $blockCheckout.find(".js-cart-detailed-totals").last();
                        if ($lastCartTotals.length) {
                            // Chercher le bloc cart-promos pour insérer après
                            var $cartPromos = $blockCheckout.find('.cart-promos').last();
                            if ($cartPromos.length) {
                                $cartPromos.after($blockPromo);
                            } else {
                                $lastCartTotals.after($blockPromo);
                            }
                        } else {
                            $blockCheckout.append($blockPromo);
                        }
                    }
                } else {
                    // Si un bloc promo existe déjà, supprimer celui qu'on a détaché pour éviter les doublons
                    $blockPromo.remove();
                }
                $blockPromo = null;
            }
            
            // Nettoyer à nouveau après réinsertion pour être sûr
            cleanDuplicateBlockPromo();
            cleanDuplicateCartDetailedTotals();
        }

        // Fonction pour s'assurer que le bloc total TTC existe toujours
        function ensureTotalTTCExists() {
            var $blockCheckout = $cartSummary.find('.block-checkout');
            if (!$blockCheckout.length) return;
            
            var $totalTTC = $blockCheckout.find('.cart-detailed-totals').filter(function() {
                return $(this).find('.cart-total').length > 0 && !$(this).hasClass('js-cart-detailed-totals');
            });
            
            if ($totalTTC.length === 0) {
                // Le bloc total TTC n'existe pas, il faut le restaurer
                // Faire une requête AJAX pour récupérer le cart_summary_totals
                var refreshURL = $('.blockcart').data('refresh-url') || window.location.href;
                if (refreshURL) {
                    $.ajax({
                        url: refreshURL,
                        type: 'POST',
                        data: {
                            ajax: 1,
                            action: 'refresh'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.cart_summary_totals) {
                                // Créer le wrapper et insérer le contenu
                                var $newWrapper = $('<div class="cart-detailed-totals"></div>').html(response.cart_summary_totals);
                                var $blockPromo = $blockCheckout.find('.block-promo').first();
                                if ($blockPromo.length) {
                                    $blockPromo.after($newWrapper);
                                } else {
                                    var $lastCartTotals = $blockCheckout.find('.js-cart-detailed-totals').last();
                                    if ($lastCartTotals.length) {
                                        $lastCartTotals.after($newWrapper);
                                    } else {
                                        $blockCheckout.append($newWrapper);
                                    }
                                }
                            }
                        }
                    });
                } else {
                    // Essayer d'utiliser l'URL de la page actuelle avec le paramètre ajax
                    var currentURL = window.location.href.split('?')[0];
                    $.ajax({
                        url: currentURL,
                        type: 'POST',
                        data: {
                            ajax: 1,
                            action: 'refresh',
                            token: $('input[name="token"]').val() || ''
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.cart_summary_totals) {
                                // Créer le wrapper et insérer le contenu
                                var $newWrapper = $('<div class="cart-detailed-totals"></div>').html(response.cart_summary_totals);
                                var $blockPromo = $blockCheckout.find('.block-promo').first();
                                if ($blockPromo.length) {
                                    $blockPromo.after($newWrapper);
                                } else {
                                    var $lastCartTotals = $blockCheckout.find('.js-cart-detailed-totals').last();
                                    if ($lastCartTotals.length) {
                                        $lastCartTotals.after($newWrapper);
                                    } else {
                                        $blockCheckout.append($newWrapper);
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        // Fonction pour réinitialiser le formulaire de code promo après mise à jour AJAX
        // Rendre cette fonction globale pour qu'elle soit accessible depuis d'autres fichiers
        window.reinitializePromoForm = function() {
            var $cartSummary = $('.cart-summary');
            if (!$cartSummary.length) return;
            
            var $blockPromo = $cartSummary.find('.block-checkout .block-promo').first();
            if ($blockPromo.length) {
                var $promoCollapse = $blockPromo.find('#promoCollapse');
                var $promoForm = $blockPromo.find('form[data-link-action="add-voucher"]');
                
                // S'assurer que le formulaire existe
                if ($promoForm.length && $promoCollapse.length) {
                    // Réinitialiser les événements du collapse
                    $promoCollapse.off('show.bs.collapse hide.bs.collapse');
                    var $chevron = $blockPromo.find('.chevron-toggle svg');
                    
                    $promoCollapse.on('show.bs.collapse', function () {
                        if ($chevron.length) {
                            $chevron.css('transform', 'rotate(180deg)');
                        }
                    });
                    
                    $promoCollapse.on('hide.bs.collapse', function () {
                        if ($chevron.length) {
                            $chevron.css('transform', 'rotate(0deg)');
                        }
                    });
                    
                    // Réinitialiser les événements du chevron
                    $blockPromo.find('.chevron-toggle').off('click');
                    $blockPromo.find('.chevron-toggle').on('click', function(e) {
                        e.preventDefault();
                        $promoCollapse.collapse('toggle');
                    });
                    
                    // Réinitialiser les événements du collapse-toggle
                    $blockPromo.find('.collapse-toggle').off('click');
                    $blockPromo.find('.collapse-toggle').on('click', function(e) {
                        e.preventDefault();
                        $promoCollapse.collapse('toggle');
                    });
                }
            }
        };

        // Fonction centralisée pour nettoyer et réinitialiser après mise à jour AJAX
        function cleanupAfterCartUpdate() {
            if ($('body').hasClass('page-cart')) {
                setTimeout(function() {
                    cleanDuplicateCartDetailedTotals();
                    cleanDuplicateBlockPromo();
                    reinitializePromoForm();
                    ensureTotalTTCExists();
                }, 150);
            }
        }

        // Nettoyer les doublons après les mises à jour AJAX du panier (pour les requêtes qui ne déclenchent pas updatedCart)
        $(document).ajaxComplete(function(event, xhr, settings) {
            if ($('body').hasClass('page-cart')) {
                // Vérifier si c'est une requête liée au panier
                var isCartRequest = false;
                if (settings.url && (settings.url.includes('panier') || settings.url.includes('cart') || settings.url.includes('ajax=1'))) {
                    isCartRequest = true;
                }
                if (settings.data && (settings.data.includes('addDiscount') || settings.data.includes('add-voucher') || settings.data.includes('deleteDiscount'))) {
                    isCartRequest = true;
                }
                
                if (isCartRequest) {
                    cleanupAfterCartUpdate();
                }
            }
        });

        // Écouter les événements PrestaShop (méthode principale pour les mises à jour du panier)
        if (typeof prestashop !== 'undefined') {
            prestashop.on('updatedCart', function(event) {
                cleanupAfterCartUpdate();
            });
        }
    }

    const $chevron = $('.chevron-toggle svg');
    const $collapse = $('#promoCollapse');
    if ($('body').hasClass('page-cart') || $('body').hasClass('page-order')) {
        // Clic manuel sur le chevron

        $('.chevron-toggle').off('click');
        $(document).on('click', '.chevron-toggle', function (e) {
            toggleCollapse(e)
        });

        function toggleCollapse(e) {
            e.preventDefault();
            $('#promoCollapse').collapse('toggle');
        }

        // Rotation du chevron
        $collapse.on('show.bs.collapse', function () {
            $chevron.css('transform', 'rotate(180deg)');
        });

        $collapse.on('hide.bs.collapse', function () {
            $chevron.css('transform', 'rotate(0deg)');
        });


        // Vérification à la soumission
        $('.js-address-form form, .js-address-form').on('submit', function (e) {
            let valid = true;
            $('input[required], select[required], textarea[required]').each(function () {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid'); // Ajoute une classe pour le style si souhaité
                    valid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!valid) {
                alert("Veuillez remplir tous les champs obligatoires.");
                e.preventDefault();
            }
        });
    }


});
