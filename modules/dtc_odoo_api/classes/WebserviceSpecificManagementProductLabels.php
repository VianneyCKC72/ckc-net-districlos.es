<?php
/**
 * Endpoint webservice : étiquettes produit (CKC module ckc_label).
 *
 * Modélisation :
 * - ps_ckc_labels : définition de l'étiquette
 * - ps_ckc_labels_product : association label <-> produit (apply_to=products)
 * - ps_ckc_labels_category : association label <-> catégorie (apply_to=categories)
 *
 * GET  /api/product_labels?id_label=X
 * GET  /api/product_labels?id_product=X
 * POST /api/product_labels  → création (id_label optionnel)
 * PUT  /api/product_labels  → modification (id_label requis)
 * DELETE /api/product_labels?id_label=X
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementProductLabels extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'product_label';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }

        if ($method === 'POST') {
            $this->payload = $this->handleWrite('POST');

            return;
        }

        if ($method === 'PUT') {
            $this->payload = $this->handleWrite('PUT');

            return;
        }

        if ($method === 'DELETE') {
            $this->payload = $this->handleDelete();

            return;
        }

        $this->methodNotAllowed('product_labels', 900);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $idLabel = $this->resolveInt($input, ['id_label', 'id']);
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);

        if ($idLabel <= 0 && $idProduct <= 0) {
            return [
                'action' => 'help',
                'description' => 'CRUD of CKC labels (ckc_label): ps_ckc_labels + associations',
                'methods' => [
                    'GET' => 'Get a label by id_label or labels applicable to a product by id_product',
                    'POST' => 'Create label + associations',
                    'PUT' => 'Update label + associations',
                    'DELETE' => 'Delete label + associations',
                ],
                'fields' => [
                    'id_label' => 'Label id (GET/PUT/DELETE)',
                    'id_product' => 'Product id (GET to return applicable labels)',
                    'color' => 'Text color (#RRGGBB)',
                    'background_color' => 'Background color (#RRGGBB)',
                    'text' => 'Label text',
                    'show_discount_percent' => '1/0',
                    'start_date' => 'Datetime or null',
                    'end_date' => 'Datetime or null',
                    'apply_to' => 'all|products|categories',
                    'active' => '1/0',
                    'product_ids' => 'Array of id_product (apply_to=products)',
                    'category_ids' => 'Array of id_category (apply_to=categories)',
                ],
                'example_get_label' => '/api/product_labels?id_label=12',
                'example_get_product' => '/api/product_labels?id_product=35',
                'example_post_json' => '{"color":"#FFFFFF","background_color":"#FF0000","text":"PROMO","show_discount_percent":1,"start_date":"2026-08-01 00:00:00","end_date":"2026-09-01 00:00:00","apply_to":"products","active":1,"product_ids":[35,42]}',
            ];
        }

        if ($idLabel > 0) {
            $this->assertRowExists('ckc_labels', 'id_label', $idLabel, 'Label', 910);

            return $this->loadLabelWithAssociations($idLabel);
        }

        // id_product query: return labels applicable "now" (same spirit than module hook),
        // without env_pro logic (no logged customer context via webservice).
        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 911);

        $labels = $this->loadApplicableLabelsForProduct($idProduct);

        return [
            'action' => 'list',
            'id_product' => $idProduct,
            'count' => count($labels),
            'labels' => $labels,
        ];
    }

    /**
     * @param string $httpMethod 'POST' or 'PUT'
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleWrite($httpMethod)
    {
        $input = $this->getParams();
        $idLabel = $this->resolveInt($input, ['id_label', 'id']);

        $isPut = Tools::strtoupper($httpMethod) === 'PUT';
        if ($isPut && $idLabel <= 0) {
            throw new WebserviceException('id_label is required for PUT', [920, 400]);
        }

        if (!$isPut && $idLabel <= 0 && !array_key_exists('color', $input)) {
            throw new WebserviceException('Label fields are required', [921, 400]);
        }

        $applyTo = Tools::strtolower((string) ($input['apply_to'] ?? ''));
        $active = (int) ($input['active'] ?? 1);
        $showDiscountPercent = (int) ($input['show_discount_percent'] ?? 1);

        $color = (string) ($input['color'] ?? '');
        $backgroundColor = (string) ($input['background_color'] ?? '');
        $text = (string) ($input['text'] ?? '');

        if ($color === '' || $backgroundColor === '' || $text === '' || $applyTo === '') {
            throw new WebserviceException('color, background_color, text and apply_to are required', [922, 400]);
        }

        if (!in_array($applyTo, ['all', 'products', 'categories'], true)) {
            throw new WebserviceException('Invalid apply_to (expected all|products|categories)', [923, 400]);
        }

        $startDate = null;
        if (array_key_exists('start_date', $input)) {
            $startDate = trim((string) $input['start_date']);
            if ($startDate === '') {
                $startDate = null;
            }
        }
        $endDate = null;
        if (array_key_exists('end_date', $input)) {
            $endDate = trim((string) $input['end_date']);
            if ($endDate === '') {
                $endDate = null;
            }
        }

        $productIds = [];
        if ($applyTo === 'products') {
            $productIdsRaw = $input['product_ids'] ?? [];
            $productIds = $this->normalizePossiblyCsvIdList($productIdsRaw);
            if (empty($productIds)) {
                throw new WebserviceException('product_ids is required when apply_to=products', [924, 400]);
            }
            foreach ($productIds as $pid) {
                $this->assertRowExists('product', 'id_product', $pid, 'Product', 925);
            }
        }

        $categoryIds = [];
        if ($applyTo === 'categories') {
            $categoryIdsRaw = $input['category_ids'] ?? [];
            $categoryIds = $this->normalizePossiblyCsvIdList($categoryIdsRaw);
            if (empty($categoryIds)) {
                throw new WebserviceException('category_ids is required when apply_to=categories', [926, 400]);
            }
            foreach ($categoryIds as $cid) {
                $this->assertRowExists('category', 'id_category', $cid, 'Category', 927);
            }
        }

        $db = Db::getInstance();

        $labelExists = $idLabel > 0 && $this->rowExists('ckc_labels', 'id_label', $idLabel);

        $fields = [
            'color' => pSQL($color, true),
            'background_color' => pSQL($backgroundColor, true),
            'text' => pSQL($text, true),
            'show_discount_percent' => $showDiscountPercent,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'apply_to' => pSQL($applyTo, true),
            'active' => $active,
        ];

        if ($labelExists) {
            $db->update('ckc_labels', $fields, 'id_label = ' . (int) $idLabel);
            $idFinal = $idLabel;
        } else {
            if ($isPut) {
                throw new WebserviceException('Label ' . (int) $idLabel . ' does not exist', [928, 404]);
            }

            $insertData = $fields;
            if ($idLabel > 0) {
                $insertData['id_label'] = (int) $idLabel;
            }

            // null_values=true to store null datetime when unset
            $db->insert('ckc_labels', $insertData, true);
            $idFinal = (int) $db->getValue('SELECT LAST_INSERT_ID()');
        }

        // Replace associations
        $db->delete('ckc_labels_product', 'id_label = ' . (int) $idFinal);
        $db->delete('ckc_labels_category', 'id_label = ' . (int) $idFinal);

        if ($applyTo === 'products') {
            foreach ($productIds as $pid) {
                $db->insert('ckc_labels_product', [
                    'id_label' => (int) $idFinal,
                    'id_product' => (int) $pid,
                ]);
            }
        } elseif ($applyTo === 'categories') {
            foreach ($categoryIds as $cid) {
                $db->insert('ckc_labels_category', [
                    'id_label' => (int) $idFinal,
                    'id_category' => (int) $cid,
                ]);
            }
        }

        $this->clearCkcLabelCache();

        $label = $this->loadLabelWithAssociations($idFinal);
        $label['action'] = 'update';

        return $label;
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleDelete()
    {
        $input = $this->getParams();
        $idLabel = $this->resolveInt($input, ['id_label', 'id']);

        if ($idLabel <= 0) {
            throw new WebserviceException('id_label is required for DELETE', [930, 400]);
        }

        $this->assertRowExists('ckc_labels', 'id_label', $idLabel, 'Label', 931);

        $db = Db::getInstance();
        $db->delete('ckc_labels_product', 'id_label = ' . (int) $idLabel);
        $db->delete('ckc_labels_category', 'id_label = ' . (int) $idLabel);
        $db->delete('ckc_labels', 'id_label = ' . (int) $idLabel);

        $this->clearCkcLabelCache();

        return [
            'action' => 'delete',
            'status' => 'ok',
            'id_label' => $idLabel,
        ];
    }

    /**
     * @param int $idLabel
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function loadLabelWithAssociations($idLabel)
    {
        $row = Db::getInstance()->getRow(
            'SELECT id_label, color, background_color, text, show_discount_percent, start_date, end_date, apply_to, active
             FROM `' . _DB_PREFIX_ . 'ckc_labels`
             WHERE id_label = ' . (int) $idLabel
        );

        if (!$row) {
            throw new WebserviceException('Label ' . (int) $idLabel . ' does not exist', [932, 404]);
        }

        $productIds = [];
        $productRows = Db::getInstance()->executeS(
            'SELECT id_product FROM `' . _DB_PREFIX_ . 'ckc_labels_product` WHERE id_label = ' . (int) $idLabel
        );
        if ($productRows) {
            foreach ($productRows as $pr) {
                $productIds[] = (int) $pr['id_product'];
            }
        }

        $categoryIds = [];
        $categoryRows = Db::getInstance()->executeS(
            'SELECT id_category FROM `' . _DB_PREFIX_ . 'ckc_labels_category` WHERE id_label = ' . (int) $idLabel
        );
        if ($categoryRows) {
            foreach ($categoryRows as $cr) {
                $categoryIds[] = (int) $cr['id_category'];
            }
        }

        // Normalize ordering for deterministic responses
        sort($productIds);
        sort($categoryIds);

        return [
            'action' => 'list',
            'id_label' => (int) $row['id_label'],
            'color' => (string) $row['color'],
            'background_color' => (string) $row['background_color'],
            'text' => (string) $row['text'],
            'show_discount_percent' => (int) $row['show_discount_percent'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'apply_to' => (string) $row['apply_to'],
            'active' => (int) $row['active'],
            'product_ids' => $productIds,
            'category_ids' => $categoryIds,
        ];
    }

    /**
     * @param int $idProduct
     *
     * @return array
     */
    protected function loadApplicableLabelsForProduct($idProduct)
    {
        $currentDate = date('Y-m-d H:i:s');
        $idCategoryDefault = (int) Db::getInstance()->getValue(
            'SELECT id_category_default FROM `' . _DB_PREFIX_ . 'product` WHERE id_product = ' . (int) $idProduct
        );

        $sql = '
            SELECT DISTINCT l.*
            FROM `' . _DB_PREFIX_ . 'ckc_labels` l
            LEFT JOIN `' . _DB_PREFIX_ . 'ckc_labels_product` lp
                ON lp.id_label = l.id_label
            LEFT JOIN `' . _DB_PREFIX_ . 'ckc_labels_category` lc
                ON lc.id_label = l.id_label
            WHERE l.active = 1
              AND (
                    (l.apply_to = \"all\")
                 OR (l.apply_to = \"products\" AND lp.id_product = ' . (int) $idProduct . ')
                 OR (l.apply_to = \"categories\" AND lc.id_category = ' . (int) $idCategoryDefault . ')
              )
              AND (l.start_date IS NULL OR l.start_date <= \"' . pSQL($currentDate) . '\")
              AND (l.end_date IS NULL OR l.end_date >= \"' . pSQL($currentDate) . '\")
            ORDER BY l.id_label ASC
        ';

        $rows = Db::getInstance()->executeS($sql);
        if (!$rows) {
            return [];
        }

        $labels = [];
        foreach ($rows as $r) {
            $labels[] = [
                'id_label' => (int) $r['id_label'],
                'color' => (string) $r['color'],
                'background_color' => (string) $r['background_color'],
                'text' => (string) $r['text'],
                'show_discount_percent' => (int) $r['show_discount_percent'],
                'start_date' => $r['start_date'],
                'end_date' => $r['end_date'],
                'apply_to' => (string) $r['apply_to'],
                'active' => (int) $r['active'],
            ];
        }

        return $labels;
    }

    /**
     * @param mixed $raw
     *
     * @return int[]
     */
    protected function normalizePossiblyCsvIdList($raw)
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $parts = preg_split('/\\s*,\\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);
            return $this->normalizeIdList($parts);
        }

        return $this->normalizeIdList($raw);
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            [
                'id_label',
                'id',
                'id_product',
                'color',
                'background_color',
                'text',
                'show_discount_percent',
                'start_date',
                'end_date',
                'apply_to',
                'active',
                'product_ids',
                'category_ids',
            ],
            ['product_label', 'product_labels']
        );
    }

    /**
     * @return void
     */
    protected function clearCkcLabelCache()
    {
        try {
            $module = Module::getInstanceByName('ckc_label');
            if ($module && method_exists($module, 'clearCache')) {
                $module->clearCache();
            }
        } catch (Throwable $e) {
            // Best effort: cache clearing is not critical for data correctness.
        }
    }
}

