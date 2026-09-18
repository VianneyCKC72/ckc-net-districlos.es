<?php
/**
 * Endpoint webservice : groupes de clients (table customer_group).
 *
 * GET  /api/customer_groups?id_customer=X
 * PUT  /api/customer_groups  (POST accepté aussi)
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementCustomerGroups extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'customer_group';
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

        $this->methodNotAllowed('customer_groups', 400);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $idCustomer = $this->resolveInt($input, ['id_customer', 'id']);

        if ($idCustomer <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write customer group assignments (table customer_group)',
                'methods' => [
                    'GET' => 'Return groups for id_customer',
                    'PUT' => 'Replace groups list for id_customer',
                    'POST' => 'Same as PUT',
                ],
                'fields' => [
                    'id_customer' => 'Customer id (required)',
                    'groups' => 'id_group list from /api/groups',
                    'id_default_group' => 'Optional: also update the default group on the customer record',
                ],
                'notes' => [
                    'Empty groups list assigns only the customer default group (BO behavior)',
                    'id_default_group must be included in the groups list if provided',
                ],
                'example_get' => '/api/customer_groups?id_customer=42',
                'example_put_json' => '{"id_customer":42,"groups":[3,4],"id_default_group":3}',
            ];
        }

        $customer = $this->loadCustomer($idCustomer);
        $groups = array_map('intval', $customer->getGroups());
        sort($groups);

        return [
            'action' => 'list',
            'id_customer' => $idCustomer,
            'id_default_group' => (int) $customer->id_default_group,
            'groups' => $groups,
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
        $idCustomer = $this->resolveInt($input, ['id_customer', 'id']);

        if ($idCustomer <= 0) {
            throw new WebserviceException('id_customer is required', [401, 400]);
        }
        if (!array_key_exists('groups', $input)) {
            throw new WebserviceException('groups field is required (use empty array for default group only)', [402, 400]);
        }

        $customer = $this->loadCustomer($idCustomer);
        $groups = $this->normalizeIdList($input['groups']);

        if (!empty($input['id_default_group'])) {
            $newDefault = (int) $input['id_default_group'];
            $this->assertRowExists('group', 'id_group', $newDefault, 'Group', 403);
            $customer->id_default_group = $newDefault;
            $customer->update();
        }

        if (empty($groups)) {
            $groups = [(int) $customer->id_default_group];
        }
        if (!in_array((int) $customer->id_default_group, $groups, true)) {
            $groups[] = (int) $customer->id_default_group;
        }

        foreach ($groups as $gid) {
            $this->assertRowExists('group', 'id_group', $gid, 'Group', 404);
        }

        $customer->cleanGroups();
        $customer->addGroups($groups);
        sort($groups);

        return [
            'action' => 'update',
            'status' => 'ok',
            'id_customer' => $idCustomer,
            'id_default_group' => (int) $customer->id_default_group,
            'groups' => $groups,
        ];
    }

    /**
     * @param int $idCustomer
     *
     * @return Customer
     *
     * @throws WebserviceException
     */
    protected function loadCustomer($idCustomer)
    {
        $customer = new Customer((int) $idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            throw new WebserviceException('Customer ' . (int) $idCustomer . ' does not exist', [406, 404]);
        }

        return $customer;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_customer', 'id', 'groups', 'id_default_group'],
            ['customer_group', 'customer_groups']
        );
    }
}
