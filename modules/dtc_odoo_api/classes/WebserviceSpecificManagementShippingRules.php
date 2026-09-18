<?php
/**
 * Endpoint webservice : règles frais de port (orderfees_shipping / of_shipping_rule).
 *
 * GET    /api/shipping_rules?id_of_shipping_rule=X
 * POST   /api/shipping_rules
 * PUT    /api/shipping_rules
 * DELETE /api/shipping_rules?id_of_shipping_rule=X
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementShippingRules extends DtcOdooApiWsEndpoint
{
    /** @var string[] */
    protected static $writableFields = [
        'name', 'id_customer', 'date_from', 'date_to', 'time_from', 'time_to',
        'priority', 'minimum_amount', 'minimum_amount_tax', 'minimum_amount_currency',
        'minimum_amount_restriction', 'maximum_amount', 'maximum_amount_tax',
        'maximum_amount_currency', 'maximum_amount_restriction',
        'country_restriction', 'zone_restriction', 'state_restriction', 'city_restriction',
        'carrier_restriction', 'group_restriction', 'of_shipping_rule_restriction',
        'product_restriction', 'shop_restriction', 'dimension_restriction',
        'zipcode_restriction', 'package_restriction', 'gender_restriction',
        'nb_supplier_min', 'nb_supplier_max', 'type', 'percent', 'amount', 'formula',
        'currency', 'tax_rules_group', 'product', 'quantity_per_product', 'active',
    ];

    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'shipping_rule';
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
        if ($method === 'POST') {
            $this->payload = $this->handleCreate();

            return;
        }
        if ($method === 'PUT') {
            $this->payload = $this->handleUpdate();

            return;
        }
        if ($method === 'DELETE') {
            $this->payload = $this->handleDelete();

            return;
        }

        $this->methodNotAllowed('shipping_rules', 1000);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $id = $this->resolveInt($input, ['id_of_shipping_rule', 'id']);

        if ($id <= 0) {
            $rows = Db::getInstance()->executeS(
                'SELECT `id_of_shipping_rule`, `name`, `priority`, `active`, `type`
                 FROM `' . _DB_PREFIX_ . 'of_shipping_rule`
                 ORDER BY `priority` ASC, `id_of_shipping_rule` ASC'
            );

            $rules = [];
            if ($rows) {
                foreach ($rows as $row) {
                    $rules[] = [
                        'id_of_shipping_rule' => (int) $row['id_of_shipping_rule'],
                        'name' => (string) $row['name'],
                        'priority' => (int) $row['priority'],
                        'active' => (int) $row['active'],
                        'type' => (int) $row['type'],
                    ];
                }
            }

            return [
                'action' => 'list',
                'count' => count($rules),
                'rules' => $rules,
                'help' => 'GET ?id_of_shipping_rule=X for full rule; associations via /api/shipping_rule_restrictions',
            ];
        }

        $rule = $this->loadRule($id);

        return array_merge(['action' => 'list'], $this->formatRule($rule));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleCreate()
    {
        $input = $this->getParams();

        if (empty($input['name'])) {
            throw new WebserviceException('name is required', [1001, 400]);
        }

        $rule = new ShippingRule();
        $this->applyWritableFields($rule, $input, true);

        if (!$rule->add()) {
            throw new WebserviceException('Failed to create shipping rule', [1002, 500]);
        }

        return array_merge([
            'action' => 'create',
            'status' => 'ok',
        ], $this->formatRule($rule));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleUpdate()
    {
        $input = $this->getParams();
        $id = $this->resolveInt($input, ['id_of_shipping_rule', 'id']);

        if ($id <= 0) {
            throw new WebserviceException('id_of_shipping_rule is required for PUT', [1003, 400]);
        }

        $rule = $this->loadRule($id);
        $updated = $this->applyWritableFields($rule, $input, false);

        if (empty($updated)) {
            throw new WebserviceException(
                'Provide at least one writable field: ' . implode(', ', self::$writableFields),
                [1004, 400]
            );
        }

        if (!$rule->update()) {
            throw new WebserviceException('Failed to update shipping rule', [1005, 500]);
        }

        return array_merge([
            'action' => 'update',
            'status' => 'ok',
            'updated' => $updated,
        ], $this->formatRule($rule));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleDelete()
    {
        $input = $this->getParams();
        $id = $this->resolveInt($input, ['id_of_shipping_rule', 'id']);

        if ($id <= 0) {
            throw new WebserviceException('id_of_shipping_rule is required for DELETE', [1006, 400]);
        }

        $rule = $this->loadRule($id);

        if (!$rule->delete()) {
            throw new WebserviceException('Failed to delete shipping rule', [1007, 500]);
        }

        return [
            'action' => 'delete',
            'status' => 'ok',
            'id_of_shipping_rule' => (int) $id,
        ];
    }

    /**
     * @param ShippingRule $rule
     * @param array        $input
     * @param bool         $isCreate
     *
     * @return string[]
     */
    protected function applyWritableFields($rule, $input, $isCreate)
    {
        $updated = [];

        foreach (self::$writableFields as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];

            if (in_array($field, ['date_from', 'date_to', 'time_from', 'time_to', 'nb_supplier_min', 'nb_supplier_max'], true)
                && ($value === '' || $value === null)
            ) {
                $value = null;
            }

            $rule->{$field} = $value;
            $updated[] = $field;
        }

        if ($isCreate && !in_array('name', $updated, true)) {
            $rule->name = (string) $input['name'];
        }

        return $updated;
    }

    /**
     * @param ShippingRule $rule
     *
     * @return array
     */
    protected function formatRule($rule)
    {
        return [
            'id_of_shipping_rule' => (int) $rule->id,
            'name' => (string) $rule->name,
            'id_customer' => (int) $rule->id_customer,
            'date_from' => $rule->date_from,
            'date_to' => $rule->date_to,
            'time_from' => $rule->time_from,
            'time_to' => $rule->time_to,
            'priority' => (int) $rule->priority,
            'minimum_amount' => (float) $rule->minimum_amount,
            'minimum_amount_tax' => (int) $rule->minimum_amount_tax,
            'minimum_amount_currency' => (int) $rule->minimum_amount_currency,
            'minimum_amount_restriction' => (int) $rule->minimum_amount_restriction,
            'maximum_amount' => (float) $rule->maximum_amount,
            'maximum_amount_tax' => (int) $rule->maximum_amount_tax,
            'maximum_amount_currency' => (int) $rule->maximum_amount_currency,
            'maximum_amount_restriction' => (int) $rule->maximum_amount_restriction,
            'country_restriction' => (int) $rule->country_restriction,
            'zone_restriction' => (int) $rule->zone_restriction,
            'state_restriction' => (int) $rule->state_restriction,
            'city_restriction' => (int) $rule->city_restriction,
            'carrier_restriction' => (int) $rule->carrier_restriction,
            'group_restriction' => (int) $rule->group_restriction,
            'of_shipping_rule_restriction' => (int) $rule->of_shipping_rule_restriction,
            'product_restriction' => (int) $rule->product_restriction,
            'shop_restriction' => (int) $rule->shop_restriction,
            'dimension_restriction' => (int) $rule->dimension_restriction,
            'zipcode_restriction' => (int) $rule->zipcode_restriction,
            'package_restriction' => (int) $rule->package_restriction,
            'gender_restriction' => (int) $rule->gender_restriction,
            'nb_supplier_min' => $rule->nb_supplier_min,
            'nb_supplier_max' => $rule->nb_supplier_max,
            'type' => (int) $rule->type,
            'percent' => (float) $rule->percent,
            'amount' => (float) $rule->amount,
            'formula' => (string) $rule->formula,
            'currency' => (int) $rule->currency,
            'tax_rules_group' => (int) $rule->tax_rules_group,
            'product' => (int) $rule->product,
            'quantity_per_product' => (int) $rule->quantity_per_product,
            'active' => (int) $rule->active,
            'date_add' => $rule->date_add,
            'date_upd' => $rule->date_upd,
        ];
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
            throw new WebserviceException('id_of_shipping_rule is required', [1008, 400]);
        }

        $rule = new ShippingRule($id);
        if (!Validate::isLoadedObject($rule)) {
            throw new WebserviceException('Shipping rule ' . $id . ' does not exist', [1009, 404]);
        }

        return $rule;
    }

    /**
     * @throws WebserviceException
     */
    protected function bootShippingModule()
    {
        if (!Module::isEnabled('orderfees_shipping')) {
            throw new WebserviceException('orderfees_shipping module is not enabled', [1010, 503]);
        }

        require_once _PS_MODULE_DIR_ . 'orderfees_shipping/classes/ShippingRule.php';
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            array_merge(['id_of_shipping_rule', 'id'], self::$writableFields),
            ['shipping_rule', 'shipping_rules']
        );
    }
}
