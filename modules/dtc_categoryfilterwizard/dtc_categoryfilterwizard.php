<?php
/**
 * Category Filter Wizard — Module PrestaShop
 *
 * Affiche un wizard interactif (étapes successives) sur les pages catégorie
 * sélectionnées, intégré via un widget Creative Elements (ps-widget-module).
 * Filtre la liste produits par AJAX sans quitter la page.
 *
 * @author    DISTRICLOS
 * @copyright 2025
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_categoryfilterwizard extends Module
{
    // Tables SQL créées par le module
    const TABLE_CONFIG  = 'dtc_wizard_config';
    const TABLE_STEPS   = 'dtc_wizard_step';

    /**
     * Evite l'affichage du wizard en double (CE + hook standard + fallback TPL).
     * La requête front peut déclencher plusieurs hooks, mais on veut une seule fois.
     */
    // IMPORTANT compat PHP < 7.4 : pas de propriété typée.
    private static $renderedOnceThisRequest = false;

    public function __construct()
    {
        $this->name           = 'dtc_categoryfilterwizard';
        $this->tab            = 'front_office_features';
        $this->version        = '1.0.0';
        $this->author         = 'DISTRICLOS';
        $this->need_instance  = 0;
        $this->bootstrap      = true;

        parent::__construct();

        $this->displayName = $this->l('Category Filter Wizard');
        $this->description = $this->l('Wizard de filtre interactif sur les pages catégorie.');
        $this->ps_versions_compliancy = ['min' => '1.7.0', 'max' => '9.99'];
    }

    /* ------------------------------------------------------------------ */
    /* INSTALLATION                                                          */
    /* ------------------------------------------------------------------ */

    public function install()
    {
        // Nettoie les traces résiduelles pour forcer un insert neuf.
        // Utile après un install interrompu.
        $this->cleanModuleAssociations();

        Hook::exec('actionModuleInstallBefore', ['object' => $this]);

        if (!Validate::isModuleName($this->name) || !$this->checkCompliancy()) {
            $this->_errors[] = Context::getContext()->getTranslator()->trans(
                'Unable to install this module: validation/compliancy failed.',
                [],
                'Admin.Modules.Notification'
            );
            return false;
        }

        // Installe controllers du module si nécessaire (hérité, protégé).
        if (!$this->installControllers()) {
            $this->_errors[] = Context::getContext()->getTranslator()->trans(
                'Could not install module controllers.',
                [],
                'Admin.Modules.Notification'
            );
            $this->uninstallOverrides();
            return false;
        }

        // Insert dans ps_module
        $result = Db::getInstance()->insert($this->table, [
            'name' => $this->name,
            'active' => 1,
            'version' => $this->version,
        ]);

        if (!$result) {
            $this->_errors[] = Context::getContext()->getTranslator()->trans(
                'Technical error: PrestaShop could not install this module.',
                [],
                'Admin.Modules.Notification'
            );
            $this->uninstallOverrides();
            return false;
        }

        $this->id = (int) Db::getInstance()->Insert_ID();

        Cache::clean('Module::isInstalled' . $this->name);
        Cache::clean('Module::getModuleIdByName_' . pSQL($this->name));

        // Enable le module pour le shop courant/contexte.
        if (!$this->enable()) {
            return false;
        }

        // Enregistrement des hooks PrestaShop
        if (!$this->registerHook('displayHeaderCategory') || !$this->registerHook('displayHeader')) {
            return false;
        }

        if (!$this->installSql()) {
            return false;
        }

        // Permissions management (version "safe" : DISTINCT + INSERT IGNORE)
        if (!$this->installPermissionsSafe()) {
            return false;
        }

        // Restrictions sur les groupes clients (même logique que core)
        Group::addRestrictionsForModule($this->id, Shop::getShops(true, null, true));

        // Creative Elements : association module_group (requis par isInCustomerGroups())
        $this->registerModuleGroups();

        Hook::exec('actionModuleInstallAfter', ['object' => $this]);

        if (Module::$update_translations_after_install) {
            $this->updateModuleTranslations();
        }

        return true;
    }

    /**
     * Permissions management "safe" pour éviter l'erreur 1062 sur PRIMARY :
     * l'INSERT SELECT vers module_access peut produire des doublons (id_profile dupliqué)
     * et casser l'installation. On force DISTINCT + INSERT IGNORE.
     */
    private function installPermissionsSafe(): bool
    {
        $db = Db::getInstance();

        foreach (['CREATE', 'READ', 'UPDATE', 'DELETE'] as $action) {
            $roleSlug = 'ROLE_MOD_MODULE_' . strtoupper($this->name) . '_' . $action;

            // Création id_authorization_role sans faire planter si le slug existe déjà
            $db->execute(
                'INSERT INTO ' . _DB_PREFIX_ . 'authorization_role (`slug`)
                 VALUES ("' . pSQL($roleSlug) . '")
                 ON DUPLICATE KEY UPDATE `slug` = `slug`'
            );

            $roleId = (int) $db->getValue(
                'SELECT id_authorization_role
                 FROM ' . _DB_PREFIX_ . 'authorization_role
                 WHERE slug = "' . pSQL($roleSlug) . '"'
            );

            if ($roleId <= 0) {
                return false;
            }

            // On supprime d'abord les entrées éventuelles pour ce roleId (propre)
            $db->execute(
                'DELETE FROM ' . _DB_PREFIX_ . 'module_access
                 WHERE id_authorization_role = ' . (int) $roleId
            );

            // DISTINCT : évite les doublons générés par l'insert-select
            $db->execute(
                'INSERT IGNORE INTO ' . _DB_PREFIX_ . 'module_access (`id_profile`, `id_authorization_role`)
                 SELECT DISTINCT a.`id_profile`, ' . (int) $roleId . '
                 FROM ' . _DB_PREFIX_ . 'access a
                 INNER JOIN ' . _DB_PREFIX_ . 'authorization_role r
                   ON r.`id_authorization_role` = a.`id_authorization_role`
                 WHERE r.`slug` = "ROLE_MOD_TAB_ADMINMODULESSF_' . pSQL($action) . '"'
            );
        }

        return true;
    }

    /**
     * Évite l'erreur "Duplicate entry 'id_shop-id_module' for key 'PRIMARY'" lors de l'install :
     * 1) Supprime toutes les traces du module (module_shop, module_group, puis ps_module).
     * 2) Supprime les lignes orphelines (id_module absent de ps_module).
     * Ainsi parent::install() fait toujours un insert neuf et enable() ne tombe pas sur un doublon.
     */
    private function cleanModuleAssociations(): void
    {
        $db     = Db::getInstance();
        $prefix = _DB_PREFIX_;
        $name   = pSQL($this->name);

        // 1) Nettoyage ciblé par nom puis suppression de la ligne module (reset complet)
        $db->execute(
            'DELETE ms FROM ' . $prefix . 'module_shop ms
             INNER JOIN ' . $prefix . 'module m ON m.id_module = ms.id_module
             WHERE m.name = \'' . $name . '\''
        );
        $db->execute(
            'DELETE mg FROM ' . $prefix . 'module_group mg
             INNER JOIN ' . $prefix . 'module m ON m.id_module = mg.id_module
             WHERE m.name = \'' . $name . '\''
        );
        $db->execute('DELETE FROM ' . $prefix . 'module WHERE name = \'' . $name . '\'');

        // 2) Lignes orphelines (id_module plus présent dans ps_module)
        $db->execute(
            'DELETE ms FROM ' . $prefix . 'module_shop ms
             LEFT JOIN ' . $prefix . 'module m ON m.id_module = ms.id_module
             WHERE m.id_module IS NULL'
        );
        $db->execute(
            'DELETE mg FROM ' . $prefix . 'module_group mg
             LEFT JOIN ' . $prefix . 'module m ON m.id_module = mg.id_module
             WHERE m.id_module IS NULL'
        );
    }

    public function uninstall()
    {
        $this->uninstallSql();

        return parent::uninstall();
    }

    /**
     * Surcharge : évite le crash d'installation si `ps_module_shop` contient
     * déjà des lignes résiduelles pour (id_shop, id_module).
     * Cas typique : install précédente interrompue → doublons lors de enable().
     */
    public function enable($force_all = false)
    {
        $list = Shop::getContextListShopID();
        if (!$this->id || !is_array($list) || empty($list)) {
            return false;
        }

        // On insère avec IGNORE pour ne jamais planter sur une contrainte PRIMARY.
        foreach ($list as $idShop) {
            Db::getInstance()->execute(
                'INSERT IGNORE INTO ' . _DB_PREFIX_ . 'module_shop (id_module, id_shop)
                 VALUES (' . (int) $this->id . ', ' . (int) $idShop . ')'
            );
        }

        Db::getInstance()->update('module', ['active' => 1], 'id_module = ' . (int) $this->id);

        return true;
    }

    private function installSql(): bool
    {
        $prefix = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;

        $sql = [
            // Config par catégorie (active / libellé intro)
            "CREATE TABLE IF NOT EXISTS `{$prefix}" . self::TABLE_CONFIG . "` (
                `id_config`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_category` INT UNSIGNED NOT NULL,
                `id_shop`     INT UNSIGNED NOT NULL DEFAULT 1,
                `active`      TINYINT(1)   NOT NULL DEFAULT 1,
                `title`       VARCHAR(255) NOT NULL DEFAULT '',
                `intro`       TEXT,
                PRIMARY KEY (`id_config`),
                UNIQUE KEY `uk_cat_shop` (`id_category`, `id_shop`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4;",

            // Étapes du wizard (ordonnées)
            "CREATE TABLE IF NOT EXISTS `{$prefix}" . self::TABLE_STEPS . "` (
                `id_step`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_config`     INT UNSIGNED NOT NULL,
                `position`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `label`         VARCHAR(255) NOT NULL DEFAULT '',
                `source_type`   ENUM('feature','attribute') NOT NULL DEFAULT 'feature',
                `id_source`     INT UNSIGNED NOT NULL COMMENT 'id_feature ou id_attribute_group',
                `allowed_value_ids` TEXT NULL COMMENT 'CSV des ids autorisés (id_feature_value ou id_attribute)',
                `custom_options_json` TEXT NULL COMMENT 'JSON des options custom (titre, image_url, product_ids)',
                PRIMARY KEY (`id_step`),
                KEY `k_config` (`id_config`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4;",
        ];

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        // Compat : si la colonne n'existe pas (ancienne version du module),
        // on la rajoute sans casser l'installation existante.
        $this->ensureSchema();

        return true;
    }

    /**
     * Ajoute les colonnes manquantes pour les nouvelles versions du module.
     */
    private function ensureSchema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        // Evite de re-taper `information_schema` à chaque requête front.
        // On ne touche au schéma qu’en cas de migration / mise à jour.
        $cacheKey = 'dtc_categoryfilterwizard_schema_ensured_v1';
        try {
            if (Cache::isStored($cacheKey)) {
                $ensured = true;
                return;
            }
        } catch (\Throwable $e) {
            // Si Cache est indisponible, on retombe sur la vérification SQL.
        }

        $db     = Db::getInstance();
        $prefix = _DB_PREFIX_;

        $tableName = pSQL($prefix . self::TABLE_STEPS);

        $has = (bool) $db->getValue(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = \'' . $tableName . '\'
               AND COLUMN_NAME = \'allowed_value_ids\''
        );

        if (!$has) {
            $db->execute(
                'ALTER TABLE ' . $prefix . self::TABLE_STEPS . '
                 ADD COLUMN allowed_value_ids TEXT NULL COMMENT \'CSV des ids autorisés (id_feature_value ou id_attribute)\''
            );
        }

        // custom_options_json column (ajout version suivante)
        $hasCustom = (bool) $db->getValue(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = \'' . $tableName . '\'
               AND COLUMN_NAME = \'custom_options_json\''
        );

        if (!$hasCustom) {
            $db->execute(
                'ALTER TABLE ' . $prefix . self::TABLE_STEPS . '
                 ADD COLUMN custom_options_json TEXT NULL COMMENT \'JSON des options custom (titre, image_url, product_ids)\''
            );
        }

        // Marque comme assuré (même si on n’a rien modifié).
        $ensured = true;
        try {
            Cache::store($cacheKey, true, 86400);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Normalise la liste de valeurs des options custom au format JSON.
     * Retourne null si vide/invalides.
     *
     * Format attendu :
     * [
     *   {
     *     "title": "Lames pleines",
     *     "image_url": "/img/sub-category/..png",
     *     "product_ids": [1324, 1936]
     *   }
     * ]
     */
    private function normalizeCustomOptionsJson(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $options = [];
        foreach ($decoded as $opt) {
            if (!is_array($opt)) {
                continue;
            }

            $title = isset($opt['title']) ? trim((string) $opt['title']) : '';
            if ($title === '') {
                continue;
            }

            $imageUrl = null;
            if (isset($opt['image_url'])) {
                $imageUrl = trim((string) $opt['image_url']);
                if ($imageUrl === '') {
                    $imageUrl = null;
                }
            }

            $productIdsRaw = $opt['product_ids'] ?? [];
            if (is_string($productIdsRaw)) {
                $productIdsRaw = preg_split('/\s*,\s*/', trim($productIdsRaw)) ?: [];
            }

            $productIdsSet = [];
            if (is_array($productIdsRaw)) {
                foreach ($productIdsRaw as $id) {
                    $idInt = (int) $id;
                    if ($idInt > 0) {
                        $productIdsSet[$idInt] = true;
                    }
                }
            }

            $options[] = [
                'title' => $title,
                'image_url' => $imageUrl,
                'product_ids' => array_keys($productIdsSet),
            ];
        }

        if (count($options) === 0) {
            return null;
        }

        return json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Normalise la liste de valeurs autorisées au format CSV d'entiers.
     * Retourne null si la liste est vide.
     */
    private function normalizeAllowedValueIds($raw): ?string
    {
        $raw = (string) $raw;
        $raw = str_replace([';', ' '], ',', $raw);
        $parts = array_filter(array_map('trim', explode(',', $raw)));
        $ids = [];

        foreach ($parts as $p) {
            $id = (int) $p;
            if ($id > 0) {
                $ids[$id] = $id; // unique
            }
        }

        if (empty($ids)) {
            return null;
        }

        return implode(',', array_values($ids));
    }

    private function uninstallSql(): void
    {
        $prefix = _DB_PREFIX_;
        Db::getInstance()->execute("DROP TABLE IF EXISTS `{$prefix}" . self::TABLE_STEPS . '`');
        Db::getInstance()->execute("DROP TABLE IF EXISTS `{$prefix}" . self::TABLE_CONFIG . '`');
    }

    /**
     * Associe le module à tous les groupes clients / boutiques.
     * Requis par Creative Elements (isInCustomerGroups()).
     * ON DUPLICATE KEY UPDATE évite l'erreur 1062 si une ligne existe déjà.
     */
    private function registerModuleGroups(): void
    {
        $db     = Db::getInstance();
        $shops  = Shop::getShops(true);
        $groups = $db->executeS('SELECT id_group FROM ' . _DB_PREFIX_ . 'group');
        $prefix = _DB_PREFIX_;

        foreach ($shops as $shop) {
            foreach ((array) $groups as $group) {
                $idModule = (int) $this->id;
                $idShop   = (int) $shop['id_shop'];
                $idGroup  = (int) $group['id_group'];

                $db->execute(
                    'INSERT INTO ' . $prefix . 'module_group (id_module, id_shop, id_group)
                     VALUES (' . $idModule . ', ' . $idShop . ', ' . $idGroup . ')
                     ON DUPLICATE KEY UPDATE id_module = id_module'
                );
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* BACK-OFFICE                                                           */
    /* ------------------------------------------------------------------ */

    public function getContent()
    {
        $output = '';

        // Suppression d'un filtre par catégorie
        if (Tools::isSubmit('delete_dtc_wizard')) {
            $output = $this->processDeleteCategory();
            return $output . $this->renderConfigForm();
        }

        // Enregistrement du formulaire d'édition (redirige en cas de succès)
        if (Tools::isSubmit('submit_dtc_wizard')) {
            $output = $this->processConfigSave();
            return $output . $this->renderConfigForm();
        }

        // Création d'un nouveau filtre (ajout d'une catégorie)
        if (Tools::isSubmit('add_dtc_wizard')) {
            $output = $this->processAddCategory();
            return $output . $this->renderConfigForm();
        }

        return $output . $this->renderConfigForm();
    }

    private function processDeleteCategory(): string
    {
        $idCategory = (int) Tools::getValue('delete_id_category');
        $idShop     = (int) Context::getContext()->shop->id;

        if ($idCategory <= 0) {
            return $this->displayError($this->l('Catégorie invalide.'));
        }

        $db = Db::getInstance();

        // Suppression des étapes associées (toujours avant la config pour éviter orphelins)
        $db->execute(
            'DELETE s FROM ' . _DB_PREFIX_ . self::TABLE_STEPS . ' s
             INNER JOIN ' . _DB_PREFIX_ . self::TABLE_CONFIG . ' c ON c.id_config = s.id_config
             WHERE c.id_category = ' . (int) $idCategory . ' AND c.id_shop = ' . (int) $idShop
        );

        // Suppression de la config
        $db->execute(
            'DELETE FROM ' . _DB_PREFIX_ . self::TABLE_CONFIG . '
             WHERE id_category = ' . (int) $idCategory . ' AND id_shop = ' . (int) $idShop
        );

        // Purge du cache CE pour la catégorie supprimée (évite de garder des fragments)
        $this->purgeCeCacheForCategory($idCategory);

        Tools::redirectAdmin(
            AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules')
        );

        return '';
    }

    private function purgeCeCacheForCategory(int $idCategory): void
    {
        $db   = Db::getInstance();
        $ceId = $idCategory . '040101';

        $db->execute(
            "DELETE FROM " . _DB_PREFIX_ . "ce_meta
             WHERE id = '" . pSQL($ceId) . "'
               AND name IN ('_ce_element_cache', '_elementor_css')"
        );
    }

    /**
     * Crée une config minimale pour la catégorie sélectionnée et redirige vers l'édition.
     */
    private function processAddCategory(): string
    {
        $idCategory = (int) Tools::getValue('id_category');
        $idShop     = (int) Context::getContext()->shop->id;

        if ($idCategory <= 0) {
            return $this->displayError($this->l('Veuillez sélectionner une catégorie.'));
        }

        $db = Db::getInstance();

        $exists = (int) $db->getValue(
            'SELECT id_config FROM ' . _DB_PREFIX_ . self::TABLE_CONFIG .
            ' WHERE id_category = ' . $idCategory . ' AND id_shop = ' . $idShop
        );

        if ($exists) {
            // Déjà configurée : rediriger vers l'édition
            Tools::redirectAdmin(
                AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') .
                '&edit_id_category=' . $idCategory
            );
            return '';
        }

        $db->insert(self::TABLE_CONFIG, [
            'id_category' => $idCategory,
            'id_shop'     => $idShop,
            'active'      => 0,
            'title'       => '',
            'intro'       => '',
        ]);

        Tools::redirectAdmin(
            AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') .
            '&edit_id_category=' . $idCategory
        );
        return '';
    }

    private function processConfigSave(): string
    {
        $idCategory = (int) Tools::getValue('id_category');
        $idShop     = (int) Context::getContext()->shop->id;

        if ($idCategory <= 0) {
            return $this->displayError($this->l('Veuillez sélectionner une catégorie.'));
        }

        $db = Db::getInstance();
        $this->ensureSchema();

        // Upsert de la config catégorie
        $exists = (int) $db->getValue(
            'SELECT id_config FROM ' . _DB_PREFIX_ . self::TABLE_CONFIG .
            ' WHERE id_category = ' . $idCategory . ' AND id_shop = ' . $idShop
        );

        $data = [
            'id_category' => $idCategory,
            'id_shop'     => $idShop,
            'active'      => (int) (bool) Tools::getValue('active'),
            'title'       => pSQL((string) Tools::getValue('title', '')),
            'intro'       => pSQL((string) Tools::getValue('intro', '')),
        ];

        if ($exists) {
            $db->update(self::TABLE_CONFIG, $data, 'id_category = ' . $idCategory . ' AND id_shop = ' . $idShop);
            $idConfig = $exists;
        } else {
            $db->insert(self::TABLE_CONFIG, $data);
            $idConfig = (int) $db->Insert_ID();
        }

        // Remplacement des étapes
        $db->execute('DELETE FROM ' . _DB_PREFIX_ . self::TABLE_STEPS . ' WHERE id_config = ' . $idConfig);

        $labels      = Tools::getValue('step_label', []);
        $positions   = Tools::getValue('step_position', []);
        $customOptionsJsons = Tools::getValue('step_custom_options_json', []);
        // Champs "legacy" (encore acceptés si jamais tu réutilises d’anciennes configurations / UI)
        $types       = Tools::getValue('step_type', []);
        $sources     = Tools::getValue('step_source', []);
        $allowedVals = Tools::getValue('step_allowed_value_ids', []);

        foreach ($labels as $i => $label) {
            $rawCustom = $customOptionsJsons[$i] ?? null;
            $normalizedCustom = $this->normalizeCustomOptionsJson($rawCustom);
            $hasCustom = $normalizedCustom !== null;

            if (empty($label) || (!$hasCustom && empty($sources[$i]))) {
                continue;
            }

            $db->insert(self::TABLE_STEPS, [
                'id_config'   => $idConfig,
                'position'    => isset($positions[$i]) ? (int) $positions[$i] : $i,
                'label'       => pSQL($label),
                'source_type' => $hasCustom ? 'feature' : (in_array($types[$i], ['feature', 'attribute'], true) ? $types[$i] : 'feature'),
                'id_source'   => $hasCustom ? 0 : (int) ($sources[$i] ?? 0),
                'allowed_value_ids' => $hasCustom ? null : $this->normalizeAllowedValueIds($allowedVals[$i] ?? ''),
                'custom_options_json' => $normalizedCustom,
            ]);
        }

        $this->purgeCeCache();

        Tools::redirectAdmin(
            AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') .
            '&edit_id_category=' . $idCategory . '&conf=4'
        );
        return '';
    }

    /**
     * Purge le _ce_element_cache CE pour toutes les catégories configurées.
     * L'id CE est construit selon la convention WebshopWorks : {id_category}040101
     */
    private function purgeCeCache(): void
    {
        $db   = Db::getInstance();
        $rows = $db->executeS('SELECT id_category FROM ' . _DB_PREFIX_ . self::TABLE_CONFIG);

        foreach ((array) $rows as $row) {
            $ceId = $row['id_category'] . '040101';
            $db->execute(
                "DELETE FROM ps_ce_meta
                 WHERE id = '" . pSQL($ceId) . "'
                   AND name IN ('_ce_element_cache', '_elementor_css')"
            );
        }
    }

    private function renderConfigForm(): string
    {
        $idShop = (int) Context::getContext()->shop->id;
        $db     = Db::getInstance();

        // Liste des configs existantes (avec link_rewrite pour affichage "id-slug")
        $configs = $db->executeS(
            'SELECT c.*, cat.name as cat_name, cat.link_rewrite as cat_link_rewrite
             FROM ' . _DB_PREFIX_ . self::TABLE_CONFIG . ' c
             LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cat
               ON cat.id_category = c.id_category AND cat.id_lang = ' . (int) $this->context->language->id . '
             WHERE c.id_shop = ' . $idShop . '
             ORDER BY cat.name ASC'
        );

        // Étapes : chargement groupé (évite le N+1).
        $stepsByConfig = [];
        if (!empty($configs)) {
            $configIds = array_values(array_filter(array_map(static function ($c) {
                return isset($c['id_config']) ? (int) $c['id_config'] : 0;
            }, $configs)));

            $configIds = array_values(array_unique($configIds));
            if (!empty($configIds)) {
                $stepsRows = $db->executeS(
                    'SELECT *
                     FROM ' . _DB_PREFIX_ . self::TABLE_STEPS . '
                     WHERE id_config IN (' . implode(',', $configIds) . ')
                     ORDER BY id_config ASC, position ASC'
                );

                foreach ((array) $stepsRows as $row) {
                    $idConfig = (int) ($row['id_config'] ?? 0);
                    if ($idConfig <= 0) {
                        continue;
                    }
                    if (!isset($stepsByConfig[$idConfig])) {
                        $stepsByConfig[$idConfig] = [];
                    }
                    $stepsByConfig[$idConfig][] = $row;
                }
            }
        }

        foreach ($configs as &$cfg) {
            $cfg['steps'] = $stepsByConfig[(int) ($cfg['id_config'] ?? 0)] ?? [];
        }
        unset($cfg);

        // Features et attribute groups disponibles
        $features = $db->executeS(
            'SELECT f.id_feature, fl.name
             FROM ' . _DB_PREFIX_ . 'feature f
             LEFT JOIN ' . _DB_PREFIX_ . 'feature_lang fl
               ON fl.id_feature = f.id_feature AND fl.id_lang = ' . (int) $this->context->language->id . '
             ORDER BY fl.name ASC'
        );

        $attributeGroups = $db->executeS(
            'SELECT ag.id_attribute_group, agl.name
             FROM ' . _DB_PREFIX_ . 'attribute_group ag
             LEFT JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl
               ON agl.id_attribute_group = ag.id_attribute_group AND agl.id_lang = ' . (int) $this->context->language->id . '
             ORDER BY agl.name ASC'
        );

        // Plus de liste déroulante complète : catégories récupérées via AJAX (voir controllers/front/categorysearch.php)
        $categorySearchUrl = $this->context->link->getModuleLink($this->name, 'categorysearch', [], true);

        $editIdCategory = (int) Tools::getValue('edit_id_category');
        $baseUrl        = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');

        // Config en cours d'édition (pour pré-remplir le formulaire)
        $editConfig = null;
        if ($editIdCategory > 0) {
            foreach ($configs as $c) {
                if ((int) $c['id_category'] === $editIdCategory) {
                    $editConfig = $c;
                    break;
                }
            }
        }

        $this->context->smarty->assign([
            'wizard_configs'       => $configs,
            'wizard_edit_config'  => $editConfig,
            'wizard_features'     => $features,
            'wizard_attr_groups'  => $attributeGroups,
            'wizard_form_action'  => $baseUrl,
            'wizard_edit_id_cat'  => $editIdCategory,
            'wizard_list_url'     => $baseUrl,
            'wizard_category_search_url' => $categorySearchUrl,
            'wizard_category_search_token' => Tools::getAdminTokenLite('AdminModules'),
            'wizard_upload_image_url'    => $this->context->link->getModuleLink($this->name, 'uploadimage', [], true),
            'wizard_upload_image_token'  => Tools::getAdminTokenLite('AdminModules'),
            'module_dir'          => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }

    /* ------------------------------------------------------------------ */
    /* HOOKS FRONT-OFFICE                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Charge les assets CSS et JS sur les pages catégorie uniquement.
     */
    public function hookDisplayHeader(array $params): void
    {
        if ('category' !== $this->context->controller->php_self) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'dtc-wizard-css',
            'modules/' . $this->name . '/views/css/wizard.css',
            ['media' => 'all', 'priority' => 200]
        );

        $this->context->controller->registerJavascript(
            'dtc-wizard-js',
            'modules/' . $this->name . '/views/js/wizard.js',
            ['position' => 'bottom', 'priority' => 200]
        );
    }

    /**
     * Hook PrestaShop standard (catégories sans Creative Elements).
     */
    public function hookDisplayHeaderCategory(array $params): string
    {
        $idCategory = $this->getCategoryIdFromContext($params['category'] ?? null);

        return $this->renderWizard($idCategory);
    }

    /**
     * Hook Creative Elements — appelé directement sur l'instance du module
     * quand le champ "Hook" du widget ps-widget-module est vide.
     */
    public function hookDisplayCEWidget(array $params): string
    {
        // CE injecte 'id' dans le contexte Smarty pour le document courant,
        // dont l'id est au format {id_category}040101
        $ceDocId    = '';
        $smartyVars = $this->context->smarty->getTemplateVars();

        // Tente de récupérer la catégorie depuis le contexte Smarty
        $idCategory = 0;
        if (!empty($smartyVars['category'])) {
            $idCategory = $this->getCategoryIdFromContext($smartyVars['category']);
        }

        // Fallback : id_category passé en paramètre ou en GET
        if (!$idCategory) {
            $idCategory = (int) Tools::getValue('id_category');
        }

        return $this->renderWizard($idCategory);
    }

    /**
     * Récupère l'id catégorie depuis une variable Smarty.
     * Selon l'exécution (hook standard vs CE), `category` peut être un objet (Category)
     * ou un tableau (ex: ['id' => 215]).
     */
    private function getCategoryIdFromContext($category): int
    {
        if (is_object($category) && isset($category->id)) {
            return (int) $category->id;
        }

        if (is_array($category)) {
            if (isset($category['id'])) {
                return (int) $category['id'];
            }
            if (isset($category['id_category'])) {
                return (int) $category['id_category'];
            }
        }

        return 0;
    }

    /* ------------------------------------------------------------------ */
    /* RENDU                                                                 */
    /* ------------------------------------------------------------------ */

    private function renderWizard(int $idCategory): string
    {
        if (!$idCategory) {
            return '';
        }

        $idShop = (int) $this->context->shop->id;
        $db     = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $this->ensureSchema();

        // Récupère la config pour cette catégorie
        $config = $db->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . self::TABLE_CONFIG .
            ' WHERE id_category = ' . $idCategory . ' AND id_shop = ' . $idShop . ' AND active = 1'
        );

        if (!$config) {
            return '';
        }

        // Étapes avec leurs valeurs (features ou attributs)
        $steps = $db->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . self::TABLE_STEPS .
            ' WHERE id_config = ' . (int) $config['id_config'] . ' ORDER BY position ASC'
        );

        if (empty($steps)) {
            return '';
        }

        // Si le wizard a déjà été rendu dans la même requête (autre hook / TPL),
        // on ne le redessine pas pour éviter les doublons.
        if (self::$renderedOnceThisRequest) {
            return '';
        }
        self::$renderedOnceThisRequest = true;

        foreach ($steps as &$step) {
            $step['options'] = $this->getStepOptions($step);
        }
        unset($step);

        // URL de base pour le filtre (compatible ps_facetedsearch)
        $categoryLink = $this->context->link->getCategoryLink($idCategory);

        $this->context->smarty->assign([
            'dtc_wizard_config'   => $config,
            'dtc_wizard_steps'    => $steps,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/wizard.tpl');
    }

    /**
     * Retourne les options disponibles pour une étape donnée,
     * en ne gardant que celles présentes dans les produits de la boutique.
     */
    private function getStepOptions(array $step): array
    {
        $db     = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $idLang = (int) $this->context->language->id;
        $customRaw = $step['custom_options_json'] ?? null;

        if (!empty($customRaw)) {
            $decoded = json_decode((string) $customRaw, true);
            if (is_array($decoded)) {
                $options = [];
                foreach ($decoded as $i => $opt) {
                    if (!is_array($opt)) {
                        continue;
                    }

                    $title = isset($opt['title']) ? trim((string) $opt['title']) : '';
                    if ($title === '') {
                        continue;
                    }

                    $productIdsRaw = $opt['product_ids'] ?? [];
                    if (is_string($productIdsRaw)) {
                        $productIdsRaw = preg_split('/\s*,\s*/', trim($productIdsRaw)) ?: [];
                    }

                    $productIds = [];
                    if (is_array($productIdsRaw)) {
                        foreach ($productIdsRaw as $id) {
                            $idInt = (int) $id;
                            if ($idInt > 0) {
                                $productIds[$idInt] = $idInt; // unique
                            }
                        }
                    }
                    $productIdsCsv = implode(',', array_values($productIds));

                    $options[] = [
                        'mode'          => 'custom',
                        'option_index' => (int) $i,
                        'label'         => $title,
                        'image_url'    => isset($opt['image_url']) && is_string($opt['image_url']) ? trim($opt['image_url']) : null,
                        'product_ids_csv' => $productIdsCsv,
                    ];
                }

                if (count($options) > 0) {
                    return $options;
                }
            }
        }

        $allowedCsv = $step['allowed_value_ids'] ?? null;
        $allowedIds = [];

        if (!empty($allowedCsv)) {
            foreach (explode(',', (string) $allowedCsv) as $p) {
                $id = (int) trim($p);
                if ($id > 0) {
                    $allowedIds[$id] = $id; // unique
                }
            }
        }

        if ($step['source_type'] === 'feature') {
            $in = empty($allowedIds) ? '' : ' AND fvl.id_feature_value IN (' . implode(',', array_values($allowedIds)) . ')';
            $rows = $db->executeS(
                'SELECT DISTINCT fvl.id_feature_value as id, fvl.value as label
                 FROM ' . _DB_PREFIX_ . 'feature_product fp
                 INNER JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl
                   ON fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = ' . $idLang . '
                 WHERE fp.id_feature = ' . (int) $step['id_source'] . '
                 ' . $in . '
                 ORDER BY fvl.value ASC'
            );

            foreach ($rows as &$r) {
                $r['mode'] = 'native';
            }

            return $rows;
        }

        // Attributs
        $in = empty($allowedIds) ? '' : ' AND a.id_attribute IN (' . implode(',', array_values($allowedIds)) . ')';
        $rows = $db->executeS(
            'SELECT DISTINCT a.id_attribute as id, al.name as label
             FROM ' . _DB_PREFIX_ . 'attribute a
             INNER JOIN ' . _DB_PREFIX_ . 'attribute_lang al
               ON al.id_attribute = a.id_attribute AND al.id_lang = ' . $idLang . '
             WHERE a.id_attribute_group = ' . (int) $step['id_source'] . '
             ' . $in . '
             ORDER BY al.name ASC'
        );

        foreach ($rows as &$r) {
            $r['mode'] = 'native';
        }

        return $rows;
    }
}
