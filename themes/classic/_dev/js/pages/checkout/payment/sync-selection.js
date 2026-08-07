export function syncPaymentCardSelection() {
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
