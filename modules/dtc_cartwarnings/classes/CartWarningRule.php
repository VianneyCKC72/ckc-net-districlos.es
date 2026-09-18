<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartWarningRule extends ObjectModel
{
    const TYPE_INCOMPATIBILITY = 'incompatibility';
    const TYPE_MISSING_SIMPLE = 'missing_simple';
    const TYPE_MISSING_MULTIPLE = 'missing_multiple';
    const TYPE_MISSING_ITEM = 'missing_item';

    const ROLE_TRIGGER = 'trigger';
    const ROLE_ALL_OF = 'all_of';
    const ROLE_ANY_OF = 'any_of';
    const ROLE_MESSAGE_ON = 'message_on';

    /** @var int */
    public $id_rule;

    /** @var int|null */
    public $id_parent;

    /** @var string */
    public $rule_type;

    /** @var bool */
    public $active = true;

    /** @var int */
    public $position = 0;

    /** @var int|null */
    public $missing_product_id;

    /** @var int|null */
    public $attribute_product_id;

    /** @var string|null */
    public $attribute_contains;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    /** @var string */
    public $message_main;

    /** @var string|null */
    public $message_extra;

    public static $definition = [
        'table' => 'dtc_cart_warning_rule',
        'primary' => 'id_rule',
        'multilang' => true,
        'fields' => [
            'id_parent' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'allow_null' => true],
            'rule_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'missing_product_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'attribute_product_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'attribute_contains' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'message_main' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'message_extra' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
        ],
    ];

    /**
     * Règles de premier niveau (sans parent).
     *
     * @param string $alias
     *
     * @return string
     */
    public static function sqlTopLevelCondition($alias = 'r')
    {
        $prefix = $alias !== '' ? $alias . '.' : '';

        return '(' . $prefix . '`id_parent` IS NULL OR ' . $prefix . '`id_parent` = 0)';
    }

    public function add($autoDate = true, $nullValues = false)
    {
        if ($autoDate) {
            $this->date_add = date('Y-m-d H:i:s');
        }
        $this->date_upd = date('Y-m-d H:i:s');

        return parent::add($autoDate, $nullValues);
    }

    public function update($nullValues = false)
    {
        $this->date_upd = date('Y-m-d H:i:s');

        return parent::update($nullValues);
    }

    /**
     * @param int $idRule
     *
     * @return array<string, int[]>
     */
    public static function getProductIdsByRole($idRule)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `role`, `id_product`
            FROM `' . _DB_PREFIX_ . 'dtc_cart_warning_rule_product`
            WHERE `id_rule` = ' . (int) $idRule . '
            ORDER BY `id_rule_product` ASC'
        );

        $grouped = [
            self::ROLE_TRIGGER => [],
            self::ROLE_ALL_OF => [],
            self::ROLE_ANY_OF => [],
            self::ROLE_MESSAGE_ON => [],
        ];

        if (!$rows) {
            return $grouped;
        }

        foreach ($rows as $row) {
            $role = (string) $row['role'];
            if (!isset($grouped[$role])) {
                $grouped[$role] = [];
            }
            $grouped[$role][] = (int) $row['id_product'];
        }

        return $grouped;
    }

    /**
     * @param int $idRule
     * @param array<string, int[]> $productsByRole
     */
    public static function saveProductIdsByRole($idRule, array $productsByRole)
    {
        Db::getInstance()->delete('dtc_cart_warning_rule_product', 'id_rule = ' . (int) $idRule);

        foreach ($productsByRole as $role => $productIds) {
            foreach (array_unique(array_filter(array_map('intval', (array) $productIds))) as $idProduct) {
                if ($idProduct <= 0) {
                    continue;
                }
                Db::getInstance()->insert('dtc_cart_warning_rule_product', [
                    'id_rule' => (int) $idRule,
                    'id_product' => $idProduct,
                    'role' => pSQL($role),
                ]);
            }
        }
    }

    /**
     * @param string $csv
     *
     * @return int[]
     */
    public static function parseProductCsv($csv)
    {
        if ($csv === '' || $csv === null) {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', (string) $csv, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(array_map('intval', $parts))));
    }

    /**
     * @param int[] $productIds
     *
     * @return string
     */
    public static function formatProductCsv(array $productIds)
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));

        return implode(', ', $productIds);
    }

    /**
     * @return CartWarningRule[]
     */
    public static function getChildren($idParent)
    {
        $ids = Db::getInstance()->executeS(
            'SELECT `id_rule`
            FROM `' . _DB_PREFIX_ . 'dtc_cart_warning_rule`
            WHERE `id_parent` = ' . (int) $idParent . '
            ORDER BY `position` ASC, `id_rule` ASC'
        );

        if (!$ids) {
            return [];
        }

        $children = [];
        foreach ($ids as $row) {
            $child = new self((int) $row['id_rule']);
            if (Validate::isLoadedObject($child)) {
                $children[] = $child;
            }
        }

        return $children;
    }

    public function delete()
    {
        $children = self::getChildren((int) $this->id);
        foreach ($children as $child) {
            $child->delete();
        }

        Db::getInstance()->delete('dtc_cart_warning_rule_product', 'id_rule = ' . (int) $this->id);

        return parent::delete();
    }
}
