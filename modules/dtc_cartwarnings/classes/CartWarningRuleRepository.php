<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartWarningRuleRepository
{
    /** @var array|null */
    private static $evaluationCache;

    /**
     * @param int $idLang
     *
     * @return array{
     *   incompatibilities: array<int, array>,
     *   missing_simple: array<int, array>,
     *   missing_multiple: array<int, array>
     * }
     */
    public static function getEvaluationData($idLang)
    {
        if (self::$evaluationCache !== null) {
            return self::$evaluationCache;
        }

        $idLang = (int) $idLang;
        $cacheId = 'dtc_cartwarnings_eval_' . $idLang;

        if (Cache::isStored($cacheId)) {
            return self::$evaluationCache = Cache::retrieve($cacheId);
        }

        $data = [
            'incompatibilities' => [],
            'missing_simple' => [],
            'missing_multiple' => [],
        ];

        try {
            $rules = Db::getInstance()->executeS(
                'SELECT r.`id_rule`, r.`rule_type`, r.`missing_product_id`, r.`attribute_product_id`, r.`attribute_contains`,
                        rl.`message_main`, rl.`message_extra`
                FROM `' . _DB_PREFIX_ . 'dtc_cart_warning_rule` r
                LEFT JOIN `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_lang` rl
                    ON (r.`id_rule` = rl.`id_rule` AND rl.`id_lang` = ' . $idLang . ')
                WHERE r.`active` = 1
                  AND ' . CartWarningRule::sqlTopLevelCondition('r') . '
                ORDER BY r.`position` ASC, r.`id_rule` ASC'
            );
        } catch (Exception $e) {
            return self::$evaluationCache = $data;
        }

        if (!$rules) {
            Cache::store($cacheId, $data);

            return self::$evaluationCache = $data;
        }

        $ruleIds = array_map('intval', array_column($rules, 'id_rule'));
        $productsByRule = self::loadProductsByRules($ruleIds);
        $childrenByParent = self::loadChildrenByParents($ruleIds, $idLang);

        foreach ($rules as $rule) {
            $idRule = (int) $rule['id_rule'];
            $products = isset($productsByRule[$idRule]) ? $productsByRule[$idRule] : [];

            switch ($rule['rule_type']) {
                case CartWarningRule::TYPE_INCOMPATIBILITY:
                    $data['incompatibilities'][] = [
                        'all_of' => isset($products[CartWarningRule::ROLE_ALL_OF]) ? $products[CartWarningRule::ROLE_ALL_OF] : [],
                        'any_of' => isset($products[CartWarningRule::ROLE_ANY_OF]) ? $products[CartWarningRule::ROLE_ANY_OF] : [],
                        'message_on' => isset($products[CartWarningRule::ROLE_MESSAGE_ON]) ? $products[CartWarningRule::ROLE_MESSAGE_ON] : [],
                        'attribute_product' => (int) $rule['attribute_product_id'],
                        'attribute_contains' => (string) $rule['attribute_contains'],
                        'message' => (string) $rule['message_main'],
                    ];
                    break;

                case CartWarningRule::TYPE_MISSING_SIMPLE:
                    $data['missing_simple'][] = [
                        'trigger_products' => isset($products[CartWarningRule::ROLE_TRIGGER]) ? $products[CartWarningRule::ROLE_TRIGGER] : [],
                        'missing_product' => (int) $rule['missing_product_id'],
                        'message_main' => (string) $rule['message_main'],
                        'message_extra' => (string) $rule['message_extra'],
                    ];
                    break;

                case CartWarningRule::TYPE_MISSING_MULTIPLE:
                    $missingProducts = [];
                    if (isset($childrenByParent[$idRule])) {
                        foreach ($childrenByParent[$idRule] as $child) {
                            $missingProducts[] = [
                                'id' => (int) $child['missing_product_id'],
                                'message_main' => (string) $child['message_main'],
                                'message_extra' => (string) $child['message_extra'],
                            ];
                        }
                    }
                    $triggerProducts = isset($products[CartWarningRule::ROLE_TRIGGER]) ? $products[CartWarningRule::ROLE_TRIGGER] : [];
                    foreach ($triggerProducts as $triggerId) {
                        $data['missing_multiple'][] = [
                            'trigger_product' => (int) $triggerId,
                            'missing_products' => $missingProducts,
                        ];
                    }
                    break;
            }
        }

        Cache::store($cacheId, $data);

        return self::$evaluationCache = $data;
    }

    public static function clearCache()
    {
        self::$evaluationCache = null;

        foreach (Language::getIDs(false) as $idLang) {
            Cache::clean('dtc_cartwarnings_eval_' . (int) $idLang);
        }
    }

    /**
     * @param int[] $ruleIds
     *
     * @return array<int, array<string, int[]>>
     */
    private static function loadProductsByRules(array $ruleIds)
    {
        if (empty($ruleIds)) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_rule`, `id_product`, `role`
            FROM `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_product`
            WHERE `id_rule` IN (' . implode(',', array_map('intval', $ruleIds)) . ')
            ORDER BY `id_rule_product` ASC'
        );

        $grouped = [];
        if (!$rows) {
            return $grouped;
        }

        foreach ($rows as $row) {
            $idRule = (int) $row['id_rule'];
            $role = (string) $row['role'];
            if (!isset($grouped[$idRule][$role])) {
                $grouped[$idRule][$role] = [];
            }
            $grouped[$idRule][$role][] = (int) $row['id_product'];
        }

        return $grouped;
    }

    /**
     * @param int[] $parentIds
     * @param int $idLang
     *
     * @return array<int, array<int, array>>
     */
    private static function loadChildrenByParents(array $parentIds, $idLang)
    {
        if (empty($parentIds)) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT r.`id_rule`, r.`id_parent`, r.`missing_product_id`, rl.`message_main`, rl.`message_extra`
            FROM `' . _DB_PREFIX_ . 'dtc_cart_warning_rule` r
            LEFT JOIN `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_lang` rl
                ON (r.`id_rule` = rl.`id_rule` AND rl.`id_lang` = ' . (int) $idLang . ')
            WHERE r.`id_parent` IN (' . implode(',', array_map('intval', $parentIds)) . ')
              AND r.`rule_type` = "' . pSQL(CartWarningRule::TYPE_MISSING_ITEM) . '"
              AND r.`active` = 1
            ORDER BY r.`position` ASC, r.`id_rule` ASC'
        );

        $grouped = [];
        if (!$rows) {
            return $grouped;
        }

        foreach ($rows as $row) {
            $idParent = (int) $row['id_parent'];
            if (!isset($grouped[$idParent])) {
                $grouped[$idParent] = [];
            }
            $grouped[$idParent][] = $row;
        }

        return $grouped;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getTopLevelRulesForList()
    {
        $idLang = (int) Context::getContext()->language->id;
        $rows = Db::getInstance()->executeS(
            'SELECT r.`id_rule`, r.`rule_type`, r.`active`, r.`position`, r.`missing_product_id`,
                    rl.`message_main`
            FROM `' . _DB_PREFIX_ . 'dtc_cart_warning_rule` r
            LEFT JOIN `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_lang` rl
                ON (r.`id_rule` = rl.`id_rule` AND rl.`id_lang` = ' . $idLang . ')
            WHERE ' . CartWarningRule::sqlTopLevelCondition('r') . '
            ORDER BY r.`position` ASC, r.`id_rule` ASC'
        );

        return $rows ? $rows : [];
    }
}
