<?php
/**
 * Plages horaires et jours d’affichage du lanceur / panneau Closy.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DtcClosySchedule
{
    /** @var array<string, string> */
    private const CKC_LEGACY_KEYS = [
        'HOUR_START' => 'CKC_YOURGPT_HOUR_START',
        'HOUR_END' => 'CKC_YOURGPT_HOUR_END',
        'ACTIVE_DAYS_SELECTED' => 'CKC_YOURGPT_ACTIVE_DAYS_SELECTED',
    ];

    public static function migrateFromCkc(): void
    {
        foreach (self::CKC_LEGACY_KEYS as $suffix => $legacyKey) {
            $newKey = DtcClosyConfig::key('SCHEDULE_' . $suffix);
            $current = trim((string) Configuration::get($newKey));
            if ($current !== '') {
                continue;
            }
            if (Configuration::hasKey($legacyKey)) {
                $legacy = trim((string) Configuration::get($legacyKey));
                if ($legacy !== '') {
                    Configuration::updateValue($newKey, $legacy);
                }
            }
        }
    }

    public static function isVisibleNow(): bool
    {
        if ((int) DtcClosyConfig::get('SCHEDULE_ENABLED', 1) !== 1) {
            return true;
        }

        $hourStart = (int) DtcClosyConfig::get('SCHEDULE_HOUR_START', 9);
        $hourEnd = (int) DtcClosyConfig::get('SCHEDULE_HOUR_END', 19);
        $currentHour = (int) date('G');

        if ($currentHour < $hourStart || $currentHour >= $hourEnd) {
            return false;
        }

        $selected = self::getSelectedDayIds();
        if ($selected === []) {
            return false;
        }

        return in_array((int) date('N'), $selected, true);
    }

    /**
     * @return list<array{id:int,label:string}>
     */
    public static function getWeekDayOptions(): array
    {
        return [
            ['id' => 1, 'label' => 'Lundi'],
            ['id' => 2, 'label' => 'Mardi'],
            ['id' => 3, 'label' => 'Mercredi'],
            ['id' => 4, 'label' => 'Jeudi'],
            ['id' => 5, 'label' => 'Vendredi'],
            ['id' => 6, 'label' => 'Samedi'],
            ['id' => 7, 'label' => 'Dimanche'],
        ];
    }

    /**
     * @return list<int>
     */
    public static function getSelectedDayIds(): array
    {
        $raw = trim((string) DtcClosyConfig::get('SCHEDULE_ACTIVE_DAYS_SELECTED', '1,2,3,4,5'));
        if ($raw === '') {
            return [];
        }

        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim($part);
            if ($id >= 1 && $id <= 7) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
