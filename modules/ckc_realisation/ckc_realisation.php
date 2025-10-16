<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once __DIR__ . '/classes/Realisation.php';

class Ckc_Realisation extends Module
{
    public function __construct()
    {
        $this->name = 'ckc_realisation';
        $this->tab = 'front_office_features';
        $this->version = '1.0.2';
        $this->author = 'Vianney';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        $this->controllers = ['myrealisation', 'myrealisationadd', 'myrealisationdelete'];

        parent::__construct();
        $this->displayName = $this->l('CKC Réalisations');
        $this->description = $this->l('Upload et gestion de photos de réalisations par magasin (gestion dans la page de configuration du module).');
    }

    public function install()
    {
        return parent::install()
//               && $this->installSql()
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('actionAdminControllerSetMedia') // <—
            && $this->registerHook('displayCustomerAccountNavigation')
            && $this->ensureUploadFolders();
    }

    public function uninstall()
    {
        // On tente le nettoyage SQL sans bloquer la désinstall si erreur
//        try { $this->uninstallSql(); } catch (\Throwable $e) {}
        return parent::uninstall();
    }

    public function hookActionAdminControllerSetMedia($params)
    {
//        dump("test"); 
        if (Tools::getValue('controller') === 'AdminModules'
            && Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        }
    }

    public function storageDir(int $idShop): string
    {
        return _PS_MODULE_DIR_ . $this->name . '/uploads/' . (int)$idShop . '/';
    }

