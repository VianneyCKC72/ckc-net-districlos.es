<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/DtcPalierService.php';
require_once __DIR__ . '/DtcCartRuleManager.php';

if (class_exists('DtcPalierMailer', false)) {
    return;
}

class DtcPalierMailer
{
    private const WARNING_DAYS = 14;

    /**
     * Recalcule le palier, synchronise la règle panier, envoie les mails utiles.
     *
     * @return array{upgrade:int,downgrade:int,warning:int}
     */
    public static function processCustomer(int $idCustomer): array
    {
        $stats = ['upgrade' => 0, 'downgrade' => 0, 'warning' => 0];

        if ($idCustomer <= 0 || !DtcPalierService::isProCustomer($idCustomer)) {
            return $stats;
        }

        $before = DtcPalierService::getCustomerTier($idCustomer) ?: [];
        $tierBeforeName = (string) ($before['current_tier'] ?? 'none');

        $tier = DtcPalierService::recompute($idCustomer);
        DtcCartRuleManager::syncCartRule($idCustomer, $tier);

        $after = DtcPalierService::getCustomerTier($idCustomer);
        if (!$after) {
            return $stats;
        }

        $newTierName = (string) ($after['current_tier'] ?? 'none');
        $notifiedTier = isset($before['last_notified_tier']) ? (string) $before['last_notified_tier'] : '';

        // Baseline : dernier palier notifié, sinon palier AVANT recalcul (évite de rater l'upgrade commande)
        $baselineTier = ($notifiedTier !== '') ? $notifiedTier : $tierBeforeName;

        $rankBefore = DtcPalierService::getTierRankByName($baselineTier);
        $rankAfter = DtcPalierService::getTierRankByName($newTierName);

        if ($rankAfter > $rankBefore) {
            if (self::send($idCustomer, 'upgrade', $after, $baselineTier)) {
                $stats['upgrade'] = 1;
            }
            self::updateNotificationState($idCustomer, [
                'last_notified_tier' => $newTierName,
                'warning_from_tier' => null,
                'warning_sent_at' => null,
            ]);
        } elseif ($rankAfter < $rankBefore) {
            if (self::send($idCustomer, 'downgrade', $after, $baselineTier)) {
                $stats['downgrade'] = 1;
            }
            self::updateNotificationState($idCustomer, [
                'last_notified_tier' => $newTierName,
                'warning_from_tier' => null,
                'warning_sent_at' => null,
            ]);
        } elseif ($notifiedTier === '' || $notifiedTier !== $newTierName) {
            self::updateNotificationState($idCustomer, [
                'last_notified_tier' => $newTierName,
            ]);
        }

        // Alerte -14 jours (si le palier actuel est menacé)
        $after = DtcPalierService::getCustomerTier($idCustomer) ?: $after;
        $rankNow = DtcPalierService::getTierRankByName((string) ($after['current_tier'] ?? 'none'));
        if ($rankNow <= 0) {
            return $stats;
        }

        $amountIn14 = DtcPalierService::getCumulatedAmountInDays($idCustomer, self::WARNING_DAYS);
        $tierIn14 = DtcPalierService::getTierForAmount($amountIn14);
        $rankIn14 = $tierIn14
            ? DtcPalierService::getTierRankByName((string) $tierIn14['name'])
            : 0;

        if ($rankIn14 < $rankNow) {
            $fromTier = (string) $after['current_tier'];
            $alreadyWarned = !empty($after['warning_sent_at'])
                && (string) ($after['warning_from_tier'] ?? '') === $fromTier;

            if (!$alreadyWarned && self::send($idCustomer, 'warning', $after, $fromTier)) {
                $stats['warning'] = 1;
                self::updateNotificationState($idCustomer, [
                    'warning_from_tier' => $fromTier,
                    'warning_sent_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } else {
            if (!empty($after['warning_sent_at']) || !empty($after['warning_from_tier'])) {
                self::updateNotificationState($idCustomer, [
                    'warning_from_tier' => null,
                    'warning_sent_at' => null,
                ]);
            }
        }

        return $stats;
    }

    private static function send(int $idCustomer, string $type, array $tierRow, string $previousTierName): bool
    {
        $customer = new Customer($idCustomer);
        if (!Validate::isLoadedObject($customer) || !Validate::isEmail($customer->email)) {
            return false;
        }

        $context = Context::getContext();
        $idLang = (int) ($customer->id_lang ?: Configuration::get('PS_LANG_DEFAULT'));
        $idShop = (int) ($context->shop->id ?? Configuration::get('PS_SHOP_DEFAULT'));

        $tierLabel = DtcPalierService::getTierShortLabel((string) ($tierRow['current_tier'] ?? 'none'));
        $previousLabel = DtcPalierService::getTierShortLabel($previousTierName);
        $discount = (float) ($tierRow['discount_percent'] ?? 0);
        $discountDisplay = rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.') . '%';

        $statusUrl = $context->link
            ? $context->link->getPageLink('discount', true, $idLang, null, false, $idShop)
            : '';

        $baseUrl = rtrim(
            $context->link
                ? $context->link->getBaseLink($idShop, true)
                : Tools::getShopDomainSsl(true) . __PS_BASE_URI__,
            '/'
        ) . '/';

        $shopPhone = trim((string) Configuration::get('PS_SHOP_PHONE', null, null, $idShop));
        if ($shopPhone === '') {
            $shopPhone = '04 22 53 10 34';
        }

        $language = new Language($idLang);
        $isEn = Validate::isLoadedObject($language) && $language->iso_code === 'en';

        $templates = [
            'upgrade' => [
                'template' => 'dtc_palier_upgrade',
                'subject' => $isEn
                    ? 'Club Pro: you are now ' . $tierLabel . '!'
                    : 'Club Pro : vous passez au statut ' . $tierLabel . ' !',
            ],
            'warning' => [
                'template' => 'dtc_palier_warning',
                'subject' => $isEn
                    ? 'Club Pro: your ' . $previousLabel . ' status is at risk'
                    : 'Club Pro : votre statut ' . $previousLabel . ' est menacé',
            ],
            'downgrade' => [
                'template' => 'dtc_palier_downgrade',
                'subject' => $isEn
                    ? 'Club Pro: your status has changed'
                    : 'Club Pro : votre statut a évolué',
            ],
        ];

        if (!isset($templates[$type])) {
            return false;
        }

        $vars = [
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{tier_label}' => $tierLabel,
            '{previous_tier_label}' => $previousLabel,
            '{discount}' => $discountDisplay,
            '{discount_sentence}' => '',
            '{status_url}' => $statusUrl,
            '{shop_phone}' => $shopPhone,
            '{shop_phone_link}' => preg_replace('/\D+/', '', $shopPhone),
            '{stores_url}' => $baseUrl . 'content/11-nos-magasins-de-cloture',
            '{club_pro_logo}' => $baseUrl . 'modules/dtc_paliers_remise/views/img/logo-pro.png',
        ];

        // Warning : on parle du statut actuel menacé
        if ($type === 'warning') {
            $vars['{tier_label}'] = $previousLabel;
            $currentTier = DtcPalierService::getTierForAmount(
                DtcPalierService::getCumulatedAmount($idCustomer)
            );
            $vars['{discount}'] = $currentTier
                ? rtrim(rtrim(number_format((float) $currentTier['discount'], 2, '.', ''), '0'), '.') . '%'
                : $discountDisplay;
        }

        if ($type === 'downgrade') {
            if ($isEn) {
                $vars['{discount_sentence}'] = $discount > 0
                    ? ' with a ' . $discountDisplay . ' discount'
                    : ' (you no longer have a Club Pro discount for now)';
            } else {
                $vars['{discount_sentence}'] = $discount > 0
                    ? ' avec ' . $discountDisplay . ' de réduction'
                    : ' (vous n\'avez plus de remise Club Pro pour le moment)';
            }
        }

        return (bool) Mail::Send(
            $idLang,
            $templates[$type]['template'],
            $templates[$type]['subject'],
            $vars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            dirname(__DIR__) . '/mails/',
            false,
            $idShop
        );
    }

    private static function updateNotificationState(int $idCustomer, array $data): void
    {
        // Autorise le NULL SQL pour reset warning
        $sets = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                $sets[] = '`' . bqSQL($key) . '` = NULL';
            } else {
                $sets[] = '`' . bqSQL($key) . '` = \'' . pSQL((string) $value) . '\'';
            }
        }

        if (!$sets) {
            return;
        }

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'dtc_customer_palier`
             SET ' . implode(', ', $sets) . '
             WHERE id_customer = ' . (int) $idCustomer
        );
    }
}
