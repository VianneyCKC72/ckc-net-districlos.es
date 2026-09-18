<?php
/**
 * Endpoint webservice : FAQ produit (table product_faq).
 *
 * GET  /api/product_faq?id_product=X
 * PUT  /api/product_faq  → remplace toutes les FAQ du produit
 * POST /api/product_faq  → ajoute une ou plusieurs FAQ
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementProductFaq extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'product_faq';
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
        if ($method === 'PUT') {
            $this->payload = $this->handleReplace();

            return;
        }
        if ($method === 'POST') {
            $this->payload = $this->handleAdd();

            return;
        }

        $this->methodNotAllowed('product_faq', 800);
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
                'description' => 'Read/write product FAQ entries (table product_faq)',
                'methods' => [
                    'GET' => 'Return FAQ list for id_product',
                    'PUT' => 'Replace all FAQ entries for id_product',
                    'POST' => 'Add one or more FAQ entries without removing existing ones',
                ],
                'fields' => [
                    'id_product' => 'Product id (required)',
                    'faqs' => 'Array of {question, answer, active} (PUT, or POST for multiple)',
                    'question' => 'Question text (POST single)',
                    'answer' => 'Answer text (POST single)',
                    'active' => '1 or 0, default 1',
                ],
                'notes' => [
                    'PUT replaces all FAQ entries; POST adds without deleting',
                    'POST accepts either a single {question,answer} or {faqs:[...]}',
                ],
                'example_get' => '/api/product_faq?id_product=35',
                'example_put_json' => '{"id_product":35,"faqs":[{"question":"Quelle garantie ?","answer":"2 ans","active":1}]}',
                'example_post_json' => '{"id_product":35,"question":"Quel délai ?","answer":"48h","active":1}',
            ];
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 809);
        $faqs = $this->loadFaqs($idProduct);

        return [
            'action' => 'list',
            'id_product' => $idProduct,
            'count' => count($faqs),
            'faqs' => $faqs,
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
            throw new WebserviceException('id_product is required', [801, 400]);
        }
        if (!array_key_exists('faqs', $input)) {
            throw new WebserviceException('faqs array is required', [802, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 809);
        $rows = $this->validateFaqList($input['faqs']);

        $db = Db::getInstance();
        $db->delete('product_faq', '`id_product` = ' . (int) $idProduct);

        foreach ($rows as $row) {
            $db->insert('product_faq', [
                'id_product' => (int) $idProduct,
                'question' => pSQL($row['question'], true),
                'answer' => pSQL($row['answer'], true),
                'active' => (int) $row['active'],
            ]);
        }

        $faqs = $this->loadFaqs($idProduct);

        return [
            'action' => 'replace',
            'status' => 'ok',
            'id_product' => $idProduct,
            'count' => count($faqs),
            'faqs' => $faqs,
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
            throw new WebserviceException('id_product is required', [801, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 809);

        if (!empty($input['faqs'])) {
            $rows = $this->validateFaqList($input['faqs']);
        } elseif (!empty($input['question'])) {
            $rows = [$this->validateFaqEntry($input)];
        } else {
            throw new WebserviceException(
                'Provide either "faqs" array or "question"+"answer" fields',
                [803, 400]
            );
        }

        $db = Db::getInstance();
        foreach ($rows as $row) {
            $db->insert('product_faq', [
                'id_product' => (int) $idProduct,
                'question' => pSQL($row['question'], true),
                'answer' => pSQL($row['answer'], true),
                'active' => (int) $row['active'],
            ]);
        }

        $faqs = $this->loadFaqs($idProduct);

        return [
            'action' => 'add',
            'status' => 'ok',
            'id_product' => $idProduct,
            'added' => count($rows),
            'count' => count($faqs),
            'faqs' => $faqs,
        ];
    }

    /**
     * @param array $list
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function validateFaqList($list)
    {
        if (!is_array($list)) {
            throw new WebserviceException('faqs must be an array', [804, 400]);
        }

        $result = [];
        foreach ($list as $i => $entry) {
            if (!is_array($entry)) {
                throw new WebserviceException('faqs[' . $i . '] must be an object', [805, 400]);
            }
            $result[] = $this->validateFaqEntry($entry, $i);
        }

        return $result;
    }

    /**
     * @param array    $entry
     * @param int|null $index
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function validateFaqEntry($entry, $index = null)
    {
        $prefix = $index !== null ? 'faqs[' . $index . '].' : '';

        if (empty($entry['question'])) {
            throw new WebserviceException($prefix . 'question is required', [806, 400]);
        }
        if (!isset($entry['answer'])) {
            throw new WebserviceException($prefix . 'answer is required', [807, 400]);
        }

        return [
            'question' => (string) $entry['question'],
            'answer' => (string) $entry['answer'],
            'active' => isset($entry['active']) ? (int) $entry['active'] : 1,
        ];
    }

    /**
     * @param int $idProduct
     *
     * @return array
     */
    protected function loadFaqs($idProduct)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id`, `question`, `answer`, `active`
             FROM `' . _DB_PREFIX_ . 'product_faq`
             WHERE `id_product` = ' . (int) $idProduct . '
             ORDER BY `id` ASC'
        );

        if (!$rows) {
            return [];
        }

        return array_map(function ($r) {
            return [
                'id' => (int) $r['id'],
                'question' => $r['question'],
                'answer' => $r['answer'],
                'active' => (int) $r['active'],
            ];
        }, $rows);
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_product', 'id', 'faqs', 'question', 'answer', 'active'],
            ['product_faq']
        );
    }
}
