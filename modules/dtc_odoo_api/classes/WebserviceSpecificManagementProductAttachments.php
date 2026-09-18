<?php
/**
 * Endpoint webservice : documents joints d'un produit.
 *
 * GET    /api/product_attachments?id_product=X
 * PUT    /api/product_attachments  → remplace la liste
 * POST   /api/product_attachments  → ajoute (ids et/ou fichier SFTP)
 * DELETE /api/product_attachments  → détache un id_attachment d'un produit
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebserviceSpecificManagementProductAttachments extends DtcOdooApiWsEndpoint
{
    /**
     * @return string
     */
    protected function getRootNode()
    {
        return 'product_attachment';
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
        if ($method === 'DELETE') {
            $this->payload = $this->handleDelete();

            return;
        }

        $this->methodNotAllowed('product_attachments', 600);
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
                'description' => 'Read/write product attachments (product_attachment table). POST can register a file already dropped via SFTP.',
                'methods' => [
                    'GET' => 'Return attachments for id_product',
                    'PUT' => 'Replace the full attachments list (empty list = unlink all)',
                    'POST' => 'Add attachments (ids and/or SFTP file_name) without removing existing ones',
                    'DELETE' => 'Unlink one attachment from a product (does not delete the attachment file)',
                ],
                'fields' => [
                    'id_product' => 'Product id (required)',
                    'id_attachment' => 'Attachment id (DELETE)',
                    'attachments' => 'id_attachment list from /api/attachments',
                    'file_name' => 'Original filename already present on the server (POST)',
                    'name' => 'Optional display name when creating from file_name (max 32 chars)',
                    'description' => 'Optional description when creating from file_name (POST create only; updates go through /api/attachments)',
                ],
                'notes' => [
                    'Incoming SFTP directory: download/sftp/ then download/',
                    'POST adds ; PUT replaces the whole list ; DELETE unlinks one id_attachment',
                    'If an attachment with the same file_name already exists, POST only creates the product link (name/description are ignored)',
                    'GET returns id, file_name, name, description, mime, file_size',
                ],
                'example_get' => '/api/product_attachments?id_product=416',
                'example_put_json' => '{"id_product":416,"attachments":[362,410]}',
                'example_put_xml' => '<product_attachment><id_product>416</id_product><attachments><item><id>362</id></item><item><id>410</id></item></attachments></product_attachment>',
                'example_put_xml_alt' => '<product_attachment><id_product>416</id_product><attachments><attachment><id>362</id></attachment><attachment><id>410</id></attachment></attachments></product_attachment>',
                'example_post_json' => '{"id_product":416,"file_name":"FT-portail-xyz.pdf","name":"Fiche technique","description":"Notice PDF"}',
                'example_delete' => '/api/product_attachments?id_product=416&id_attachment=362',
            ];
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 609);

        return [
            'action' => 'list',
            'id_product' => $idProduct,
            'attachments' => $this->getAttachments($idProduct),
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
            throw new WebserviceException('id_product is required', [601, 400]);
        }
        if (!array_key_exists('attachments', $input)) {
            throw new WebserviceException('attachments field is required (use empty array to unlink all)', [602, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 609);
        $ids = $this->normalizeIdList($input['attachments']);
        $this->assertAttachmentsExist($ids);
        Attachment::attachToProduct($idProduct, $ids);

        return [
            'action' => 'replace',
            'status' => 'ok',
            'id_product' => $idProduct,
            'attachments' => $this->getAttachments($idProduct),
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
            throw new WebserviceException('id_product is required', [601, 400]);
        }

        $hasIds = array_key_exists('attachments', $input);
        $hasFile = !empty($input['file_name']);
        if (!$hasIds && !$hasFile) {
            throw new WebserviceException('POST requires "attachments" and/or "file_name"', [603, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 609);

        $linked = [];
        $createdFromFile = false;

        if ($hasIds) {
            $ids = $this->normalizeIdList($input['attachments']);
            $this->assertAttachmentsExist($ids);
            foreach ($ids as $idAttachment) {
                if ($this->linkProductAttachment($idProduct, $idAttachment)) {
                    $linked[] = $idAttachment;
                }
            }
        }

        if ($hasFile) {
            $fileName = $this->sanitizeFileName($input['file_name']);
            $displayName = isset($input['name']) ? trim((string) $input['name']) : '';
            $description = isset($input['description']) ? trim((string) $input['description']) : '';
            $resolved = $this->resolveOrCreateFromFile($fileName, $displayName, $description);
            if ($this->linkProductAttachment($idProduct, $resolved['id'])) {
                $linked[] = $resolved['id'];
            }
            $createdFromFile = $resolved['created'];
        }

        return [
            'action' => 'add',
            'status' => 'ok',
            'id_product' => $idProduct,
            'linked' => array_values(array_unique($linked)),
            'created_from_file' => $createdFromFile,
            'attachments' => $this->getAttachments($idProduct),
        ];
    }

    /**
     * Détache un document d'un produit (ne supprime pas le fichier Attachment).
     *
     * @return array
     *
     * @throws WebserviceException
     */
    protected function handleDelete()
    {
        $input = $this->getParams();
        $idProduct = $this->resolveInt($input, ['id_product', 'id']);
        $idAttachment = $this->resolveInt($input, ['id_attachment']);

        if ($idAttachment <= 0 && array_key_exists('attachments', $input)) {
            $ids = $this->normalizeIdList($input['attachments']);
            if (count($ids) === 1) {
                $idAttachment = $ids[0];
            }
        }

        if ($idProduct <= 0) {
            throw new WebserviceException('id_product is required for DELETE', [611, 400]);
        }
        if ($idAttachment <= 0) {
            throw new WebserviceException('id_attachment is required for DELETE', [612, 400]);
        }

        $this->assertRowExists('product', 'id_product', $idProduct, 'Product', 609);

        $existed = (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'product_attachment`
             WHERE `id_product` = ' . (int) $idProduct . '
               AND `id_attachment` = ' . (int) $idAttachment
        );

        if ($existed) {
            Db::getInstance()->execute(
                'DELETE FROM `' . _DB_PREFIX_ . 'product_attachment`
                 WHERE `id_product` = ' . (int) $idProduct . '
                   AND `id_attachment` = ' . (int) $idAttachment
            );
            Product::updateCacheAttachment((int) $idProduct);
        }

        return [
            'action' => 'delete',
            'status' => 'ok',
            'id_product' => $idProduct,
            'id_attachment' => $idAttachment,
            'unlinked' => $existed,
            'attachments' => $this->getAttachments($idProduct),
        ];
    }

    /**
     * @param int $idProduct
     *
     * @return array
     */
    protected function getAttachments($idProduct)
    {
        $rows = Product::getAttachmentsStatic((int) Context::getContext()->language->id, (int) $idProduct);
        if (!$rows) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row['id_attachment'],
                'file_name' => (string) $row['file_name'],
                'name' => (string) $row['name'],
                'description' => isset($row['description']) ? (string) $row['description'] : '',
                'mime' => (string) $row['mime'],
                'file_size' => (int) $row['file_size'],
            ];
        }

        return $out;
    }

    /**
     * @param int $idProduct
     * @param int $idAttachment
     *
     * @return bool
     */
    protected function linkProductAttachment($idProduct, $idAttachment)
    {
        $exists = (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'product_attachment`
             WHERE `id_product` = ' . (int) $idProduct . '
               AND `id_attachment` = ' . (int) $idAttachment
        );
        if ($exists) {
            return false;
        }

        return Attachment::associateProductAttachment((int) $idProduct, (int) $idAttachment);
    }

    /**
     * @param string $fileName
     * @param string $displayName
     * @param string $description
     *
     * @return array{id:int, created:bool}
     *
     * @throws WebserviceException
     */
    protected function resolveOrCreateFromFile($fileName, $displayName, $description = '')
    {
        $existingId = (int) Db::getInstance()->getValue(
            'SELECT `id_attachment` FROM `' . _DB_PREFIX_ . 'attachment`
             WHERE `file_name` = \'' . pSQL($fileName) . '\'
             ORDER BY `id_attachment` ASC'
        );
        if ($existingId > 0) {
            // Fichier déjà connu : lien seul. MAJ name/description → /api/attachments
            return ['id' => $existingId, 'created' => false];
        }

        $sourcePath = $this->findIncomingFile($fileName);
        if ($sourcePath === null) {
            throw new WebserviceException(
                'File "' . $fileName . '" not found in download/sftp or download/',
                [604, 404]
            );
        }

        $hash = sha1(uniqid((string) mt_rand(), true));
        $destPath = rtrim(_PS_DOWNLOAD_DIR_, '/\\') . DIRECTORY_SEPARATOR . $hash;
        if (!copy($sourcePath, $destPath)) {
            throw new WebserviceException('Failed to copy "' . $fileName . '" into download/', [605, 500]);
        }

        $mime = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($destPath);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }

        $name = $displayName !== '' ? $displayName : pathinfo($fileName, PATHINFO_FILENAME);
        $name = Tools::substr($name, 0, 32);
        if ($name === '' || $name === false) {
            $name = 'Attachment';
        }

        $localizedName = [];
        $localizedDescription = [];
        foreach (Language::getIDs(false) as $idLang) {
            $idLang = (int) $idLang;
            $localizedName[$idLang] = $name;
            $localizedDescription[$idLang] = $description;
        }

        $attachment = new Attachment();
        $attachment->file = $hash;
        $attachment->file_name = $fileName;
        $attachment->mime = $mime;
        $attachment->file_size = (int) filesize($destPath);
        $attachment->name = $localizedName;
        $attachment->description = $localizedDescription;

        if (!$attachment->add()) {
            @unlink($destPath);
            throw new WebserviceException('Failed to create attachment record', [606, 500]);
        }

        return ['id' => (int) $attachment->id, 'created' => true];
    }

    /**
     * @param string $fileName
     *
     * @return string|null
     */
    protected function findIncomingFile($fileName)
    {
        $base = rtrim(_PS_DOWNLOAD_DIR_, '/\\');
        foreach ([$base . DIRECTORY_SEPARATOR . 'sftp' . DIRECTORY_SEPARATOR . $fileName, $base . DIRECTORY_SEPARATOR . $fileName] as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws WebserviceException
     */
    protected function sanitizeFileName($value)
    {
        $fileName = basename(str_replace('\\', '/', (string) $value));
        if ($fileName === '' || $fileName === '.' || $fileName === '..' || !Validate::isGenericName($fileName)) {
            throw new WebserviceException('Invalid file_name', [607, 400]);
        }

        return $fileName;
    }

    /**
     * @param int[] $ids
     *
     * @throws WebserviceException
     */
    protected function assertAttachmentsExist(array $ids)
    {
        foreach ($ids as $id) {
            $this->assertRowExists('attachment', 'id_attachment', $id, 'Attachment', 608);
        }
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->extractInput(
            ['id_product', 'id', 'id_attachment', 'attachments', 'file_name', 'name', 'description'],
            ['product_attachment', 'product_attachments']
        );
    }
}
