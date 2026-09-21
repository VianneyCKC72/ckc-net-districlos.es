<?php
/**
 * Endpoint webservice : associations règles frais de port (orderfees_shipping).
 *
 * GET  /api/shipping_rule_restrictions?id_of_shipping_rule=X
 * PUT  /api/shipping_rule_restrictions  (POST accepté aussi)
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementShippingRuleRestrictions extends DtcOdooApiWsEndpoint
{
    /** @var string[] */
    protected static $simpleIdKeys = [
        'countries', 'zones', 'states', 'carriers', 'groups', 'shops', 'genders',
        'compatible_shipping_rules',
    ];

    /** @var string[] */
    protected static $complexKeys = [
        'cities', 'product_rule_groups', 'dimension_rule_groups',
        'city_rule_groups', 'zipcode_rule_groups', 'package_rule_groups',
    ];

    /** @var string[] */
    protected static $productRuleTypes = [
        'products', 'categories', 'main_categories', 'attributes',
        'manufacturers', 'suppliers', 'features', 'outofstock',
    ];

    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'shipping_rule_restriction';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        $this->bootShippingModule();

        $method = Tools::strtoupper($this->wsObject->method);

        if ($method === 'GET') {
            $this->payload = $this->handleGet();

            return;
        }

        if ($method === 'PUT' || $method === 'POST') {
            $this->payload = $this->handleWrite();

            return;
        }

        $this->methodNotAllowed('shipping_rule_restrictions', 1100);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $id = $this->resolveInt($this->getParams(), ['id_of_shipping_rule', 'id']);

        if ($id <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write shipping rule associations (orderfees_shipping)',
                'methods' => [
                    'GET' => 'Return associations for id_of_shipping_rule',
                    'PUT' => 'Replace provided keys only (omit a key to leave unchanged)',
                    'POST' => 'Same as PUT',
                ],
                'simple_fields' => [
                    'countries' => 'id_country list',
                    'zones' => 'id_zone list',
                    'states' => 'id_state list',
                    'cities' => 'city name list (simple city restriction)',
                    'carriers' => 'id_carrier from /api/carriers (stored as id_reference)',
                    'groups' => 'id_group list',
                    'shops' => 'id_shop list',
                    'genders' => 'id_gender list',
                    'compatible_shipping_rules' => 'id_of_shipping_rule list',
                ],
                'complex_fields' => [
                    'product_rule_groups' => '[{quantity, rules:[{type, ids}]}]',
                    'dimension_rule_groups' => '[{base, rules:[{type, operator, value}]}]',
                    'city_rule_groups' => '[{rules:[{type, value}]}] (type = id_country)',
                    'zipcode_rule_groups' => '[{rules:[{type, operator, value}]}] (type = id_country)',
                    'package_rule_groups' => '[{unit, unit_weight, ratio, rules:[{range_start, range_end, round, divider, currency, tax, value}]}]',
                ],
                'product_rule_types' => self::$productRuleTypes,
            ];
        }

        $rule = $this->loadRule($id);

        return [
            'action' => 'list',
            'id_of_shipping_rule' => $id,
            'country_restriction' => (int) $rule->country_restriction,
            'zone_restriction' => (int) $rule->zone_restriction,
            'state_restriction' => (int) $rule->state_restriction,
            'city_restriction' => (int) $rule->city_restriction,
            'carrier_restriction' => (int) $rule->carrier_restriction,
            'group_restriction' => (int) $rule->group_restriction,
            'shop_restriction' => (int) $rule->shop_restriction,
            'gender_restriction' => (int) $rule->gender_restriction,
            'of_shipping_rule_restriction' => (int) $rule->of_shipping_rule_restriction,
            'product_restriction' => (int) $rule->product_restriction,
            'dimension_restriction' => (int) $rule->dimension_restriction,
            'zipcode_restriction' => (int) $rule->zipcode_restriction,
            'package_restriction' => (int) $rule->package_restriction,
            'countries' => $this->getSimpleIds($id, 'of_shipping_rule_country', 'id_country'),
            'zones' => $this->getSimpleIds($id, 'of_shipping_rule_zone', 'id_zone'),
            'states' => $this->getSimpleIds($id, 'of_shipping_rule_state', 'id_state'),
            'cities' => $this->getCityNames($id),
            'carriers' => $this->getCarrierIds($id),
            'groups' => $this->getSimpleIds($id, 'of_shipping_rule_group', 'id_group'),
            'shops' => $this->getSimpleIds($id, 'of_shipping_rule_shop', 'id_shop'),
            'genders' => $this->getSimpleIds($id, 'of_shipping_rule_gender', 'id_gender'),
            'compatible_shipping_rules' => $this->getCompatibleIds($id),
            'product_rule_groups' => $this->getProductRuleGroups($id),
            'dimension_rule_groups' => $this->getDimensionRuleGroups($id),
            'city_rule_groups' => $this->getCityRuleGroups($id),
            'zipcode_rule_groups' => $this->getZipcodeRuleGroups($id),
            'package_rule_groups' => $this->getPackageRuleGroups($id),
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
        $id = $this->resolveInt($input, ['id_of_shipping_rule', 'id']);
        $rule = $this->loadRule($id);

        $updated = [];

        if (array_key_exists('countries', $input)) {
            $this->replaceSimple($id, 'of_shipping_rule_country', 'id_country', 'country_restriction', $input['countries']);
            $updated[] = 'countries';
        }
        if (array_key_exists('zones', $input)) {
            $this->replaceSimple($id, 'of_shipping_rule_zone', 'id_zone', 'zone_restriction', $input['zones']);
            $updated[] = 'zones';
        }
        if (array_key_exists('states', $input)) {
            $this->replaceSimple($id, 'of_shipping_rule_state', 'id_state', 'state_restriction', $input['states']);
            $updated[] = 'states';
        }
        if (array_key_exists('cities', $input)) {
            $this->replaceCities($id, $input['cities']);
            $updated[] = 'cities';
        }
        if (array_key_exists('carriers', $input)) {
            $this->replaceCarriers($id, $input['carriers']);
            $updated[] = 'carriers';
        }
        if (array_key_exists('groups', $input)) {
            $this->replaceSimple($id, 'of_shipping_rule_group', 'id_group', 'group_restriction', $input['groups']);
            $updated[] = 'groups';
        }
        if (array_key_exists('shops', $input)) {
            $this->replaceSimple($id, 'of_shipping_rule_shop', 'id_shop', 'shop_restriction', $input['shops']);
            $updated[] = 'shops';
        }
        if (array_key_exists('genders', $input)) {
            $this->replaceSimple($id, 'of_shipping_rule_gender', 'id_gender', 'gender_restriction', $input['genders']);
            $updated[] = 'genders';
        }
        if (array_key_exists('compatible_shipping_rules', $input)) {
            $this->replaceCombinations($id, $input['compatible_shipping_rules']);
            $updated[] = 'compatible_shipping_rules';
        }
        if (array_key_exists('product_rule_groups', $input)) {
            $this->replaceProductRules($id, $input['product_rule_groups']);
            $updated[] = 'product_rule_groups';
        }
        if (array_key_exists('dimension_rule_groups', $input)) {
            $this->replaceDimensionRules($id, $input['dimension_rule_groups']);
            $updated[] = 'dimension_rule_groups';
        }
        if (array_key_exists('city_rule_groups', $input)) {
            $this->replaceCityRuleGroups($id, $input['city_rule_groups']);
            $updated[] = 'city_rule_groups';
        }
        if (array_key_exists('zipcode_rule_groups', $input)) {
            $this->replaceZipcodeRuleGroups($id, $input['zipcode_rule_groups']);
            $updated[] = 'zipcode_rule_groups';
        }
        if (array_key_exists('package_rule_groups', $input)) {
            $this->replacePackageRuleGroups($id, $input['package_rule_groups']);
            $updated[] = 'package_rule_groups';
        }

        if (!$updated) {
            throw new WebserviceException(
                'Provide at least one of: ' . implode(', ', array_merge(self::$simpleIdKeys, self::$complexKeys)),
                [1101, 400]
            );
        }

        $rule = $this->loadRule($id);

        return [
            'action' => 'update',
            'status' => 'ok',
            'id_of_shipping_rule' => $id,
            'updated' => $updated,
            'country_restriction' => (int) $rule->country_restriction,
            'zone_restriction' => (int) $rule->zone_restriction,
            'state_restriction' => (int) $rule->state_restriction,
            'city_restriction' => (int) $rule->city_restriction,
            'carrier_restriction' => (int) $rule->carrier_restriction,
            'group_restriction' => (int) $rule->group_restriction,
            'shop_restriction' => (int) $rule->shop_restriction,
            'gender_restriction' => (int) $rule->gender_restriction,
            'of_shipping_rule_restriction' => (int) $rule->of_shipping_rule_restriction,
            'product_restriction' => (int) $rule->product_restriction,
            'dimension_restriction' => (int) $rule->dimension_restriction,
            'zipcode_restriction' => (int) $rule->zipcode_restriction,
            'package_restriction' => (int) $rule->package_restriction,
            'countries' => $this->getSimpleIds($id, 'of_shipping_rule_country', 'id_country'),
            'zones' => $this->getSimpleIds($id, 'of_shipping_rule_zone', 'id_zone'),
            'states' => $this->getSimpleIds($id, 'of_shipping_rule_state', 'id_state'),
            'cities' => $this->getCityNames($id),
            'carriers' => $this->getCarrierIds($id),
            'groups' => $this->getSimpleIds($id, 'of_shipping_rule_group', 'id_group'),
            'shops' => $this->getSimpleIds($id, 'of_shipping_rule_shop', 'id_shop'),
            'genders' => $this->getSimpleIds($id, 'of_shipping_rule_gender', 'id_gender'),
            'compatible_shipping_rules' => $this->getCompatibleIds($id),
            'product_rule_groups' => $this->getProductRuleGroups($id),
            'dimension_rule_groups' => $this->getDimensionRuleGroups($id),
            'city_rule_groups' => $this->getCityRuleGroups($id),
            'zipcode_rule_groups' => $this->getZipcodeRuleGroups($id),
            'package_rule_groups' => $this->getPackageRuleGroups($id),
        ];
    }

    /**
     * @param int    $id
     * @param string $table
     * @param string $column
     *
     * @return int[]
     */
    protected function getSimpleIds($id, $table, $column)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `' . bqSQL($column) . '` FROM `' . _DB_PREFIX_ . bqSQL($table) . '`
             WHERE `id_of_shipping_rule` = ' . (int) $id
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
     * @param int $id
     *
     * @return string[]
     */
    protected function getCityNames($id)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `name` FROM `' . _DB_PREFIX_ . 'of_shipping_rule_city`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if (!$rows) {
            return [];
        }

        $names = [];
        foreach ($rows as $row) {
            $names[] = (string) $row['name'];
        }

        return $names;
    }

    /**
     * @param int $id
     *
     * @return int[]
     */
    protected function getCarrierIds($id)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT src.`id_carrier` AS id_reference, c.`id_carrier`
             FROM `' . _DB_PREFIX_ . 'of_shipping_rule_carrier` src
             LEFT JOIN `' . _DB_PREFIX_ . 'carrier` c
                ON c.`id_reference` = src.`id_carrier` AND c.`deleted` = 0
             WHERE src.`id_of_shipping_rule` = ' . (int) $id
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
            'SELECT `id_of_shipping_rule_2` FROM `' . _DB_PREFIX_ . 'of_shipping_rule_combination`
             WHERE `id_of_shipping_rule_1` = ' . (int) $id
        );
        if (!$rows) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['id_of_shipping_rule_2'];
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
             FROM `' . _DB_PREFIX_ . 'of_shipping_rule_product_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if (!$groups) {
            return [];
        }

        $out = [];
        foreach ($groups as $group) {
            $rules = Db::getInstance()->executeS(
                'SELECT pr.`id_product_rule`, pr.`type`, prv.`id_item`
                 FROM `' . _DB_PREFIX_ . 'of_shipping_rule_product_rule` pr
                 LEFT JOIN `' . _DB_PREFIX_ . 'of_shipping_rule_product_rule_value` prv
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
     *
     * @return array
     */
    protected function getDimensionRuleGroups($id)
    {
        $groups = Db::getInstance()->executeS(
            'SELECT `id_dimension_rule_group`, `base`
             FROM `' . _DB_PREFIX_ . 'of_shipping_rule_dimension_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if (!$groups) {
            return [];
        }

        $out = [];
        foreach ($groups as $group) {
            $rules = Db::getInstance()->executeS(
                'SELECT `type`, `operator`, `value`
                 FROM `' . _DB_PREFIX_ . 'of_shipping_rule_dimension_rule`
                 WHERE `id_dimension_rule_group` = ' . (int) $group['id_dimension_rule_group']
            );
            $ruleList = [];
            if ($rules) {
                foreach ($rules as $rule) {
                    $ruleList[] = [
                        'type' => (string) $rule['type'],
                        'operator' => (string) $rule['operator'],
                        'value' => (string) $rule['value'],
                    ];
                }
            }
            $out[] = [
                'base' => (string) $group['base'],
                'rules' => $ruleList,
            ];
        }

        return $out;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    protected function getCityRuleGroups($id)
    {
        $groups = Db::getInstance()->executeS(
            'SELECT `id_city_rule_group`
             FROM `' . _DB_PREFIX_ . 'of_shipping_rule_city_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if (!$groups) {
            return [];
        }

        $out = [];
        foreach ($groups as $group) {
            $rules = Db::getInstance()->executeS(
                'SELECT `type`, `value`
                 FROM `' . _DB_PREFIX_ . 'of_shipping_rule_city_rule`
                 WHERE `id_city_rule_group` = ' . (int) $group['id_city_rule_group']
            );
            $ruleList = [];
            if ($rules) {
                foreach ($rules as $rule) {
                    $ruleList[] = [
                        'type' => (string) $rule['type'],
                        'value' => (string) $rule['value'],
                    ];
                }
            }
            $out[] = ['rules' => $ruleList];
        }

        return $out;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    protected function getZipcodeRuleGroups($id)
    {
        $groups = Db::getInstance()->executeS(
            'SELECT `id_zipcode_rule_group`
             FROM `' . _DB_PREFIX_ . 'of_shipping_rule_zipcode_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if (!$groups) {
            return [];
        }

        $out = [];
        foreach ($groups as $group) {
            $rules = Db::getInstance()->executeS(
                'SELECT `type`, `operator`, `value`
                 FROM `' . _DB_PREFIX_ . 'of_shipping_rule_zipcode_rule`
                 WHERE `id_zipcode_rule_group` = ' . (int) $group['id_zipcode_rule_group']
            );
            $ruleList = [];
            if ($rules) {
                foreach ($rules as $rule) {
                    $ruleList[] = [
                        'type' => (string) $rule['type'],
                        'operator' => (string) $rule['operator'],
                        'value' => (string) $rule['value'],
                    ];
                }
            }
            $out[] = ['rules' => $ruleList];
        }

        return $out;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    protected function getPackageRuleGroups($id)
    {
        $groups = Db::getInstance()->executeS(
            'SELECT `id_package_rule_group`, `unit`, `unit_weight`, `ratio`
             FROM `' . _DB_PREFIX_ . 'of_shipping_rule_package_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if (!$groups) {
            return [];
        }

        $out = [];
        foreach ($groups as $group) {
            $rules = Db::getInstance()->executeS(
                'SELECT `range_start`, `range_end`, `round`, `divider`, `currency`, `tax`, `value`
                 FROM `' . _DB_PREFIX_ . 'of_shipping_rule_package_rule`
                 WHERE `id_package_rule_group` = ' . (int) $group['id_package_rule_group']
            );
            $ruleList = [];
            if ($rules) {
                foreach ($rules as $rule) {
                    $ruleList[] = [
                        'range_start' => (float) $rule['range_start'],
                        'range_end' => (float) $rule['range_end'],
                        'round' => (float) $rule['round'],
                        'divider' => (float) $rule['divider'],
                        'currency' => (int) $rule['currency'],
                        'tax' => (int) $rule['tax'],
                        'value' => (float) $rule['value'],
                    ];
                }
            }
            $out[] = [
                'unit' => (string) $group['unit'],
                'unit_weight' => (string) $group['unit_weight'],
                'ratio' => (string) $group['ratio'],
                'rules' => $ruleList,
            ];
        }

        return $out;
    }

    /**
     * @param int    $id
     * @param string $table
     * @param string $column
     * @param string $flagField
     * @param mixed  $ids
     */
    protected function replaceSimple($id, $table, $column, $flagField, $ids)
    {
        $ids = $this->normalizeIdList($ids);
        Db::getInstance()->delete($table, '`id_of_shipping_rule` = ' . (int) $id);

        if ($ids) {
            $values = [];
            foreach ($ids as $itemId) {
                $values[] = [
                    'id_of_shipping_rule' => (int) $id,
                    $column => (int) $itemId,
                ];
            }
            Db::getInstance()->insert($table, $values);
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            [$flagField => $ids ? 1 : 0],
            'id_of_shipping_rule = ' . (int) $id
        );
    }

    /**
     * @param int   $id
     * @param mixed $names
     */
    protected function replaceCities($id, $names)
    {
        if (!is_array($names)) {
            $names = [$names];
        }

        $clean = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                $clean[$name] = $name;
            }
        }

        Db::getInstance()->delete('of_shipping_rule_city', '`id_of_shipping_rule` = ' . (int) $id);

        if ($clean) {
            $values = [];
            foreach ($clean as $name) {
                $values[] = [
                    'id_of_shipping_rule' => (int) $id,
                    'name' => pSQL($name, true),
                ];
            }
            Db::getInstance()->insert('of_shipping_rule_city', $values);
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            ['city_restriction' => $clean ? 1 : 0],
            'id_of_shipping_rule = ' . (int) $id
        );
    }

    /**
     * @param int   $id
     * @param mixed $ids
     */
    protected function replaceCarriers($id, $ids)
    {
        $references = $this->toCarrierReferences($ids, 1106);

        Db::getInstance()->delete('of_shipping_rule_carrier', '`id_of_shipping_rule` = ' . (int) $id);

        if ($references) {
            $values = [];
            foreach ($references as $ref) {
                $values[] = [
                    'id_of_shipping_rule' => (int) $id,
                    'id_carrier' => (int) $ref,
                ];
            }
            Db::getInstance()->insert('of_shipping_rule_carrier', $values);
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            ['carrier_restriction' => $references ? 1 : 0],
            'id_of_shipping_rule = ' . (int) $id
        );
    }

    /**
     * @param int   $id
     * @param mixed $ids
     */
    protected function replaceCombinations($id, $ids)
    {
        $ids = $this->normalizeIdList($ids);
        $ids = array_values(array_filter($ids, function ($otherId) use ($id) {
            return (int) $otherId !== (int) $id;
        }));

        Db::getInstance()->delete(
            'of_shipping_rule_combination',
            '`id_of_shipping_rule_1` = ' . (int) $id . ' OR `id_of_shipping_rule_2` = ' . (int) $id
        );

        if ($ids) {
            Db::getInstance()->update(
                'of_shipping_rule',
                ['of_shipping_rule_restriction' => 1],
                'id_of_shipping_rule = ' . (int) $id
            );
            $values = [];
            foreach ($ids as $otherId) {
                $values[] = [
                    'id_of_shipping_rule_1' => (int) $id,
                    'id_of_shipping_rule_2' => (int) $otherId,
                ];
            }
            Db::getInstance()->insert('of_shipping_rule_combination', $values);

            return;
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            ['of_shipping_rule_restriction' => 0],
            'id_of_shipping_rule = ' . (int) $id
        );

        Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'of_shipping_rule_combination` (`id_of_shipping_rule_1`, `id_of_shipping_rule_2`) (
                SELECT `id_of_shipping_rule`, ' . (int) $id . '
                FROM `' . _DB_PREFIX_ . 'of_shipping_rule`
                WHERE `of_shipping_rule_restriction` = 1 AND `id_of_shipping_rule` != ' . (int) $id . '
            )'
        );
    }

    /**
     * @param int   $id
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
            throw new WebserviceException('product_rule_groups must be an array', [1102, 400]);
        }

        $this->deleteProductRuleGroups($id);

        $hasRules = false;
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $quantity = isset($group['quantity']) ? max(1, (int) $group['quantity']) : 1;
            $rules = isset($group['rules']) && is_array($group['rules']) ? $group['rules'] : [];

            Db::getInstance()->insert('of_shipping_rule_product_rule_group', [
                'id_of_shipping_rule' => (int) $id,
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
                        'Unsupported product rule type "' . $type . '"',
                        [1103, 400]
                    );
                }
                $itemIds = $this->normalizeIdList(isset($rule['ids']) ? $rule['ids'] : []);
                Db::getInstance()->insert('of_shipping_rule_product_rule', [
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
                    Db::getInstance()->insert('of_shipping_rule_product_rule_value', $values);
                    $hasRules = true;
                }
            }
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            ['product_restriction' => $hasRules ? 1 : 0],
            'id_of_shipping_rule = ' . (int) $id
        );
    }

    /**
     * @param int   $id
     * @param mixed $groups
     *
     * @throws WebserviceException
     */
    protected function replaceDimensionRules($id, $groups)
    {
        if ($groups === null || $groups === '') {
            $groups = [];
        }
        if (!is_array($groups)) {
            throw new WebserviceException('dimension_rule_groups must be an array', [1104, 400]);
        }

        $this->deleteDimensionRuleGroups($id);

        $hasRules = false;
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $base = isset($group['base']) ? (string) $group['base'] : 'all';
            $rules = isset($group['rules']) && is_array($group['rules']) ? $group['rules'] : [];

            Db::getInstance()->insert('of_shipping_rule_dimension_rule_group', [
                'id_of_shipping_rule' => (int) $id,
                'base' => pSQL($base),
            ]);
            $idGroup = (int) Db::getInstance()->Insert_ID();

            foreach ($rules as $rule) {
                if (!is_array($rule) || empty($rule['type']) || empty($rule['operator'])) {
                    continue;
                }
                Db::getInstance()->insert('of_shipping_rule_dimension_rule', [
                    'id_dimension_rule_group' => $idGroup,
                    'type' => pSQL((string) $rule['type']),
                    'operator' => pSQL((string) $rule['operator']),
                    'value' => pSQL((string) ($rule['value'] ?? ''), true),
                ]);
                $hasRules = true;
            }
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            ['dimension_restriction' => $hasRules ? 1 : 0],
            'id_of_shipping_rule = ' . (int) $id
        );
    }

    /**
     * @param int   $id
     * @param mixed $groups
     *
     * @throws WebserviceException
     */
    protected function replaceCityRuleGroups($id, $groups)
    {
        if ($groups === null || $groups === '') {
            $groups = [];
        }
        if (!is_array($groups)) {
            throw new WebserviceException('city_rule_groups must be an array', [1105, 400]);
        }

        $this->deleteCityRuleGroups($id);

        $hasRules = false;
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $rules = isset($group['rules']) && is_array($group['rules']) ? $group['rules'] : [];

            Db::getInstance()->insert('of_shipping_rule_city_rule_group', [
                'id_of_shipping_rule' => (int) $id,
            ]);
            $idGroup = (int) Db::getInstance()->Insert_ID();

            foreach ($rules as $rule) {
                if (!is_array($rule) || empty($rule['type'])) {
                    continue;
                }
                Db::getInstance()->insert('of_shipping_rule_city_rule', [
                    'id_city_rule_group' => $idGroup,
                    'type' => pSQL((string) $rule['type']),
                    'value' => pSQL((string) ($rule['value'] ?? ''), true),
                ]);
                $hasRules = true;
            }
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            ['city_restriction' => $hasRules ? 1 : 0],
            'id_of_shipping_rule = ' . (int) $id
        );
    }

    /**
     * @param int   $id
     * @param mixed $groups
     *
     * @throws WebserviceException
     */
    protected function replaceZipcodeRuleGroups($id, $groups)
    {
        if ($groups === null || $groups === '') {
            $groups = [];
        }
        if (!is_array($groups)) {
            throw new WebserviceException('zipcode_rule_groups must be an array', [1107, 400]);
        }

        $this->deleteZipcodeRuleGroups($id);

        $hasRules = false;
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $rules = isset($group['rules']) && is_array($group['rules']) ? $group['rules'] : [];

            Db::getInstance()->insert('of_shipping_rule_zipcode_rule_group', [
                'id_of_shipping_rule' => (int) $id,
            ]);
            $idGroup = (int) Db::getInstance()->Insert_ID();

            foreach ($rules as $rule) {
                if (!is_array($rule) || empty($rule['type']) || empty($rule['operator'])) {
                    continue;
                }
                Db::getInstance()->insert('of_shipping_rule_zipcode_rule', [
                    'id_zipcode_rule_group' => $idGroup,
                    'type' => pSQL((string) $rule['type']),
                    'operator' => pSQL((string) $rule['operator']),
                    'value' => pSQL((string) ($rule['value'] ?? ''), true),
                ]);
                $hasRules = true;
            }
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            ['zipcode_restriction' => $hasRules ? 1 : 0],
            'id_of_shipping_rule = ' . (int) $id
        );
    }

    /**
     * @param int   $id
     * @param mixed $groups
     *
     * @throws WebserviceException
     */
    protected function replacePackageRuleGroups($id, $groups)
    {
        if ($groups === null || $groups === '') {
            $groups = [];
        }
        if (!is_array($groups)) {
            throw new WebserviceException('package_rule_groups must be an array', [1108, 400]);
        }

        $this->deletePackageRuleGroups($id);

        $hasRules = false;
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            Db::getInstance()->insert('of_shipping_rule_package_rule_group', [
                'id_of_shipping_rule' => (int) $id,
                'unit' => pSQL((string) ($group['unit'] ?? 'kg/m3')),
                'unit_weight' => pSQL((string) ($group['unit_weight'] ?? 'kg')),
                'ratio' => pSQL((string) ($group['ratio'] ?? '250')),
            ]);
            $idGroup = (int) Db::getInstance()->Insert_ID();

            $rules = isset($group['rules']) && is_array($group['rules']) ? $group['rules'] : [];
            foreach ($rules as $rule) {
                if (!is_array($rule)) {
                    continue;
                }
                Db::getInstance()->insert('of_shipping_rule_package_rule', [
                    'id_package_rule_group' => $idGroup,
                    'range_start' => (float) ($rule['range_start'] ?? 0),
                    'range_end' => (float) ($rule['range_end'] ?? 0),
                    'round' => (float) ($rule['round'] ?? 1),
                    'divider' => (float) ($rule['divider'] ?? 1),
                    'currency' => (int) ($rule['currency'] ?? 0),
                    'tax' => (int) ($rule['tax'] ?? 0),
                    'value' => (float) ($rule['value'] ?? 0),
                ]);
                $hasRules = true;
            }
        }

        Db::getInstance()->update(
            'of_shipping_rule',
            ['package_restriction' => $hasRules ? 1 : 0],
            'id_of_shipping_rule = ' . (int) $id
        );
    }

    /**
     * @param int $id
     */
    protected function deleteProductRuleGroups($id)
    {
        $groupIds = Db::getInstance()->executeS(
            'SELECT `id_product_rule_group` FROM `' . _DB_PREFIX_ . 'of_shipping_rule_product_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if ($groupIds) {
            $in = [];
            foreach ($groupIds as $row) {
                $in[] = (int) $row['id_product_rule_group'];
            }
            $inSql = implode(',', $in);
            $ruleIds = Db::getInstance()->executeS(
                'SELECT `id_product_rule` FROM `' . _DB_PREFIX_ . 'of_shipping_rule_product_rule`
                 WHERE `id_product_rule_group` IN (' . $inSql . ')'
            );
            if ($ruleIds) {
                $ruleIn = [];
                foreach ($ruleIds as $row) {
                    $ruleIn[] = (int) $row['id_product_rule'];
                }
                Db::getInstance()->delete(
                    'of_shipping_rule_product_rule_value',
                    '`id_product_rule` IN (' . implode(',', $ruleIn) . ')'
                );
            }
            Db::getInstance()->delete(
                'of_shipping_rule_product_rule',
                '`id_product_rule_group` IN (' . $inSql . ')'
            );
        }
        Db::getInstance()->delete('of_shipping_rule_product_rule_group', '`id_of_shipping_rule` = ' . (int) $id);
    }

    /**
     * @param int $id
     */
    protected function deleteDimensionRuleGroups($id)
    {
        $groupIds = Db::getInstance()->executeS(
            'SELECT `id_dimension_rule_group` FROM `' . _DB_PREFIX_ . 'of_shipping_rule_dimension_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if ($groupIds) {
            $in = [];
            foreach ($groupIds as $row) {
                $in[] = (int) $row['id_dimension_rule_group'];
            }
            Db::getInstance()->delete(
                'of_shipping_rule_dimension_rule',
                '`id_dimension_rule_group` IN (' . implode(',', $in) . ')'
            );
        }
        Db::getInstance()->delete('of_shipping_rule_dimension_rule_group', '`id_of_shipping_rule` = ' . (int) $id);
    }

    /**
     * @param int $id
     */
    protected function deleteCityRuleGroups($id)
    {
        $groupIds = Db::getInstance()->executeS(
            'SELECT `id_city_rule_group` FROM `' . _DB_PREFIX_ . 'of_shipping_rule_city_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if ($groupIds) {
            $in = [];
            foreach ($groupIds as $row) {
                $in[] = (int) $row['id_city_rule_group'];
            }
            Db::getInstance()->delete(
                'of_shipping_rule_city_rule',
                '`id_city_rule_group` IN (' . implode(',', $in) . ')'
            );
        }
        Db::getInstance()->delete('of_shipping_rule_city_rule_group', '`id_of_shipping_rule` = ' . (int) $id);
    }

    /**
     * @param int $id
     */
    protected function deleteZipcodeRuleGroups($id)
    {
        $groupIds = Db::getInstance()->executeS(
            'SELECT `id_zipcode_rule_group` FROM `' . _DB_PREFIX_ . 'of_shipping_rule_zipcode_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if ($groupIds) {
            $in = [];
            foreach ($groupIds as $row) {
                $in[] = (int) $row['id_zipcode_rule_group'];
            }
            Db::getInstance()->delete(
                'of_shipping_rule_zipcode_rule',
                '`id_zipcode_rule_group` IN (' . implode(',', $in) . ')'
            );
        }
        Db::getInstance()->delete('of_shipping_rule_zipcode_rule_group', '`id_of_shipping_rule` = ' . (int) $id);
    }

    /**
     * @param int $id
     */
    protected function deletePackageRuleGroups($id)
    {
        $groupIds = Db::getInstance()->executeS(
            'SELECT `id_package_rule_group` FROM `' . _DB_PREFIX_ . 'of_shipping_rule_package_rule_group`
             WHERE `id_of_shipping_rule` = ' . (int) $id
        );
        if ($groupIds) {
            $in = [];
            foreach ($groupIds as $row) {
                $in[] = (int) $row['id_package_rule_group'];
            }
            Db::getInstance()->delete(
                'of_shipping_rule_package_rule',
                '`id_package_rule_group` IN (' . implode(',', $in) . ')'
            );
        }
        Db::getInstance()->delete('of_shipping_rule_package_rule_group', '`id_of_shipping_rule` = ' . (int) $id);
    }

    /**
     * @param int $id
     *
     * @return ShippingRule
     *
     * @throws WebserviceException
     */
    protected function loadRule($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            throw new WebserviceException('id_of_shipping_rule is required', [1109, 400]);
        }

        $rule = new ShippingRule($id);
        if (!Validate::isLoadedObject($rule)) {
            throw new WebserviceException('Shipping rule ' . $id . ' does not exist', [1110, 404]);
        }

        return $rule;
    }

    /**
     * @throws WebserviceException
     */
    protected function bootShippingModule()
    {
        if (!Module::isEnabled('orderfees_shipping')) {
            throw new WebserviceException('orderfees_shipping module is not enabled', [1111, 503]);
        }

        require_once _PS_MODULE_DIR_ . 'orderfees_shipping/classes/ShippingRule.php';
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            array_merge(
                ['id_of_shipping_rule', 'id'],
                self::$simpleIdKeys,
                self::$complexKeys
            ),
            ['shipping_rule_restriction', 'shipping_rule_restrictions']
        );
    }
}
