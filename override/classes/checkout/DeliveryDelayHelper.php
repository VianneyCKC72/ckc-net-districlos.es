<?php
/**
 * Remplacement [delaiLivraison+N] → date FR (jours ouvrés, hors WE et fériés).
 *
 * @author Districlos
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DeliveryDelayHelper
{
    /**
     * Échappe le texte BO, puis remplace [delaiLivraison+N] par une date en <strong>.
     *
     * @param string $delay
     *
     * @return string
     */
    public static function replace($delay)
    {
        $delay = (string) $delay;
        if ($delay === '') {
            return '';
        }

        $safe = htmlspecialchars($delay, ENT_QUOTES, 'UTF-8');

        if (strpos($safe, '[delaiLivraison+') === false) {
            return $safe;
        }

        return preg_replace_callback(
            '/\[delaiLivraison\+(\d+)\]/',
            function (array $matches) {
                return self::formatFrenchDate(self::getBusinessDayDate((int) $matches[1]));
            },
            $safe
        );
    }

    /**
     * @param int $businessDays
     *
     * @return DateTime
     */
    private static function getBusinessDayDate($businessDays)
    {
        $date = new DateTime('today');
        $date->modify('+1 day');
        $remaining = max(0, (int) $businessDays);

        while ($remaining > 0) {
            if (self::isBusinessDay($date)) {
                --$remaining;
                if ($remaining === 0) {
                    break;
                }
            }
            $date->modify('+1 day');
        }

        return $date;
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    private static function isBusinessDay(DateTime $date)
    {
        if ((int) $date->format('N') >= 6) {
            return false;
        }

        return !in_array($date->format('Y-m-d'), self::getFrenchHolidays((int) $date->format('Y')), true);
    }

    /**
     * @param int $year
     *
     * @return string[]
     */
    private static function getFrenchHolidays($year)
    {
        static $cache = [];

        if (isset($cache[$year])) {
            return $cache[$year];
        }

        $holidays = [];
        foreach (['-01-01', '-05-01', '-05-08', '-07-14', '-08-15', '-11-01', '-11-11', '-12-25'] as $suffix) {
            $holidays[] = $year . $suffix;
        }

        if (function_exists('easter_date')) {
            $easter = new DateTime();
            $easter->setTimestamp(easter_date($year));

            foreach ([1, 39, 50] as $daysAfterEaster) {
                $holiday = clone $easter;
                $holiday->modify('+' . $daysAfterEaster . ' day');
                $holidays[] = $holiday->format('Y-m-d');
            }
        }

        return $cache[$year] = $holidays;
    }

    /**
     * @param DateTime $date
     *
     * @return string
     */
    private static function formatFrenchDate(DateTime $date)
    {
        static $weekDays = [
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            7 => 'dimanche',
        ];
        static $months = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];

        return '<strong>'
            . $weekDays[(int) $date->format('N')] . ' '
            . (int) $date->format('j') . ' '
            . $months[(int) $date->format('n')]
            . '</strong>';
    }
}
