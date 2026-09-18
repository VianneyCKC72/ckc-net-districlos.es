<?php
/**
 * Endpoint webservice : accès groupes clients sur une catégorie (table category_group).
 *
 * GET  /api/category_groups?id_category=X
 * PUT  /api/category_groups  (POST accepté aussi)
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementCategoryGroups extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'category_group';
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

        $this->methodNotAllowed('category_groups', 1900);
    }

    /**
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleGet()
    {
        $input = $this->getParams();
        $idCategory = $this->resolveInt($input, ['id_category', 'id']);

        if ($idCategory <= 0) {
            return [
                'action' => 'help',
                'description' => 'Read/write category ↔ customer group links (table category_group)',
                'methods' => [
                    'GET' => 'Return groups for id_category',
                    'PUT' => 'Replace groups list for id_category',
                    'POST' => 'Same as PUT',
                ],
                'fields' => [
                    'id_category' => 'Category id (required)',
                    'groups' => 'id_group list from /api/groups (empty = no group access)',
                ],
                'example_get' => '/api/category_groups?id_category=12',
                'example_put_json' => '{"id_category":12,"groups":[1,2,3]}',
            ];
        }

        $category = $this->loadCategory($idCategory);
        $groups = array_map('intval', $category->getGroups());
        sort($groups);

        return [
            'action' => 'list',
            'id_category' => $idCategory,
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
        $idCategory = $this->resolveInt($input, ['id_category', 'id']);

        if ($idCategory <= 0) {
            throw new WebserviceException('id_category is required', [1901, 400]);
        }
        if (!array_key_exists('groups', $input)) {
            throw new WebserviceException('groups field is required (use empty array to unlink all)', [1902, 400]);
        }

        $category = $this->loadCategory($idCategory);
        $groups = $this->normalizeIdList($input['groups']);

        foreach ($groups as $gid) {
            $this->assertRowExists('group', 'id_group', $gid, 'Group', 1903);
        }

        $category->cleanGroups();
        if (!empty($groups)) {
            $category->addGroups($groups);
        }
        sort($groups);

        return [
            'action' => 'update',
            'status' => 'ok',
            'id_category' => $idCategory,
            'groups' => $groups,
        ];
    }

    /**
     * @param int $idCategory
     *
     * @return Category
     *
     * @throws WebserviceException
     */
    protected function loadCategory($idCategory)
    {
        $category = new Category((int) $idCategory);
        if (!Validate::isLoadedObject($category)) {
            throw new WebserviceException('Category ' . (int) $idCategory . ' does not exist', [1904, 404]);
        }

        return $category;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_category', 'id', 'groups'],
            ['category_group', 'category_groups']
        );
    }
}
