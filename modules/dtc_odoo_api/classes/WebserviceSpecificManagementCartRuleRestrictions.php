<?php
/**
 * Endpoint webservice : liaisons des règles panier (absentes de /api/cart_rules).
 *
 * GET  /api/cart_rule_restrictions?id_cart_rule=X
 * PUT  /api/cart_rule_restrictions  (POST accepté aussi)
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementCartRuleRestrictions extends DtcOdooApiWsEndpoint
{
    /** @var string[] */
    protected static $simpleTypes = ['countries', 'carriers', 'groups', 'shops', 'compatible_cart_rules'];

    /** @var string[] */
    protected static $productRuleTypes = ['products', 'categories', 'attributes', 'manufacturers', 'suppliers'];

    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'cart_rule_restriction';
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

        if ($method === 'PUT' || $method === 'POST') {
            $this->payload = $this->handleWrite();

            return;
        }

        $this->methodNotAllowed('cart_rule_restrictions', 200);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $id = $this->resolveInt($this->getParams(), ['id_cart_rule', 'id']);
        if ($id <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write cart rule associations not exposed by /api/cart_rules',
                'methods' => [
                    'GET' => 'Return associations for id_cart_rule',
                    'PUT' => 'Replace provided association keys (omit a key to leave it unchanged)',
                    'POST' => 'Same as PUT',
                ],
                'fields' => [
                    'countries' => 'id_country list',
                    'carriers' => 'id_carrier from /api/carriers (stored as carrier id_reference)',
                    'groups' => 'id_group list',
                    'shops' => 'id_shop list',
                    'compatible_cart_rules' => 'id_cart_rule list (empty = compatible with all)',
                    'product_rule_groups' => '[{quantity, rules:[{type, ids}]}]',
                ],
                'product_rule_types' => self::$productRuleTypes,
                'example_put_json' => '{"id_cart_rule":42,"countries":[8],"groups":[3],"carriers":[12],"shops":[1],"compatible_cart_rules":[10],"product_rule_groups":[{"quantity":1,"rules":[{"type":"products","ids":[35]}]}]}',
            ];
        }

        $cartRule = $this->loadCartRule($id);

        return [
            'action' => 'list',
            'id_cart_rule' => $id,
            'country_restriction' => (int) $cartRule->country_restriction,
            'carrier_restriction' => (int) $cartRule->carrier_restriction,
            'group_restriction' => (int) $cartRule->group_restriction,
            'shop_restriction' => (int) $cartRule->shop_restriction,
            'cart_rule_restriction' => (int) $cartRule->cart_rule_restriction,
            'product_restriction' => (int) $cartRule->product_restriction,
            'countries' => $this->getSimpleIds($id, 'cart_rule_country', 'id_country'),
            'carriers' => $this->getCarrierIds($id),
            'groups' => $this->getSimpleIds($id, 'cart_rule_group', 'id_group'),
            'shops' => $this->getSimpleIds($id, 'cart_rule_shop', 'id_shop'),
            'compatible_cart_rules' => $this->getCompatibleIds($id),
            'product_rule_groups' => $this->getProductRuleGroups($id),
        ];
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleWrite()
    {
        $input = $this->getParams();
        $id = $this->resolveInt($input, ['id_cart_rule', 'id']);
        $cartRule = $this->loadCartRule($id);

        $updated = [];

        if (array_key_exists('countries', $input)) {
            $this->replaceSimple($id, 'cart_rule_country', 'id_country', 'country', $input['countries']);
            $updated[] = 'countries';
        }
        if (array_key_exists('carriers', $input)) {
            $this->replaceCarriers($id, $input['carriers']);
            $updated[] = 'carriers';
        }
        if (array_key_exists('groups', $input)) {
            $this->replaceSimple($id, 'cart_rule_group', 'id_group', 'group', $input['groups']);
            $updated[] = 'groups';
        }
        if (array_key_exists('shops', $input)) {
            $this->replaceSimple($id, 'cart_rule_shop', 'id_shop', 'shop', $input['shops']);
            $updated[] = 'shops';
        }
        if (array_key_exists('compatible_cart_rules', $input)) {
            $this->replaceCombinations($id, $input['compatible_cart_rules']);
            $updated[] = 'compatible_cart_rules';
        }
        if (array_key_exists('product_rule_groups', $input)) {
            $this->replaceProductRules($id, $input['product_rule_groups']);
            $updated[] = 'product_rule_groups';
        }

        if (!$updated) {
            throw new WebserviceException(
                'Provide at least one of: ' . implode(', ', array_merge(self::$simpleTypes, ['product_rule_groups'])),
                [201, 400]
            );
        }

        Cache::clean('getContextualValue_' . $id . '_*');
        $cartRule = $this->loadCartRule($id);

        return [
            'action' => 'update',
            'status' => 'ok',
            'id_cart_rule' => $id,
            'updated' => $updated,
            'country_restriction' => (int) $cartRule->country_restriction,
            'carrier_restriction' => (int) $cartRule->carrier_restriction,
            'group_restriction' => (int) $cartRule->group_restriction,
            'shop_restriction' => (int) $cartRule->shop_restriction,
            'cart_rule_restriction' => (int) $cartRule->cart_rule_restriction,
            'product_restriction' => (int) $cartRule->product_restriction,
            'countries' => $this->getSimpleIds($id, 'cart_rule_country', 'id_country'),
            'carriers' => $this->getCarrierIds($id),
            'groups' => $this->getSimpleIds($id, 'cart_rule_group', 'id_group'),
            'shops' => $this->getSimpleIds($id, 'cart_rule_shop', 'id_shop'),
            'compatible_cart_rules' => $this->getCompatibleIds($id),
            'product_rule_groups' => $this->getProductRuleGroups($id),
        ];
    }

    /**
     * @param int $id
     * @param string $table
     * @param string $column
     *
     * @return int[]
     */
    protected function getSimpleIds($id, $table, $column)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `' . bqSQL($column) . '` FROM `' . _DB_PREFIX_ . bqSQL($table) . '`
             WHERE `id_cart_rule` = ' . (int) $id
        );
        if (!$rows) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row[$column];
        }

        return $ids;
    }

    /**
     * Retourne les id_carrier courants (API /api/carriers), pas les id_reference stockÃ©s.
     *
     * @param int $id
     *
     * @return int[]
     */
    protected function getCarrierIds($id)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT crc.`id_carrier` AS id_reference, c.`id_carrier`
             FROM `' . _DB_PREFIX_ . 'cart_rule_carrier` crc
             LEFT JOIN `' . _DB_PREFIX_ . 'carrier` c
                ON c.`id_reference` = crc.`id_carrier` AND c.`deleted` = 0
             WHERE crc.`id_cart_rule` = ' . (int) $id
        );
        if (!$rows) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) ($row['id_carrier'] ? $row['id_carrier'] : $row['id_reference']);
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param int $id
     *
     * @return int[]
     */
    protected function getCompatibleIds($id)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_cart_rule_2` FROM `' . _DB_PREFIX_ . 'cart_rule_combination`
             WHERE `id_cart_rule_1` = ' . (int) $id
        );
        if (!$rows) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['id_cart_rule_2'];
        }

        return $ids;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    protected function getProductRuleGroups($id)
    {
        $groups = Db::getInstance()->executeS(
            'SELECT `id_product_rule_group`, `quantity`
             FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`
             WHERE `id_cart_rule` = ' . (int) $id
        );
        if (!$groups) {
            return [];
        }

        $out = [];
        foreach ($groups as $group) {
            $rules = Db::getInstance()->executeS(
                'SELECT pr.`id_product_rule`, pr.`type`, prv.`id_item`
                 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule` pr
                 LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_product_rule_value` prv
                    ON prv.`id_product_rule` = pr.`id_product_rule`
                 WHERE pr.`id_product_rule_group` = ' . (int) $group['id_product_rule_group']
            );
            $byRule = [];
            if ($rules) {
                foreach ($rules as $rule) {
                    $rid = (int) $rule['id_product_rule'];
                    if (!isset($byRule[$rid])) {
                        $byRule[$rid] = [
                            'type' => (string) $rule['type'],
                            'ids' => [],
                        ];
                    }
                    if ($rule['id_item'] !== null) {
                        $byRule[$rid]['ids'][] = (int) $rule['id_item'];
                    }
                }
            }
            $out[] = [
                'quantity' => (int) $group['quantity'],
                'rules' => array_values($byRule),
            ];
        }

        return $out;
    }

    /**
     * @param int $id
     * @param string $table
     * @param string $column
     * @param string $flagType country|group|shop
     * @param mixed $ids
     */
    protected function replaceSimple($id, $table, $column, $flagType, $ids)
    {
        $ids = $this->normalizeIdList($ids);
        Db::getInstance()->delete($table, '`id_cart_rule` = ' . (int) $id);

        if ($ids) {
            $values = [];
            foreach ($ids as $itemId) {
                $values[] = [
                    'id_cart_rule' => (int) $id,
                    $column => (int) $itemId,
                ];
            }
            Db::getInstance()->insert($table, $values);
        }

        Db::getInstance()->update(
            'cart_rule',
            [$flagType . '_restriction' => $ids ? 1 : 0],
            'id_cart_rule = ' . (int) $id
        );
    }

    /**
     * @param int $id
     * @param mixed $ids
     */
    protected function replaceCarriers($id, $ids)
    {
        $references = $this->toCarrierReferences($ids, 206);

        Db::getInstance()->delete('cart_rule_carrier', '`id_cart_rule` = ' . (int) $id);

        if ($references) {
            $values = [];
            foreach ($references as $ref) {
                $values[] = [
                    'id_cart_rule' => (int) $id,
                    'id_carrier' => (int) $ref,
                ];
            }
            Db::getInstance()->insert('cart_rule_carrier', $values);
        }

        Db::getInstance()->update(
            'cart_rule',
            ['carrier_restriction' => $references ? 1 : 0],
            'id_cart_rule = ' . (int) $id
        );
    }

    /**
     * @param int $id
     * @param mixed $ids
     */
    protected function replaceCombinations($id, $ids)
    {
        $ids = $this->normalizeIdList($ids);
        $ids = array_values(array_filter($ids, function ($otherId) use ($id) {
            return (int) $otherId !== (int) $id;
        }));

        Db::getInstance()->delete(
            'cart_rule_combination',
            '`id_cart_rule_1` = ' . (int) $id . ' OR `id_cart_rule_2` = ' . (int) $id
        );

        if ($ids) {
            Db::getInstance()->update(
                'cart_rule',
                ['cart_rule_restriction' => 1],
                'id_cart_rule = ' . (int) $id
            );
            $values = [];
            foreach ($ids as $otherId) {
                $values[] = [
                    'id_cart_rule_1' => (int) $id,
                    'id_cart_rule_2' => (int) $otherId,
                ];
            }
            Db::getInstance()->insert('cart_rule_combination', $values);

            return;
        }

        Db::getInstance()->update(
            'cart_rule',
            ['cart_rule_restriction' => 0],
            'id_cart_rule = ' . (int) $id
        );

        // Comme le BO : une rÃ¨gle sans restriction est ajoutÃ©e Ã  la whitelist des rÃ¨gles restreintes.
        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
                SELECT `id_cart_rule`, ' . (int) $id . '
                FROM `' . _DB_PREFIX_ . 'cart_rule`
                WHERE `cart_rule_restriction` = 1 AND `id_cart_rule` != ' . (int) $id . '
            )'
        );
    }

    /**
     * @param int $id
     * @param mixed $groups
     *
     * @throws WebserviceException
     */
    protected function replaceProductRules($id, $groups)
    {
        if ($groups === null || $groups === '') {
            $groups = [];
        }
        if (!is_array($groups)) {
            throw new WebserviceException('product_rule_groups must be an array', [202, 400]);
        }

        $groupIds = Db::getInstance()->executeS(
            'SELECT `id_product_rule_group` FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`
             WHERE `id_cart_rule` = ' . (int) $id
        );
        if ($groupIds) {
            $in = [];
            foreach ($groupIds as $row) {
                $in[] = (int) $row['id_product_rule_group'];
            }
            $inSql = implode(',', $in);
            $ruleIds = Db::getInstance()->executeS(
                'SELECT `id_product_rule` FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule`
                 WHERE `id_product_rule_group` IN (' . $inSql . ')'
            );
            if ($ruleIds) {
                $ruleIn = [];
                foreach ($ruleIds as $row) {
                    $ruleIn[] = (int) $row['id_product_rule'];
                }
                Db::getInstance()->delete(
                    'cart_rule_product_rule_value',
                    '`id_product_rule` IN (' . implode(',', $ruleIn) . ')'
                );
            }
            Db::getInstance()->delete(
                'cart_rule_product_rule',
                '`id_product_rule_group` IN (' . $inSql . ')'
            );
        }
        Db::getInstance()->delete('cart_rule_product_rule_group', '`id_cart_rule` = ' . (int) $id);

        $hasRules = false;
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $quantity = isset($group['quantity']) ? max(1, (int) $group['quantity']) : 1;
            $rules = [];
            if (isset($group['rules']) && is_array($group['rules'])) {
                $rules = $group['rules'];
            }
            Db::getInstance()->insert('cart_rule_product_rule_group', [
                'id_cart_rule' => (int) $id,
                'quantity' => $quantity,
            ]);
            $idGroup = (int) Db::getInstance()->Insert_ID();
            foreach ($rules as $rule) {
                if (!is_array($rule) || empty($rule['type'])) {
                    continue;
                }
                $type = Tools::strtolower((string) $rule['type']);
                if (!in_array($type, self::$productRuleTypes, true)) {
                    throw new WebserviceException(
                        'Unsupported product rule type "' . $type . '". Allowed: ' . implode(', ', self::$productRuleTypes),
                        [203, 400]
                    );
                }
                $itemIds = $this->normalizeIdList(isset($rule['ids']) ? $rule['ids'] : []);
                Db::getInstance()->insert('cart_rule_product_rule', [
                    'id_product_rule_group' => $idGroup,
                    'type' => pSQL($type),
                ]);
                $idRule = (int) Db::getInstance()->Insert_ID();
                if ($itemIds) {
                    $values = [];
                    foreach ($itemIds as $itemId) {
                        $values[] = [
                            'id_product_rule' => $idRule,
                            'id_item' => (int) $itemId,
                        ];
                    }
                    Db::getInstance()->insert('cart_rule_product_rule_value', $values);
                    $hasRules = true;
                }
            }
        }

        Db::getInstance()->update(
            'cart_rule',
            ['product_restriction' => $hasRules ? 1 : 0],
            'id_cart_rule = ' . (int) $id
        );
    }

    /**
     * @param int $id
     *
     * @return CartRule
     *
     * @throws WebserviceException
     */
    protected function loadCartRule($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            throw new WebserviceException('id_cart_rule is required', [204, 400]);
        }

        $cartRule = new CartRule($id);
        if (!Validate::isLoadedObject($cartRule)) {
            throw new WebserviceException('Cart rule ' . $id . ' does not exist', [205, 404]);
        }

        return $cartRule;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            array_merge(['id_cart_rule', 'id'], self::$simpleTypes, ['product_rule_groups']),
            ['cart_rule_restriction', 'cart_rule_restrictions']
        );
    }
}
