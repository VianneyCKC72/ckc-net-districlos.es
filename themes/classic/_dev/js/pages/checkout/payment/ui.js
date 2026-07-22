export function applyPaymentLogos() {
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
}

export function boldPaymentInstallments() {
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

export function markEmptyPaymentForms() {
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
