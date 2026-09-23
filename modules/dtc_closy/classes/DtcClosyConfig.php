<?php

/**

 * Clés de configuration et migration depuis dtc_productquestions / ckc_yourgpt.

 */

if (!defined('_PS_VERSION_')) {

    exit;

}



class DtcClosyConfig

{

    public const CAROUSEL_MAX_PRODUCTS = 8;

    public const CAROUSEL_MAX_URLS = 12;



    /** @var array<string, string> suffix => clé legacy dtc_productquestions */

    private const LEGACY_KEYS = [

        'ACTIVE' => 'DTC_PRODUCTQUESTIONS_ACTIVE',

        'OPENAI_API_KEY' => 'DTC_PRODUCTQUESTIONS_OPENAI_API_KEY',

        'OPENAI_MODEL' => 'DTC_PRODUCTQUESTIONS_OPENAI_MODEL',

        'HEADER_LABEL' => 'DTC_PRODUCTQUESTIONS_HEADER_LABEL',

        'STATIC_TRANSPORT' => 'DTC_PRODUCTQUESTIONS_STATIC_TRANSPORT',

        'CTA_LABEL' => 'DTC_PRODUCTQUESTIONS_CTA_LABEL',

        'BATCH_SIZE' => 'DTC_PRODUCTQUESTIONS_BATCH_SIZE',

        'SHOW_FALLBACK' => 'DTC_PRODUCTQUESTIONS_SHOW_FALLBACK',

        'PANEL_ACTIVE' => 'DTC_PRODUCTQUESTIONS_PANEL_ACTIVE',

        'YOURGPT_API_TOKEN' => 'DTC_PRODUCTQUESTIONS_YOURGPT_API_TOKEN',

        'YOURGPT_PROJECT_UID' => 'DTC_PRODUCTQUESTIONS_YOURGPT_PROJECT_UID',

        'YOURGPT_WIDGET_UID' => 'DTC_PRODUCTQUESTIONS_YOURGPT_WIDGET_UID',

        'PANEL_TITLE' => 'DTC_PRODUCTQUESTIONS_PANEL_TITLE',

        'PANEL_WELCOME' => 'DTC_PRODUCTQUESTIONS_PANEL_WELCOME',

    ];



    public static function key(string $suffix): string

    {

        return 'DTC_CLOSY_' . $suffix;

    }



    /**

     * @param mixed $default

     *

     * @return mixed

     */

    public static function get(string $suffix, $default = null)

    {

        $key = self::key($suffix);

        $value = Configuration::get($key);

        if ($value !== false) {

            return $value;

        }



        $legacy = self::LEGACY_KEYS[$suffix] ?? null;

        if ($legacy !== null) {

            $legacyValue = Configuration::get($legacy);

            if ($legacyValue !== false) {

                return $legacyValue;

            }

        }



        return $default;

    }



    public static function update(string $suffix, $value): void

    {

        Configuration::updateValue(self::key($suffix), $value);

    }



    public static function deleteAll(): void

    {

        $suffixes = array_keys(self::LEGACY_KEYS);

        $suffixes = array_merge($suffixes, [

            'PANEL_WELCOME_GLOBAL',

            'SCHEDULE_ENABLED',

            'SCHEDULE_HOUR_START',

            'SCHEDULE_HOUR_END',

            'SCHEDULE_ACTIVE_DAYS_SELECTED',

            'CHAT_TOKEN',

        ]);



        foreach ($suffixes as $suffix) {

            Configuration::deleteByName(self::key($suffix));

        }

    }



    public static function migrateFromLegacy(): void

    {

        foreach (self::LEGACY_KEYS as $suffix => $legacyKey) {

            $newKey = self::key($suffix);

            if (!Configuration::hasKey($newKey) && Configuration::hasKey($legacyKey)) {

                Configuration::updateValue($newKey, Configuration::get($legacyKey));

            }

        }



        $widget = self::key('YOURGPT_WIDGET_UID');

        $project = self::key('YOURGPT_PROJECT_UID');

        if (!Configuration::hasKey($widget) && Configuration::hasKey($project)) {

            $legacyProject = trim((string) Configuration::get($project));

            if ($legacyProject !== '') {

                Configuration::updateValue($widget, $legacyProject);

            }

        }



        if (!Configuration::hasKey(self::key('PANEL_WELCOME_GLOBAL'))) {

            $legacyWelcome = trim((string) self::get('PANEL_WELCOME', ''));

            if ($legacyWelcome !== '') {

                Configuration::updateValue(

                    self::key('PANEL_WELCOME_GLOBAL'),

                    'Bonjour ! Je suis Closy, votre assistant. Comment puis-je vous aider aujourd’hui ?'

                );

            }

        }



        require_once dirname(__FILE__) . '/DtcClosySchedule.php';

        DtcClosySchedule::migrateFromCkc();

    }



    /**

     * Migrations idempotentes (install, ouverture BO).

     */

    public static function runMigrations(): void

