<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Dtc_Carrier_Badge extends Module
{
    public function __construct()
    {
        $this->name = 'dtc_carrier_badge';
        $this->version = '1.0.0';
        $this->author = 'Districlos';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        parent::__construct();

        $this->displayName = $this->l('Badge transporteur checkout');
        $this->description = $this->l('Badge configurable par transporteur au checkout.');
    }

    public function install()
    {
        $this->cleanupOrphanedPermissions();

        return parent::install()
            && Db::getInstance()->execute(
                'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dtc_carrier_badge_lang` (
                    `id_reference` INT UNSIGNED NOT NULL,
                    `id_lang` INT UNSIGNED NOT NULL,
                    `badge` VARCHAR(128) NOT NULL DEFAULT \'\',
                    PRIMARY KEY (`id_reference`, `id_lang`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;'
            )
            && $this->registerHook('actionCarrierUpdate')
            && $this->registerHook('actionObjectCarrierAddAfter');
    }

    public function uninstall()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dtc_carrier_badge_lang`')
            && parent::uninstall();
    }

    public function hookActionCarrierUpdate($params)
    {
        if (!empty($params['carrier'])) {
            $this->saveFromPost((int) $params['carrier']->id_reference);
        }
    }

    public function hookActionObjectCarrierAddAfter($params)
    {
        if (!empty($params['object'])) {
            $this->saveFromPost((int) $params['object']->id_reference);
        }
    }

    public function getCarrierBadgeFieldsValues($carrier)
    {
        $badges = [];
        $idReference = Validate::isLoadedObject($carrier) ? (int) $carrier->id_reference : 0;

        if ($idReference > 0) {
            $rows = Db::getInstance()->executeS(
                'SELECT `id_lang`, `badge`
                FROM `' . _DB_PREFIX_ . 'dtc_carrier_badge_lang`
                WHERE `id_reference` = ' . $idReference
            );
            foreach ($rows ?: [] as $row) {
                $badges[(int) $row['id_lang']] = $row['badge'];
            }
        }

        return ['dtc_checkout_badge' => $badges];
    }

    public function getCarrierBadgeFormField()
    {
        return [
            'type' => 'text',
            'label' => $this->l('Badge checkout'),
            'name' => 'dtc_checkout_badge',
            'lang' => true,
            'maxlength' => 128,
            'hint' => $this->l('Ex. : Offre limitée ! Laisser vide pour masquer.'),
        ];
    }

    public function getBadge($idReference, $idLang)
    {
        $badges = $this->getBadges([(int) $idReference], (int) $idLang);

        return isset($badges[(int) $idReference]) ? $badges[(int) $idReference] : '';
    }

    /**
     * Badges pour plusieurs id_reference (1 requête).
     *
     * @param int[] $idReferences
     * @param int $idLang
     *
     * @return array<int, string> id_reference => badge
     */
    public function getBadges(array $idReferences, $idLang)
    {
        $idReferences = array_values(array_unique(array_filter(array_map('intval', $idReferences))));
        if (!$idReferences) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_reference`, `badge`
            FROM `' . _DB_PREFIX_ . 'dtc_carrier_badge_lang`
            WHERE `id_lang` = ' . (int) $idLang . '
            AND `id_reference` IN (' . implode(',', $idReferences) . ')'
        );

        $badges = [];
        foreach ($rows ?: [] as $row) {
            $badge = trim((string) $row['badge']);
            if ($badge !== '') {
                $badges[(int) $row['id_reference']] = $badge;
            }
        }

        return $badges;
    }

    private function saveFromPost($idReference)
    {
        if ($idReference <= 0) {
            return;
        }

        // Ne toucher la BDD que si le formulaire wizard a bien posté le champ badge
        $hasBadgeField = false;
        foreach (Language::getLanguages(false) as $lang) {
            if (Tools::getIsset('dtc_checkout_badge_' . (int) $lang['id_lang'])) {
                $hasBadgeField = true;
                break;
            }
        }
        if (!$hasBadgeField) {
            return;
        }

        Db::getInstance()->delete('dtc_carrier_badge_lang', 'id_reference = ' . $idReference);

        foreach (Language::getLanguages(false) as $lang) {
            $badge = trim((string) Tools::getValue('dtc_checkout_badge_' . (int) $lang['id_lang']));
            if ($badge === '') {
                continue;
            }

            Db::getInstance()->insert('dtc_carrier_badge_lang', [
                'id_reference' => $idReference,
                'id_lang' => (int) $lang['id_lang'],
                'badge' => pSQL($badge, true),
            ]);
        }
    }

    private function cleanupOrphanedPermissions()
    {
        $roles = Db::getInstance()->executeS(
            'SELECT `id_authorization_role`
            FROM `' . _DB_PREFIX_ . 'authorization_role`
            WHERE `slug` LIKE "ROLE_MOD_MODULE_DTC_CARRIER_BADGE_%"'
        );

        foreach ($roles ?: [] as $role) {
            Db::getInstance()->delete(
                'module_access',
                'id_authorization_role = ' . (int) $role['id_authorization_role']
            );
            Db::getInstance()->delete(
                'authorization_role',
                'id_authorization_role = ' . (int) $role['id_authorization_role']
            );
        }
    }
}
