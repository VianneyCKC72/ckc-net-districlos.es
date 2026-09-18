<?php
/**
 * Endpoint webservice : groupes d'accessoires (hsmultiaccessoriespro / accessory_group).
 *
 * GET    /api/accessory_groups
 * GET    /api/accessory_groups?id_accessory_group=X
 * POST   /api/accessory_groups
 * PUT    /api/accessory_groups
 * DELETE /api/accessory_groups?id_accessory_group=X
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementAccessoryGroups extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'accessory_group';
    }

    /**
     * @throws WebserviceException
     */
    public function manage()
    {
        $this->assertModuleEnabled();

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

        $this->methodNotAllowed('accessory_groups', 1400);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $id = $this->resolveInt($this->getParams(), ['id_accessory_group', 'id']);

        if ($id <= 0) {
            $rows = Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'accessory_group`
                 ORDER BY `position` ASC, `id_accessory_group` ASC'
            );
            $groups = [];
            if ($rows) {
                foreach ($rows as $row) {
                    $groups[] = $this->formatGroup($row);
                }
            }

            return [
                'action' => 'list',
                'count' => count($groups),
                'groups' => $groups,
            ];
        }

        return array_merge(['action' => 'list'], $this->loadGroup($id));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleCreate()
    {
        $input = $this->getParams();
        $names = $this->normalizeLangNames($input['name'] ?? null, true);

        $position = isset($input['position']) ? (int) $input['position'] : $this->nextPosition();

        Db::getInstance()->insert('accessory_group', [
            'active' => isset($input['active']) ? (int) $input['active'] : 1,
            'position' => $position,
            'display_style' => isset($input['display_style']) ? (int) $input['display_style'] : 3,
            'buy_together' => isset($input['buy_together']) ? (int) $input['buy_together'] : 2,
            'show_name' => isset($input['show_name']) ? (int) $input['show_name'] : 1,
            'collapse_expand' => isset($input['collapse_expand']) ? (int) $input['collapse_expand'] : 1,
        ]);
        $id = (int) Db::getInstance()->Insert_ID();
        $this->saveGroupNames($id, $names);

        return array_merge([
            'action' => 'create',
            'status' => 'ok',
        ], $this->loadGroup($id));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleUpdate()
    {
        $input = $this->getParams();
        $id = $this->resolveInt($input, ['id_accessory_group', 'id']);
        if ($id <= 0) {
            throw new WebserviceException('id_accessory_group is required for PUT', [1401, 400]);
        }
        $this->assertGroupExists($id);

        $fields = [];
        foreach (['active', 'position', 'display_style', 'buy_together', 'show_name', 'collapse_expand'] as $key) {
            if (array_key_exists($key, $input)) {
                $fields[$key] = (int) $input[$key];
            }
        }
        if ($fields) {
            Db::getInstance()->update('accessory_group', $fields, 'id_accessory_group = ' . (int) $id);
        }

        if (array_key_exists('name', $input)) {
            $this->saveGroupNames($id, $this->normalizeLangNames($input['name'], true));
        }

        if (!$fields && !array_key_exists('name', $input)) {
            throw new WebserviceException('Provide at least one field to update', [1402, 400]);
        }

        return array_merge([
            'action' => 'update',
            'status' => 'ok',
        ], $this->loadGroup($id));
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleDelete()
    {
        $id = $this->resolveInt($this->getParams(), ['id_accessory_group', 'id']);
        if ($id <= 0) {
            throw new WebserviceException('id_accessory_group is required for DELETE', [1403, 400]);
        }
        $this->assertGroupExists($id);

        $linkIds = Db::getInstance()->executeS(
            'SELECT `id_accessory_group_product` FROM `' . _DB_PREFIX_ . 'accessory_group_product`
             WHERE `id_accessory_group` = ' . (int) $id
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
            Db::getInstance()->delete(
                'accessory_group_product',
                '`id_accessory_group` = ' . (int) $id
            );
        }

        Db::getInstance()->delete('accessory_group_lang', 'id_accessory_group = ' . (int) $id);
        Db::getInstance()->delete('accessory_group', 'id_accessory_group = ' . (int) $id);

        return [
            'action' => 'delete',
            'status' => 'ok',
            'id_accessory_group' => $id,
        ];
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function formatGroup(array $row)
    {
        return [
            'id_accessory_group' => (int) $row['id_accessory_group'],
            'active' => (int) $row['active'],
            'position' => (int) $row['position'],
            'display_style' => (int) $row['display_style'],
            'buy_together' => (int) $row['buy_together'],
            'show_name' => (int) $row['show_name'],
            'collapse_expand' => (int) $row['collapse_expand'],
            'name' => $this->getGroupNames((int) $row['id_accessory_group']),
        ];
    }

    /**
     * @param int $id
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function loadGroup($id)
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'accessory_group`
             WHERE `id_accessory_group` = ' . (int) $id
        );
        if (!$row) {
            throw new WebserviceException('Accessory group ' . (int) $id . ' does not exist', [1404, 404]);
        }

        return $this->formatGroup($row);
    }

    /**
     * @param int $id
     *
     * @return array
     */
    protected function getGroupNames($id)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_lang`, `name` FROM `' . _DB_PREFIX_ . 'accessory_group_lang`
             WHERE `id_accessory_group` = ' . (int) $id
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
     * @param mixed $raw
     * @param bool  $required
     *
     * @return array id_lang => name
     *
     * @throws WebserviceException
     */
    protected function normalizeLangNames($raw, $required)
    {
        $langs = Language::getLanguages(false);
        $names = [];

        if (is_string($raw) && $raw !== '') {
            foreach ($langs as $lang) {
                $names[(int) $lang['id_lang']] = $raw;
            }
        } elseif (is_array($raw)) {
            foreach ($raw as $idLang => $name) {
                $names[(int) $idLang] = (string) $name;
            }
        }

        if ($required && !$names) {
            throw new WebserviceException('name is required (string or {id_lang: name})', [1405, 400]);
        }

        // Fill missing langs with first provided value
        if ($names) {
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
     * @param int   $id
     * @param array $names
     */
    protected function saveGroupNames($id, array $names)
    {
        $db = Db::getInstance();
        $db->delete('accessory_group_lang', 'id_accessory_group = ' . (int) $id);
        foreach ($names as $idLang => $name) {
            $db->insert('accessory_group_lang', [
                'id_accessory_group' => (int) $id,
                'id_lang' => (int) $idLang,
                'name' => pSQL($name),
            ]);
        }
    }

    /**
     * @return int
     */
    protected function nextPosition()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COALESCE(MAX(`position`), 0) + 1 FROM `' . _DB_PREFIX_ . 'accessory_group`'
        );
    }

    /**
     * @param int $id
     *
     * @throws WebserviceException
     */
    protected function assertGroupExists($id)
    {
        if (!$this->rowExists('accessory_group', 'id_accessory_group', $id)) {
            throw new WebserviceException('Accessory group ' . (int) $id . ' does not exist', [1404, 404]);
        }
    }

    /**
     * @throws WebserviceException
     */
    protected function assertModuleEnabled()
    {
        if (!Module::isEnabled('hsmultiaccessoriespro')) {
            throw new WebserviceException('hsmultiaccessoriespro module is not enabled', [1406, 503]);
        }
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            [
                'id_accessory_group', 'id', 'name', 'active', 'position',
                'display_style', 'buy_together', 'show_name', 'collapse_expand',
            ],
            ['accessory_group', 'accessory_groups']
        );
    }
}
