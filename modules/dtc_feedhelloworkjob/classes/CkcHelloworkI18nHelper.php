<?php
/**
 * CKC Vianney — sync + localize HelloWork offers.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcHelloworkI18nHelper
{
    public static function ensureSql()
    {
        $db = Db::getInstance();
        $db->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckc_hellowork_job` (
                `id_job` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `offer_code` VARCHAR(128) NOT NULL,
                `company` VARCHAR(255) NOT NULL DEFAULT \'\',
                `city` VARCHAR(255) NOT NULL DEFAULT \'\',
                `link` TEXT,
                `create_date` VARCHAR(32) NOT NULL DEFAULT \'\',
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_job`),
                UNIQUE KEY `offer_code` (`offer_code`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
        $db->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ckc_hellowork_job_lang` (
                `id_job` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `title` TEXT NOT NULL,
                `description` MEDIUMTEXT NOT NULL,
                PRIMARY KEY (`id_job`, `id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    /**
     * @param array $jobs
     *
     * @return int
     */
    public static function syncFromFeed(array $jobs)
    {
        self::ensureSql();
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        if ($idLangDefault <= 0) {
            $idLangDefault = 1;
        }

        $count = 0;
        foreach ($jobs as $job) {
            if (!is_array($job)) {
                continue;
            }
            if (self::upsertFromFeed($job, $idLangDefault) > 0) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array $jobs
     *
     * @return array
     */
    public static function applyTranslations(array $jobs)
    {
        self::ensureSql();
        $context = Context::getContext();
        $idLang = (int) ($context && $context->language ? $context->language->id : 0);
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
        if ($idLangDefault <= 0) {
            $idLangDefault = 1;
        }
        if ($idLang <= 0 || $idLang === $idLangDefault) {
            return array_values($jobs);
        }

        $localized = [];
        foreach ($jobs as $job) {
            if (!is_array($job)) {
                continue;
            }
            $offerCode = self::extractOfferCode($job);
            $idJob = 0;
            if ($offerCode !== '') {
                $idJob = (int) Db::getInstance()->getValue(
                    'SELECT id_job FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job`
                     WHERE offer_code = \'' . pSQL($offerCode) . '\''
                );
            }
            if ($idJob > 0) {
                $row = self::getLangRow($idJob, $idLang);
                if ($row) {
                    if ($row['title'] !== '') {
                        $job['NameAdvertise'] = $row['title'];
                    }
                    if ($row['description'] !== '') {
                        $job['Description'] = $row['description'];
                    }
                }
            }
            $localized[] = $job;
        }

        return $localized;
    }

    /**
     * @param array $job
     * @param int $idLangSource
     *
     * @return int
     */
    public static function upsertFromFeed(array $job, $idLangSource)
    {
        $offerCode = self::extractOfferCode($job);
        if ($offerCode === '') {
            return 0;
        }

        $title = trim((string) ($job['NameAdvertise'] ?? ''));
        $description = (string) ($job['Description'] ?? '');
        $company = Tools::substr(trim((string) ($job['CompanyName'] ?? '')), 0, 255);
        $city = Tools::substr(trim((string) ($job['City'] ?? '')), 0, 255);
        $link = (string) ($job['LinkPage'] ?? '');
        $createDate = Tools::substr((string) ($job['CreateDate'] ?? ''), 0, 32);
        $idLangSource = (int) $idLangSource;

        $db = Db::getInstance();
        $existing = $db->getRow(
            'SELECT id_job FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job`
             WHERE offer_code = \'' . pSQL($offerCode) . '\''
        );
        $idJob = $existing ? (int) $existing['id_job'] : 0;

        $payload = [
            'offer_code' => pSQL($offerCode),
            'company' => pSQL($company),
            'city' => pSQL($city),
            'link' => pSQL($link),
            'create_date' => pSQL($createDate),
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        if ($idJob > 0) {
            $db->update('ckc_hellowork_job', $payload, 'id_job = ' . $idJob);
        } else {
            $db->insert('ckc_hellowork_job', $payload);
            $idJob = (int) $db->Insert_ID();
        }

        if ($idJob <= 0 || $idLangSource <= 0) {
            return $idJob;
        }

        $oldSource = self::getLangRow($idJob, $idLangSource);
        $sourceChanged = $oldSource
            && ($oldSource['title'] !== $title || $oldSource['description'] !== $description);

        self::saveLangRow($idJob, $idLangSource, $title, $description);

        if ($sourceChanged) {
            $db->delete(
                'ckc_hellowork_job_lang',
                'id_job = ' . $idJob . ' AND id_lang != ' . $idLangSource
            );
        }

        return $idJob;
    }

    /**
     * @param array $job
     *
     * @return string
     */
    public static function extractOfferCode(array $job)
    {
        $link = (string) ($job['LinkPage'] ?? '');
        if ($link !== '' && preg_match('#offre-([A-Za-z0-9_-]+)#i', $link, $m)) {
            return Tools::substr($m[1], 0, 128);
        }

        $basis = trim((string) ($job['NameAdvertise'] ?? ''))
            . '|' . trim((string) ($job['City'] ?? ''))
            . '|' . trim((string) ($job['CompanyName'] ?? ''));
        if ($basis === '||') {
            return '';
        }

        return Tools::substr(sha1($basis), 0, 40);
    }

    /**
     * @param int $idJob
     * @param int $idLang
     *
     * @return array{title: string, description: string}|null
     */
    public static function getLangRow($idJob, $idLang)
    {
        $idJob = (int) $idJob;
        $idLang = (int) $idLang;
        if ($idJob <= 0 || $idLang <= 0) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT title, description FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job_lang`
             WHERE id_job = ' . $idJob . ' AND id_lang = ' . $idLang
        );
        if (!$row) {
            return null;
        }

        return [
            'title' => (string) $row['title'],
            'description' => (string) $row['description'],
        ];
    }

    /**
     * @param int $idJob
     * @param int $idLang
     * @param string $title
     * @param string $description
     *
     * @return bool
     */
    public static function saveLangRow($idJob, $idLang, $title, $description)
    {
        $idJob = (int) $idJob;
        $idLang = (int) $idLang;
        if ($idJob <= 0 || $idLang <= 0) {
            return false;
        }

        self::ensureSql();
        $db = Db::getInstance();
        $exists = (int) $db->getValue(
            'SELECT id_job FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job_lang`
             WHERE id_job = ' . $idJob . ' AND id_lang = ' . $idLang
        );
        $payload = [
            'title' => pSQL($title, true),
            'description' => pSQL($description, true),
        ];

        if ($exists) {
            return (bool) $db->update(
                'ckc_hellowork_job_lang',
                $payload,
                'id_job = ' . $idJob . ' AND id_lang = ' . $idLang
            );
        }

        $payload['id_job'] = $idJob;
        $payload['id_lang'] = $idLang;

        return (bool) $db->insert('ckc_hellowork_job_lang', $payload);
    }

    /**
     * @return int
     */
    public static function countJobs()
    {
        self::ensureSql();

        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ckc_hellowork_job`'
        );
    }
}