    // Charge la CSS uniquement sur la page “Configurer” de ce module
    public function hookDisplayBackOfficeHeader($params)
    {


        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        }
    }

    protected function installSql(): bool
    {
        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $e = _MYSQL_ENGINE_;
        $ok = true;

        $ok = $ok && $db->execute("
  CREATE TABLE IF NOT EXISTS `{$p}ckc_realisation` (
    `id_realisation` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_shop` INT UNSIGNED NOT NULL,
    `id_customer` INT UNSIGNED NULL,
    `customer_type` ENUM('pro','proticulier') NULL,
    `id_product` INT UNSIGNED NULL,
    `filename` VARCHAR(255) NOT NULL,
    `mime` VARCHAR(64) NOT NULL,
    `filesize` INT UNSIGNED NOT NULL DEFAULT 0,
    `keywords` VARCHAR(512) NULL,
    `source` ENUM('admin','client') NOT NULL DEFAULT 'client',
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `date_add` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_upd` DATETIME NULL,
    INDEX (`id_shop`),
    INDEX (`id_customer`),
    INDEX (`customer_type`),
    INDEX (`id_product`),
    INDEX (`status`)
  ) ENGINE={$e} DEFAULT CHARSET=utf8mb4
");

        $ok = $ok && $db->execute("
            CREATE TABLE IF NOT EXISTS `{$p}ckc_realisation_lang` (
              `id_realisation` INT UNSIGNED NOT NULL,
              `id_lang` INT UNSIGNED NOT NULL,
              `title` VARCHAR(255) NULL,
              `alt` VARCHAR(255) NULL,
              PRIMARY KEY (`id_realisation`,`id_lang`)
            ) ENGINE={$e} DEFAULT CHARSET=utf8mb4
        ");

        return $ok;
    }

    protected function uninstallSql(): bool
    {
        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $ok = true;

        try {
            $db->execute('SET FOREIGN_KEY_CHECKS=0');
        } catch (\Throwable $e) {
        }

        foreach (["{$p}ckc_realisation_lang", "{$p}ckc_realisation"] as $tbl) {
            try {
                $ok = $ok && $db->execute('DROP TABLE IF EXISTS `' . $tbl . '`');
            } catch (\Throwable $e) {
                $ok = false;
            }
        }

        try {
            $db->execute('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Throwable $e) {
        }
        return $ok;
    }

    protected function ensureUploadFolders(): bool
    {
        $ok = true;
        foreach (Shop::getShops(false, null, true) as $idShop) {
            $path = _PS_MODULE_DIR_ . $this->name . '/uploads/' . $idShop . '/';
            if (!is_dir($path)) {
                $ok = $ok && @mkdir($path, 0755, true);
            }
            @file_put_contents($path . 'index.php', "<?php\n");
        }
        return $ok;
    }

    /* =========================
     * Hooks FO (Lien "Mes réalisations")
     * ========================= */

    private function ajaxSearchProducts(int $idShop, int $idLang, string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $limit = 20;

        $conds = [];
        if (ctype_digit($q)) {
            $id = (int)$q;
            $conds[] = "p.id_product = {$id}";
        }
        $sq = pSQL($q, true);
        $like = "'%{$sq}%'";
        $conds[] = "pl.name LIKE {$like}";
        $conds[] = "p.reference LIKE {$like}";

        $where = implode(' OR ', $conds);

        $sql = "
        SELECT p.id_product, pl.name, p.reference
        FROM {$p}product p
        INNER JOIN {$p}product_shop ps ON (ps.id_product = p.id_product AND ps.id_shop = " . (int)$idShop . ")
        INNER JOIN {$p}product_lang pl ON (pl.id_product = p.id_product AND pl.id_lang = " . (int)$idLang . ")
        WHERE ({$where})
        ORDER BY (p.id_product = " . (ctype_digit($q) ? (int)$q : 0) . ") DESC, p.id_product DESC
        LIMIT {$limit}
    ";

        $rows = $db->executeS($sql) ?: [];

        return array_map(static function ($r) {
            return [
                'id_product' => (int)$r['id_product'],
                'name' => $r['name'],
                'reference' => (string)$r['reference'],
                'label' => trim($r['id_product'] . ' - ' . $r['name'] . ($r['reference'] ? ' [' . $r['reference'] . ']' : '')),
            ];
        }, $rows);
    }


    public function getContent()
    {
        $idShop = (int)$this->context->shop->id;
        $idLang = (int)$this->context->language->id;
        $token = Tools::getAdminTokenLite('AdminModules');

        if (Tools::getValue('ajax') === '1' && Tools::getValue('action') === 'ckc_search_product') {
            if ($token !== Tools::getValue('token')) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                die('{"error":"bad token"}');
            }
            header('Content-Type: application/json; charset=utf-8');
            $q = (string)Tools::getValue('q', '');
            die(Tools::jsonEncode($this->ajaxSearchProducts($idShop, $idLang, $q), JSON_UNESCAPED_UNICODE));
        }

        $prefix = _DB_PREFIX_;

        $products = Db::getInstance()->executeS("
  SELECT p.id_product, pl.name, p.reference
  FROM {$prefix}product p
  INNER JOIN {$prefix}product_shop ps
    ON ps.id_product = p.id_product AND ps.id_shop = {$idShop}
  INNER JOIN {$prefix}product_lang pl
    ON pl.id_product = p.id_product AND pl.id_lang = {$idLang}
  WHERE ps.active = 1
  
    GROUP BY p.id_product
  ORDER BY pl.name ASC

");
        $errors = [];
        $conf = [];

        $baseUrl = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&module_name=' . $this->name . '&token=' . $token;

        if (Tools::isSubmit('ckc_add') || Tools::isSubmit('ckc_update')) {
            if ($token !== Tools::getValue('token')) {
                $errors[] = $this->l('Token invalide');
            } else {
                $isUpdate = (bool)Tools::isSubmit('ckc_update');
                $id = (int)Tools::getValue('id_realisation');
                $res = $this->handleSave($isUpdate ? $id : null, $idShop);
                if (!empty($res['errors'])) {
                    $errors = array_merge($errors, $res['errors']);
                } elseif ($res['ok']) {
                    $conf[] = $isUpdate ? $this->l('Réalisation mise à jour.') : $this->l('Réalisation créée.');
                }
            }
        }

        if ($action = Tools::getValue('ckc_action')) {
            if ($token !== Tools::getValue('token')) {
                $errors[] = $this->l('Token invalide');
            } else {
                $id = (int)Tools::getValue('id_realisation');
                if ($action === 'delete') {
                    $res = $this->handleDelete($id);
                    if ($res['ok']) {
                        $conf[] = $this->l('Réalisation supprimée.');
                    } else {
                        $errors[] = $res['error'] ?? $this->l('Suppression impossible');
                    }
                } elseif (in_array($action, ['approve', 'reject', 'pending'])) {
                    $newStatus = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'pending');
                    $res = $this->handleStatus($id, $newStatus);
                    if ($res['ok']) {
                        $conf[] = $this->l('Statut mis à jour.');
                    } else {
                        $errors[] = $res['error'] ?? $this->l('Mise à jour impossible');
                    }
                }
            }
        }

        foreach ($errors as $e) {
            echo $this->displayError($e);
        }
        foreach ($conf as $c) {
            echo $this->displayConfirmation($c);
        }

        $editing = false;
        $editRow = null;
        if (Tools::getValue('ckc_action') === 'edit' && Tools::getValue('id_realisation')) {
            $editing = true;
            $editRow = $this->fetchOne((int)Tools::getValue('id_realisation'), $idLang);
            if (!$editRow) {
                $editing = false;
                echo $this->displayError($this->l('Élément introuvable.'));
            }
        }
        $f_status = (string)Tools::getValue('f_status', '');

        $page = max(1, (int)Tools::getValue('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $rows = $this->fetchList($idShop, $idLang, $f_status, $limit, $offset, $countAll);
        $stores = $this->fetchStoresForShop($idShop);

        $row_langs = [];
        if ($editing && $editRow) {
            $raw = Db::getInstance()->executeS('
                SELECT id_lang, title, alt
                FROM ' . _DB_PREFIX_ . 'ckc_realisation_lang
                WHERE id_realisation=' . (int)$editRow['id_realisation']
            );
            foreach ($raw as $lr) {
                $row_langs[(int)$lr['id_lang']] = ['title' => $lr['title'], 'alt' => $lr['alt']];
            }
        }

        $languages = Language::getLanguages(false);
        $statuses = ['pending' => $this->l('En attente'), 'approved' => $this->l('Approuvée'), 'rejected' => $this->l('Rejetée')];
        $pages = max(1, (int)ceil($countAll / $limit));
        $shop_id = $idShop;
        $file_base_url = Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/uploads/' . $shop_id . '/';

        $types = ['pro' => $this->l('Professionnel'), 'proticulier' => $this->l('Proticulier')];
        $remote = $this->context->link->getAdminLink('AdminProducts', true, [], [
            'ajax' => 1,
            'action' => 'productsList',
            'forceJson' => 1,
            'disableCombination' => 1,
            'exclude_packs' => 0,
            'excludeVirtuals' => 0,
            'limit' => 20,
        ]);

        $this->context->smarty->assign([
            'products' => $products ?: [],
            'products_json' => json_encode($products ?: [], JSON_UNESCAPED_UNICODE),
            'product_remote_url' => $remote . '&q=',
            'types' => $types,
            'f_type' => (string)Tools::getValue('f_type', ''),
            'module_name' => $this->name,
            'token' => $token,
            'base_url' => $baseUrl,
            'module_dir' => $this->_path,
            'shop_id' => $shop_id,
            'languages' => $languages,
            'statuses' => $statuses,
            'file_base_url' => $file_base_url,
            'editing' => $editing,
            'row' => $editRow,
            'row_langs' => $row_langs,
            'stores' => $stores,
            'rows' => $rows,
            'count_all' => (int)$countAll,
            'page' => (int)$page,
            'pages' => (int)$pages,
            'limit' => (int)$limit,
            'f_status' => (string)$f_status,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }


    private function sqlWhere(array $parts): string
    {
        $parts = array_values(array_filter($parts, static function ($v) {
            return $v !== '' && $v !== null && $v !== false;
        }));
        return $parts ? ' WHERE ' . implode(' AND ', $parts) : '';
    }

    protected function productExistsOnShop($idProduct, $idShop)
    {
        $idProduct = (int)$idProduct;
        if ($idProduct <= 0) {
            return false;
        }

        $q = new DbQuery();
        $q->select('1');

        if (Shop::isFeatureActive() && (int)$idShop > 0) {
            $q->from('product_shop', 'ps');
            $q->where('ps.id_product = ' . (int)$idProduct);
            $q->where('ps.id_shop = ' . (int)$idShop);
        } else {
            $q->from('product', 'p');
            $q->where('p.id_product = ' . (int)$idProduct);
        }

        return (bool)Db::getInstance()->getValue($q);
    }

    protected function handleSave($id, $idShop)
    {
        $errors = array();


        $status = (string)Tools::getValue('status', 'pending');
        $customer_type = (string)Tools::getValue('customer_type', ''); // 'pro' | 'proticulier' | ''
        $id_product = (int)Tools::getValue('id_product', 0);
        $keywords = trim((string)Tools::getValue('keywords', ''));
        $allowedStatus = array('pending', 'approved', 'rejected');
        $allowedTypes  = array('pro', 'proticulier');

        $customer_type = trim(Tools::strtolower($customer_type));
        if ($customer_type === 'particulier' || $customer_type === 'particuliers') {
            $customer_type = 'proticulier';
        }
        if ($customer_type === 'professionnel' || $customer_type === 'professionnelle' || $customer_type === 'professionnels') {
            $customer_type = 'pro';
        }

        if (!in_array($status, $allowedStatus, true)) {
            $errors[] = $this->l('Statut invalide');
        }
        if ($customer_type !== '' && !in_array($customer_type, $allowedTypes, true)) {
            $errors[] = $this->l('Type client invalide');
        }


        $obj = $id ? new Realisation((int)$id) : new Realisation();
        if ($id && !Validate::isLoadedObject($obj)) {
            return array('ok' => false, 'errors' => array($this->l('Elément introuvable.')));
        }

        $newFilename = null;
        $mime = null;
        $filesize = 0;

        if (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];

            if ((int)$file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = $this->l('Erreur upload');
            } else {
                if (function_exists('finfo_open')) {
                    $f = finfo_open(FILEINFO_MIME_TYPE);
                    if ($f) {
                        $mime = finfo_file($f, $file['tmp_name']);
                        finfo_close($f);
                    }
                }
                if (!$mime && function_exists('mime_content_type')) {
                    $mime = @mime_content_type($file['tmp_name']);
                }

                if (!in_array($mime, array('image/jpeg', 'image/png'), true)) {
                    $errors[] = $this->l('Format non supporte (JPEG/PNG uniquement)');
                }
                if ((int)$file['size'] > 5 * 1024 * 1024) {
                    $errors[] = $this->l('Fichier trop volumineux (>5MB)');
                }

                if (empty($errors)) {
                    $dir = $this->storageDir((int)$idShop);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }

                    $newFilename = uniqid('real_', true) . ($mime === 'image/png' ? '.png' : '.jpg');
                    if (!@move_uploaded_file($file['tmp_name'], $dir . $newFilename)) {
                        $errors[] = $this->l('Echec de l\'upload');
                    } else {
                        $ext = Tools::strtolower(pathinfo($newFilename, PATHINFO_EXTENSION)); // jpg|png
                        ImageManager::resize($dir . $newFilename, $dir . 'thumb_' . $newFilename, 360, 0, $ext, false);
                        $filesize = (int)$file['size'];
                    }
                }
            }
        } elseif (!$id) {
            $errors[] = $this->l('Fichier requis');
        }

        if (!empty($errors)) {
            return array('ok' => false, 'errors' => $errors);
        }

        $obj->id_shop = (int)$idShop;
        $obj->status = $status;
        $obj->customer_type = ($customer_type !== '') ? $customer_type : null;
        $obj->id_product = $id_product ?: null;
        $obj->keywords = ($keywords !== '') ? $keywords : null;

        if (!$id) {
            $obj->source = 'admin';
            $obj->id_customer = null;
        }

        if ($newFilename) {
            if ($id && !empty($obj->filename)) {
                @unlink($this->storageDir((int)$idShop) . $obj->filename);
                @unlink($this->storageDir((int)$idShop) . 'thumb_' . $obj->filename);
            }
            $obj->filename = $newFilename;
            $obj->mime = $mime ? $mime : '';
            $obj->filesize = $filesize;
        }

        $lid = (int)$this->context->language->id;
        $obj->title[$lid] = (string)Tools::getValue('title_' . $lid, '');
        $obj->alt[$lid] = (string)Tools::getValue('alt_' . $lid, '');

        $ok = $id ? (bool)$obj->update() : (bool)$obj->add();
        return array('ok' => $ok, 'errors' => $ok ? array() : array($this->l('Echec de l\'enregistrement')));
    }

    protected function handleDelete(int $id): array
    {
        $obj = new Realisation($id);
        if (!Validate::isLoadedObject($obj)) {
            return ['ok' => false, 'error' => $this->l('Introuvable')];
        }
        if (!empty($obj->filename)) {
            $dir = $this->storageDir((int)$obj->id_shop);
            @unlink($dir . $obj->filename);
            @unlink($dir . 'thumb_' . $obj->filename);
        }
        return ['ok' => $obj->delete()];
    }

    protected function handleStatus(int $id, string $status): array
    {
        $obj = new Realisation($id);
        if (!Validate::isLoadedObject($obj)) {
            return ['ok' => false, 'error' => $this->l('Introuvable')];
        }
        $obj->status = $status;
        return ['ok' => $obj->update()];
    }

    protected function fetchList(int $idShop, int $idLang, string $status, int $limit, int $offset, &$countAll)
    {
        $where = ' WHERE r.id_shop=' . (int)$idShop . ' ';
        if ($status !== '') {
            $where .= " AND r.status='" . pSQL($status) . "' ";
        }
        $f_type = (string)Tools::getValue('f_type', '');
        if ($f_type !== '') {
            $where .= " AND r.customer_type='" . pSQL($f_type) . "' ";
        }

        $countAll = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'ckc_realisation r ' . $where
        );

        $sql = '
      SELECT r.*,
             rl.title, rl.alt,
             CASE
               WHEN r.source="admin" OR r.id_customer IS NULL OR r.id_customer=0
                 THEN "ADMIN"
               ELSE COALESCE(NULLIF(TRIM(CONCAT(IFNULL(c.firstname,""), " ", IFNULL(c.lastname,""))), ""), c.email, CONCAT("Client #", r.id_customer))
             END AS customer_name,
             pl.name AS product_name
      FROM ' . _DB_PREFIX_ . 'ckc_realisation r
      LEFT JOIN ' . _DB_PREFIX_ . 'ckc_realisation_lang rl
        ON (rl.id_realisation=r.id_realisation AND rl.id_lang=' . (int)$idLang . ')
      LEFT JOIN ' . _DB_PREFIX_ . 'customer c
        ON (c.id_customer=r.id_customer)
      LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl
        ON (pl.id_product=r.id_product AND pl.id_lang=' . (int)$idLang . ' AND pl.id_shop=' . (int)$idShop . ')
      ' . $where . '
      ORDER BY r.date_add DESC
      LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        return Db::getInstance()->executeS($sql) ?: [];
    }


    protected function fetchOne(int $id, int $idLang)
    {
        $sql = '
      SELECT r.*,
             rl.title, rl.alt,
             pl.name AS product_name,
             p.reference
      FROM ' . _DB_PREFIX_ . 'ckc_realisation r
      LEFT JOIN ' . _DB_PREFIX_ . 'ckc_realisation_lang rl
        ON (rl.id_realisation = r.id_realisation AND rl.id_lang = ' . (int)$idLang . ')
      LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl
        ON (pl.id_product = r.id_product AND pl.id_lang = ' . (int)$idLang . ')
      LEFT JOIN ' . _DB_PREFIX_ . 'product p
        ON (p.id_product = r.id_product)
      WHERE r.id_realisation = ' . (int)$id;

        return Db::getInstance()->getRow($sql) ?: null;
    }


    private function getProGroupId(): int
    {
        $v = (int)Configuration::get('CKC_GROUP_PRO_ID');
        return $v > 0 ? $v : 10;
    }

    private function getProticulierGroupId(): int
    {
        $v = (int)Configuration::get('CKC_GROUP_PROTICULIER_ID');
        return $v > 0 ? $v : 15;
    }

    public function detectCustomerType(int $idCustomer): ?string
    {
        if ($idCustomer <= 0) {
            return null;
        }
        $p   = _DB_PREFIX_;
        $pro = (int)$this->getProGroupId();
        $part= (int)$this->getProticulierGroupId();

        $rows   = Db::getInstance()->executeS("SELECT id_group FROM {$p}customer_group WHERE id_customer=".(int)$idCustomer);
        $groups = array_map('intval', array_column($rows ?: [], 'id_group'));

        if (in_array($pro, $groups, true))  return 'pro';
        if (in_array($part, $groups, true)) return 'proticulier';
        return null;
    }


    protected function fetchStoresForShop(int $idShop): array
    {
        $p = _DB_PREFIX_;
        $idLang = (int)$this->context->language->id;

        $sql = "
      SELECT s.id_store,
             COALESCE(sl.name, CONCAT('Store #', s.id_store)) AS name
      FROM `{$p}store` s
      LEFT JOIN `{$p}store_lang` sl
        ON sl.id_store = s.id_store AND sl.id_lang = {$idLang}
      ORDER BY name ASC
    ";

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function hookDisplayCustomerAccount() {
        $url = $this->context->link->getModuleLink($this->name, 'myrealisation');
        return '<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" href="'.htmlspecialchars($url).'">'.$this->l('Mes réalisations').'</a>';
    }
    public function hookDisplayCustomerAccountNavigation() {
        $url = $this->context->link->getModuleLink($this->name, 'myrealisation');
        return '<li><a href="'.htmlspecialchars($url).'">'.$this->l('Mes réalisations').'</a></li>';
    }

}
