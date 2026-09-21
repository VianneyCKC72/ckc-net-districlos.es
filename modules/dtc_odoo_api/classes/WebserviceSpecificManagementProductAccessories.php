<?php
/**
 * Endpoint webservice : accessoires d'un produit (accessory_group_product).
 *
 * GET  /api/product_accessories?id_product=X
 * PUT  /api/product_accessories  → remplace toutes les liaisons du produit
 * POST /api/product_accessories  → ajoute une ou plusieurs liaisons
 *
 * Remise (HSMA) : stockée via cart_rule + accessory_product_cart_rule
 * (pas de colonne discount sur accessory_group_product).
 * Champs API : reduction_percent / reduction_amount
 * (écriture alternative : discount_type 0=%|1=montant + discount_value).
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementProductAccessories extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'product_accessory';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        if (!Module::isEnabled('hsmultiaccessoriespro')) {
            throw new WebserviceException('hsmultiaccessoriespro module is not enabled', [1500, 503]);
        }

        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }
        if ($method === 'PUT') {
            $this->payload = $this->handleReplace();

            return;
        }
        if ($method === 'POST') {
            $this->payload = $this->handleAdd();

            return;
        }

        $this->methodNotAllowed('product_accessories', 1501);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);

        if ($idProduct <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write HS Multi Accessories Pro product links (accessory_group_product)',
                'methods' => [
                    'GET' => 'List accessories for id_product',
                    'PUT' => 'Replace all accessories for id_product',
                    'POST' => 'Add accessories without removing existing ones',
                ],
                'fields' => [
                    'id_product' => 'Main product id',
                    'accessories' => '[{id_accessory_group, id_accessory, id_product_attribute, default_quantity, min_quantity, required, position, name, reduction_percent, reduction_amount}]',
                    'required' => '1 = mandatory accessory on product page',
                    'reduction_percent' => 'Percent discount on accessory (HSMA cart rule)',
                    'reduction_amount' => 'Amount discount on accessory (HSMA cart rule)',
                    'discount_type' => 'Write alternative: 0 = percent, 1 = amount (with discount_value)',
                    'discount_value' => 'Write alternative: value used with discount_type',
                ],
                'example_get' => '/api/product_accessories?id_product=35',
                'example_put_json' => '{"id_product":35,"accessories":[{"id_accessory_group":4,"id_accessory":88,"default_quantity":1,"required":0,"reduction_percent":10}]}',
            ];
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1502);
        $accessories = $this->loadAccessories($idProduct);

        return [
            'action' => 'list',
            'id_product' => $idProduct,
            'count' => count($accessories),
            'accessories' => $accessories,
        ];
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleReplace()
    {
        $input = $this->getParams();
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);
        if ($idProduct <= 0) {
            throw new WebserviceException('id_product is required', [1503, 400]);
        }
        if (!array_key_exists('accessories', $input)) {
            throw new WebserviceException('accessories array is required', [1504, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1502);
        $lines = $this->validateAccessoryList($input['accessories']);

        $this->deleteProductLinks($idProduct);
        foreach ($lines as $line) {
            $this->insertLink($idProduct, $line);
        }

        $accessories = $this->loadAccessories($idProduct);

        return [
            'action' => 'replace',
            'status' => 'ok',
            'id_product' => $idProduct,
            'count' => count($accessories),
            'accessories' => $accessories,
        ];
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleAdd()
    {
        $input = $this->getParams();
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);
        if ($idProduct <= 0) {
            throw new WebserviceException('id_product is required', [1503, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 1502);

        if (!empty($input['accessories'])) {
            $lines = $this->validateAccessoryList($input['accessories']);
        } else {
            $lines = [$this->validateAccessoryEntry($input)];
        }

        foreach ($lines as $line) {
            $this->insertLink($idProduct, $line);
        }

        $accessories = $this->loadAccessories($idProduct);

        return [
            'action' => 'add',
            'status' => 'ok',
            'id_product' => $idProduct,
            'added' => count($lines),
            'count' => count($accessories),
            'accessories' => $accessories,
        ];
    }

    /**
     * @param mixed $list
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function validateAccessoryList($list)
    {
        if (!is_array($list)) {
            throw new WebserviceException('accessories must be an array', [1505, 400]);
        }

        $out = [];
        foreach ($list as $i => $entry) {
            if (!is_array($entry)) {
                throw new WebserviceException('accessories[' . $i . '] must be an object', [1506, 400]);
            }
            $out[] = $this->validateAccessoryEntry($entry, $i);
        }

        return $out;
    }

    /**
     * @param array    $entry
     * @param int|null $index
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function validateAccessoryEntry(array $entry, $index = null)
    {
        $prefix = $index !== null ? 'accessories[' . $index . '].' : '';
        $idGroup = (int) ($entry['id_accessory_group'] ?? 0);
        $idAccessory = (int) ($entry['id_accessory'] ?? 0);

        if ($idGroup <= 0) {
            throw new WebserviceException($prefix . 'id_accessory_group is required', [1507, 400]);
        }
        if ($idAccessory <= 0) {
            throw new WebserviceException($prefix . 'id_accessory is required', [1508, 400]);
        }
        if (!$this->rowExists('accessory_group', 'id_accessory_group', $idGroup)) {
            throw new WebserviceException($prefix . 'Accessory group ' . $idGroup . ' does not exist', [1509, 404]);
        }
        $this->assertRowExists('product', 'id_product', $idAccessory, 'Accessory product', 1510);

        $discount = $this->parseDiscountFields($entry, $prefix);

        return [
            'id_accessory_group' => $idGroup,
            'id_accessory' => $idAccessory,
            'id_product_attribute' => (int) ($entry['id_product_attribute'] ?? 0),
            'default_quantity' => max(1, (int) ($entry['default_quantity'] ?? 1)),
            'min_quantity' => max(1, (int) ($entry['min_quantity'] ?? 1)),
            'required' => !empty($entry['required']) ? 1 : 0,
            'position' => (int) ($entry['position'] ?? 0),
            'name' => $entry['name'] ?? null,
            'reduction_percent' => $discount['reduction_percent'],
            'reduction_amount' => $discount['reduction_amount'],
        ];
    }

    /**
     * @param array  $entry
     * @param string $prefix
     *
     * @return array{reduction_percent: float, reduction_amount: float}
     *
     * @throws WebserviceException
     */
    protected function parseDiscountFields(array $entry, $prefix = '')
    {
        $hasPercent = array_key_exists('reduction_percent', $entry);
        $hasAmount = array_key_exists('reduction_amount', $entry);
        $hasLegacy = array_key_exists('discount_value', $entry) || array_key_exists('discount_type', $entry);

        $percent = 0.0;
        $amount = 0.0;

        if ($hasPercent || $hasAmount) {
            $percent = max(0.0, (float) ($entry['reduction_percent'] ?? 0));
            $amount = max(0.0, (float) ($entry['reduction_amount'] ?? 0));
        } elseif ($hasLegacy) {
            // Aligné BO HSMA : discount_type 0 = %, 1 = montant
            $discountType = (int) ($entry['discount_type'] ?? 0);
            $discountValue = max(0.0, (float) ($entry['discount_value'] ?? 0));
            if ($discountType === 1) {
                $amount = $discountValue;
            } else {
                $percent = $discountValue;
            }
        }

        if ($percent > 0 && $amount > 0) {
            throw new WebserviceException(
                $prefix . 'Use either reduction_percent or reduction_amount, not both',
                [1511, 400]
            );
        }
        if ($percent > 100) {
            throw new WebserviceException($prefix . 'reduction_percent cannot exceed 100', [1512, 400]);
        }

        return [
            'reduction_percent' => $percent,
            'reduction_amount' => $amount,
        ];
    }

    /**
     * @param int   $idProduct
     * @param array $line
     */
    protected function insertLink($idProduct, array $line)
    {
        $names = $this->resolveLinkNames($line['id_accessory'], $line['name']);

        Db::getInstance()->insert('accessory_group_product', [
            'id_accessory_group' => (int) $line['id_accessory_group'],
            'id_product' => (int) $idProduct,
            'id_accessory' => (int) $line['id_accessory'],
            'id_product_attribute' => (int) $line['id_product_attribute'],
            'default_quantity' => (int) $line['default_quantity'],
            'min_quantity' => (int) $line['min_quantity'],
            'required' => (int) $line['required'],
            'position' => (int) $line['position'],
        ]);
        $idLink = (int) Db::getInstance()->Insert_ID();

        foreach ($names as $idLang => $name) {
            Db::getInstance()->insert('accessory_group_product_lang', [
                'id_accessory_group_product' => $idLink,
                'id_lang' => (int) $idLang,
                'name' => pSQL($name),
            ]);
        }

        $this->syncAccessoryDiscount(
            (int) $idProduct,
            $idLink,
            [
                'reduction_percent' => (float) ($line['reduction_percent'] ?? 0),
                'reduction_amount' => (float) ($line['reduction_amount'] ?? 0),
            ]
        );
    }

    /**
     * @param int   $idAccessory
     * @param mixed $rawName
     *
     * @return array
     */
    protected function resolveLinkNames($idAccessory, $rawName)
    {
        $langs = Language::getLanguages(false);
        $names = [];

        if (is_string($rawName) && $rawName !== '') {
            foreach ($langs as $lang) {
                $names[(int) $lang['id_lang']] = $rawName;
            }
        } elseif (is_array($rawName)) {
            foreach ($rawName as $idLang => $name) {
                if ((string) $name !== '') {
                    $names[(int) $idLang] = (string) $name;
                }
            }
        }

        if (!$names) {
            foreach ($langs as $lang) {
                $productName = (string) Db::getInstance()->getValue(
                    'SELECT `name` FROM `' . _DB_PREFIX_ . 'product_lang`
                     WHERE `id_product` = ' . (int) $idAccessory . '
                       AND `id_lang` = ' . (int) $lang['id_lang']
                );
                $names[(int) $lang['id_lang']] = $productName !== '' ? $productName : ('Accessory ' . (int) $idAccessory);
            }
        } else {
            $default = reset($names);
            foreach ($langs as $lang) {
                $idLang = (int) $lang['id_lang'];
                if (!isset($names[$idLang]) || $names[$idLang] === '') {
                    $names[$idLang] = $default;
                }
            }
        }

        return $names;
    }

    /**
     * @param int $idProduct
     */
    protected function deleteProductLinks($idProduct)
    {
        $this->deleteProductCartRules((int) $idProduct);

        $linkIds = Db::getInstance()->executeS(
            'SELECT `id_accessory_group_product` FROM `' . _DB_PREFIX_ . 'accessory_group_product`
             WHERE `id_product` = ' . (int) $idProduct
        );
        if ($linkIds) {
            $in = [];
            foreach ($linkIds as $row) {
                $in[] = (int) $row['id_accessory_group_product'];
            }
            Db::getInstance()->delete(
                'accessory_group_product_lang',
                '`id_accessory_group_product` IN (' . implode(',', $in) . ')'
            );
        }
        Db::getInstance()->delete('accessory_group_product', '`id_product` = ' . (int) $idProduct);
    }

    /**
     * Supprime les cart rules HSMA liées au produit principal.
     *
     * @param int $idProduct
     */
    protected function deleteProductCartRules($idProduct)
    {
        $this->ensureHsmaLoaded();

        $idCartRules = HsMaCartRule::getIdCartRuleByIdProduct((int) $idProduct);
        if (empty($idCartRules)) {
            return;
        }

        foreach ($idCartRules as $row) {
            $cartRule = new HsMaCartRule((int) $row['id_cart_rule']);
            if (Validate::isLoadedObject($cartRule)) {
                $cartRule->delete();
            }
        }
    }

    /**
     * Crée / met à jour / retire la remise HSMA pour une liaison accessoire.
     *
     * @param int   $idProduct
     * @param int   $idLink    id_accessory_group_product
     * @param array $discount
     *
     * @throws WebserviceException
     */
    protected function syncAccessoryDiscount($idProduct, $idLink, array $discount)
    {
        $this->ensureHsmaLoaded();

        $accessory = new HsAccessoriesGroupProduct((int) $idLink);
        if (!Validate::isLoadedObject($accessory)) {
            throw new WebserviceException('Unable to load accessory link for discount', [1513, 500]);
        }

        $product = new Product((int) $idProduct);
        if (!Validate::isLoadedObject($product)) {
            throw new WebserviceException('Unable to load main product for discount', [1514, 500]);
        }

        $existingId = (int) HsMaCartRule::getIdCartRuleByAccessoryProduct(
            (int) $accessory->id_accessory,
            (int) $product->id
        );
        if ($existingId > 0) {
            $existing = new HsMaCartRule($existingId);
            if (Validate::isLoadedObject($existing)) {
                $existing->deleteMulti((int) $product->id, (int) $accessory->id_accessory, 0);
            }
        }

        $percent = (float) ($discount['reduction_percent'] ?? 0);
        $amount = (float) ($discount['reduction_amount'] ?? 0);
        if ($percent <= 0 && $amount <= 0) {
            return;
        }

        // BO HSMA : discount_type 0 = %, 1 = montant
        $discountType = $percent > 0 ? 0 : 1;
        $discountValue = $percent > 0 ? $percent : $amount;

        $module = Module::getInstanceByName('hsmultiaccessoriespro');
        if (!$module || !method_exists($module, 'generateCartRuleNames')) {
            throw new WebserviceException('hsmultiaccessoriespro helpers unavailable', [1515, 500]);
        }

        $cartRule = new HsMaCartRule();
        $cartRule->product = $product;
        $cartRule->accessory = $accessory;
        $cartRule->multi_adding = 0;
        $cartRule->addCartRule(
            $discountType,
            $discountValue,
            $module->generateCartRuleNames($accessory),
            $module->getCartRuleDescription()
        );

        if (!Validate::isLoadedObject($cartRule)) {
            throw new WebserviceException('Unable to save accessory discount cart rule', [1516, 500]);
        }
    }

    /**
     * Charge les classes HSMA nécessaires (HsMaCartRule, HsAccessoriesGroupProduct).
     */
    protected function ensureHsmaLoaded()
    {
        if (!class_exists('HsMaCartRule', false) || !class_exists('HsAccessoriesGroupProduct', false)) {
            Module::getInstanceByName('hsmultiaccessoriespro');
        }
        if (!class_exists('HsMaCartRule', false)) {
            require_once _PS_MODULE_DIR_ . 'hsmultiaccessoriespro/abstract/classes/HsMaCartRule.php';
        }
        if (!class_exists('HsAccessoriesGroupProduct', false)) {
            require_once _PS_MODULE_DIR_ . 'hsmultiaccessoriespro/classes/HsAccessoriesGroupProduct.php';
        }
    }

    /**
     * @param int $idProduct
     *
     * @return array
     */
    protected function loadAccessories($idProduct)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT agp.*,
                    apcr.`id_cart_rule`,
                    cr.`reduction_percent`,
                    cr.`reduction_amount`
             FROM `' . _DB_PREFIX_ . 'accessory_group_product` agp
             LEFT JOIN `' . _DB_PREFIX_ . 'accessory_product_cart_rule` apcr
               ON apcr.`id_main_product` = agp.`id_product`
              AND apcr.`id_accessory` = agp.`id_accessory`
             LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule` cr
               ON cr.`id_cart_rule` = apcr.`id_cart_rule`
             WHERE agp.`id_product` = ' . (int) $idProduct . '
             ORDER BY agp.`position` ASC, agp.`id_accessory_group_product` ASC'
        );
        if (!$rows) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id_accessory_group_product' => (int) $row['id_accessory_group_product'],
                'id_accessory_group' => (int) $row['id_accessory_group'],
                'id_accessory' => (int) $row['id_accessory'],
                'id_product_attribute' => (int) $row['id_product_attribute'],
                'default_quantity' => (int) $row['default_quantity'],
                'min_quantity' => (int) $row['min_quantity'],
                'required' => (int) $row['required'],
                'position' => (int) $row['position'],
                'name' => $this->getLinkNames((int) $row['id_accessory_group_product']),
                'id_cart_rule' => !empty($row['id_cart_rule']) ? (int) $row['id_cart_rule'] : 0,
                'reduction_percent' => isset($row['reduction_percent']) ? (float) $row['reduction_percent'] : 0.0,
                'reduction_amount' => isset($row['reduction_amount']) ? (float) $row['reduction_amount'] : 0.0,
            ];
        }

        return $out;
    }

    /**
     * @param int $idLink
     *
     * @return array
     */
    protected function getLinkNames($idLink)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_lang`, `name` FROM `' . _DB_PREFIX_ . 'accessory_group_product_lang`
             WHERE `id_accessory_group_product` = ' . (int) $idLink
        );
        $names = [];
        if ($rows) {
            foreach ($rows as $row) {
                $names[(string) (int) $row['id_lang']] = (string) $row['name'];
            }
        }

        return $names;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            [
                'id_product', 'id', 'accessories',
                'id_accessory_group', 'id_accessory', 'id_product_attribute',
                'default_quantity', 'min_quantity', 'required', 'position', 'name',
                'reduction_percent', 'reduction_amount', 'discount_type', 'discount_value',
            ],
            ['product_accessory', 'product_accessories']
        );
    }
}