    {

        self::migrateFromLegacy();



        if (!Configuration::hasKey(self::key('PANEL_WELCOME_GLOBAL'))) {

            Configuration::updateValue(

                self::key('PANEL_WELCOME_GLOBAL'),

                'Bonjour ! Je suis Closy, votre assistant. Comment puis-je vous aider aujourd’hui ?'

            );

        }



        if (!Configuration::hasKey(self::key('SCHEDULE_ENABLED'))) {

            Configuration::updateValue(self::key('SCHEDULE_ENABLED'), 1);

        }

        if (!Configuration::hasKey(self::key('SCHEDULE_HOUR_START'))) {

            Configuration::updateValue(self::key('SCHEDULE_HOUR_START'), 9);

        }

        if (!Configuration::hasKey(self::key('SCHEDULE_HOUR_END'))) {

            Configuration::updateValue(self::key('SCHEDULE_HOUR_END'), 19);

        }

        if (!Configuration::hasKey(self::key('SCHEDULE_ACTIVE_DAYS_SELECTED'))) {

            Configuration::updateValue(self::key('SCHEDULE_ACTIVE_DAYS_SELECTED'), '1,2,3,4,5');

        }



        require_once dirname(__FILE__) . '/DtcClosyChatSecurity.php';

        DtcClosyChatSecurity::ensureToken();

        self::migrateToMultilang();

    }

    /**
     * Textes FO éditables en BO, une valeur par langue.
     *
     * @return array<string, array{fr: string, en: string}>
     */
    public static function getTranslatableConfigKeys(): array
    {
        return [
            'DTC_CLOSY_HEADER_LABEL' => [
                'fr' => 'Une question sur le produit ?',
                'en' => 'A question about this product?',
            ],
            'DTC_CLOSY_STATIC_TRANSPORT' => [
                'fr' => 'Quel sont les délais de livraison ?',
                'en' => 'What are the delivery times?',
            ],
            'DTC_CLOSY_CTA_LABEL' => [
                'fr' => 'Posez votre question',
                'en' => 'Ask your question',
            ],
            'DTC_CLOSY_PANEL_TITLE' => [
                'fr' => 'Assistant produit',
                'en' => 'Product assistant',
            ],
            'DTC_CLOSY_PANEL_WELCOME' => [
                'fr' => 'Bonjour ! Je suis là pour répondre à vos questions sur ce produit (pose, fixation, usage…).',
                'en' => 'Hello! I\'m here to answer your questions about this product (fitting, fixing, usage…).',
            ],
            'DTC_CLOSY_PANEL_WELCOME_GLOBAL' => [
                'fr' => 'Bonjour ! Je suis Closy, votre assistant. Comment puis-je vous aider aujourd’hui ?',
                'en' => 'Hello! I\'m Closy, your assistant. How can I help you today?',
            ],
        ];
    }

    public static function getLangValue(string $key, int $idLang): string
    {
        $defaults = self::getTranslatableConfigKeys();
        $iso = strtolower((string) Language::getIsoById($idLang));
        $value = Configuration::get($key, $idLang);
        $raw = is_string($value) ? trim($value) : '';

        if (isset($defaults[$key])) {
            $frDefault = (string) ($defaults[$key]['fr'] ?? '');
            $isoDefault = (string) ($defaults[$key][$iso] ?? '');
            // EN encore vide ou copié depuis le FR
            if ($iso !== 'fr' && $isoDefault !== '' && ($raw === '' || $raw === $frDefault)) {
                return $isoDefault;
            }
        }

        if ($raw !== '') {
            return $raw;
        }

        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');
        if ($idLang !== $defaultLang) {
            $fallback = Configuration::get($key, $defaultLang);
            if (is_string($fallback) && $fallback !== '') {
                return $fallback;
            }
        }

        $plain = Configuration::get($key);

        return is_string($plain) ? $plain : '';
    }

    /**
     * @return array<int, string>
     */
    public static function getLangValues(string $key): array
    {
        $out = [];
        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];
            $out[$idLang] = self::getLangValue($key, $idLang);
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    public static function collectLangPost(string $key): array
    {
        $values = [];
        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];
            $values[$idLang] = trim((string) Tools::getValue($key . '_' . $idLang));
        }

        return $values;
    }

    public static function seedTranslatableDefaults(): void
    {
        foreach (self::getTranslatableConfigKeys() as $key => $defaults) {
            self::seedOneTranslatableKey($key, $defaults);
        }
    }

    /**
     * Convertit les anciennes configs monolingues vers une valeur par langue.
     */
    public static function migrateToMultilang(): bool
    {
        foreach (self::getTranslatableConfigKeys() as $key => $defaults) {
            self::seedOneTranslatableKey($key, $defaults, true);
        }

        return true;
    }

    /**
     * @param array{fr: string, en: string} $defaults
     */
    private static function seedOneTranslatableKey(string $key, array $defaults, bool $keepExisting = false): void
    {
        $plain = (string) Configuration::get($key);
        $defaultLangId = (int) Configuration::get('PS_LANG_DEFAULT');
        $values = [];

        foreach (Language::getLanguages(false) as $lang) {
            $idLang = (int) $lang['id_lang'];
            $iso = (string) $lang['iso_code'];

            if ($keepExisting) {
                $existing = Configuration::get($key, $idLang);
                $frDefault = $defaults['fr'] ?? '';
                if (is_string($existing) && $existing !== '' && !($iso === 'en' && $existing === $frDefault)) {
                    $values[$idLang] = $existing;
                    continue;
                }
            }

            if ($iso === 'en') {
                $values[$idLang] = $defaults['en'] ?? '';
            } elseif ($idLang === $defaultLangId || $iso === 'fr') {
                $values[$idLang] = $plain !== '' ? $plain : ($defaults['fr'] ?? '');
            } else {
                $values[$idLang] = $plain !== '' ? $plain : ($defaults['fr'] ?? '');
            }
        }

        if (!empty($values)) {
            Configuration::updateValue($key, $values, true);
        }
    }

}

