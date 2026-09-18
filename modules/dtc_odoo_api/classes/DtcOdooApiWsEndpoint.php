<?php
/**
 * Base commune des endpoints webservice dtc_odoo_api.
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

abstract class DtcOdooApiWsEndpoint implements WebserviceSpecificManagementInterface
{
    /** @var WebserviceOutputBuilder */
    protected $objOutput;

    /** @var WebserviceRequest */
    protected $wsObject;

    /** @var array|null */
    protected $payload;

    /**
     * Noeud racine JSON/XML (ex. shop_unlink).
     *
     * @return string
     */
    abstract protected function getRootNode();

    public function setObjectOutput(WebserviceOutputBuilder $obj)
    {
        $this->objOutput = $obj;

        return $this;
    }

    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    public function setWsObject(WebserviceRequest $obj)
    {
        $this->wsObject = $obj;

        return $this;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $data = $this->payload !== null ? $this->payload : [];
        $root = $this->getRootNode();

        if ($this->isJsonOutput()) {
            return json_encode([$root => $data], JSON_UNESCAPED_UNICODE);
        }

        return $this->objOutput->getObjectRender()->overrideContent(
            $this->arrayToXml($root, $data)
        );
    }

    /**
     * @return bool
     */
    protected function isJsonOutput()
    {
        if (!empty($this->wsObject->urlFragments['output_format'])
            && Tools::strtolower($this->wsObject->urlFragments['output_format']) === 'json'
        ) {
            return true;
        }

        return $this->objOutput->getObjectRender() instanceof WebserviceOutputJSON;
    }

    /**
     * @param string $resource
     * @param int $code
     *
     * @throws WebserviceException
     */
    protected function methodNotAllowed($resource, $code)
    {
        throw new WebserviceException(
            'Method ' . Tools::strtoupper($this->wsObject->method) . ' is not allowed for ' . $resource,
            [$code, 405]
        );
    }

    /**
     * Fusionne query string, segments URL et body JSON/XML.
     *
     * @param string[] $keys
     * @param string[] $wrappers
     * @param string[] $segmentMap index segment => clé (1-based urlSegment)
     *
     * @return array
     */
    protected function extractInput(array $keys, array $wrappers = [], array $segmentMap = [])
    {
        $params = [];

        foreach ($keys as $key) {
            if (isset($this->wsObject->urlFragments[$key]) && $this->wsObject->urlFragments[$key] !== '') {
                $params[$key] = $this->wsObject->urlFragments[$key];
            }
        }

        $segments = $this->wsObject->urlSegment;
        foreach ($segmentMap as $index => $key) {
            if (!isset($params[$key]) && !empty($segments[$index]) && $segments[$index] !== '') {
                $params[$key] = $segments[$index];
            }
        }

        $body = $this->getInputBody();
        if ($body === '') {
            return $params;
        }

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            foreach ($wrappers as $wrapper) {
                if (isset($decoded[$wrapper]) && is_array($decoded[$wrapper])) {
                    $decoded = $decoded[$wrapper];
                    break;
                }
            }

            return array_merge($params, $decoded);
        }

        return array_merge($params, $this->xmlToArray($body, $wrappers));
    }

    /**
     * @return string
     */
    protected function getInputBody()
    {
        $ws = $this->wsObject;
        $read = Closure::bind(function () {
            return isset($this->_inputXml) ? trim((string) $this->_inputXml) : '';
        }, $ws, $ws);

        return $read ? $read() : '';
    }

    /**
     * @param array $input
     * @param string[] $keys
     * @param int $segmentIndex
     *
     * @return int
     */
    protected function resolveInt(array $input, array $keys, $segmentIndex = 1)
    {
        foreach ($keys as $key) {
            if (!empty($input[$key])) {
                return (int) $input[$key];
            }
        }

        $segments = $this->wsObject->urlSegment;
        if (isset($segments[$segmentIndex]) && $segments[$segmentIndex] !== '' && ctype_digit((string) $segments[$segmentIndex])) {
            return (int) $segments[$segmentIndex];
        }

        return 0;
    }

    /**
     * @param mixed $value
     *
     * @return int[]
     */
    protected function normalizeIdList($value)
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }
        if (!is_array($value)) {
            $id = (int) $value;

            return $id > 0 ? [$id] : [];
        }

        // Déballe les wrappers XML courants : <attachments><attachment><id>…</id></attachment></attachments>
        $wrapperKeys = [
            'attachment', 'attachments', 'item', 'items',
            'product', 'products', 'carrier', 'carriers',
            'group', 'groups', 'label', 'labels',
        ];
        $keys = array_keys($value);
        if (count($keys) === 1 && in_array($keys[0], $wrapperKeys, true) && !isset($value['id'])) {
            $inner = $value[$keys[0]];
            if (is_array($inner) && isset($inner['id']) && !$this->isIndexedList($inner)) {
                return $this->normalizeIdList([$inner]);
            }

            return $this->normalizeIdList($inner);
        }

        // Objet unique {id: X} (un seul nœud XML)
        if (isset($value['id']) && !$this->isIndexedList($value)) {
            $id = (int) $value['id'];

            return $id > 0 ? [$id] : [];
        }

        $ids = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                if (isset($item['id'])) {
                    $item = $item['id'];
                } elseif (isset($item['id_attachment'])) {
                    $item = $item['id_attachment'];
                } elseif (isset($item['item'])) {
                    foreach ($this->normalizeIdList($item['item']) as $nestedId) {
                        $ids[$nestedId] = $nestedId;
                    }
                    continue;
                } else {
                    foreach ($this->normalizeIdList($item) as $nestedId) {
                        $ids[$nestedId] = $nestedId;
                    }
                    continue;
                }
            }
            if (is_array($item)) {
                continue;
            }
            $id = (int) $item;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param array $value
     *
     * @return bool
     */
    protected function isIndexedList(array $value)
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * @param string $table
     * @param string $primary
     * @param int $id
     *
     * @return bool
     */
    protected function rowExists($table, $primary, $id)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . bqSQL($table) . '` WHERE `' . bqSQL($primary) . '` = ' . (int) $id
        );
    }

    /**
     * @param string $table
     * @param string $primary
     * @param int $id
     * @param string $label
     * @param int $code
     *
     * @throws WebserviceException
     */
    protected function assertRowExists($table, $primary, $id, $label, $code)
    {
        if (!$this->rowExists($table, $primary, $id)) {
            throw new WebserviceException($label . ' ' . (int) $id . ' does not exist', [$code, 404]);
        }
    }

    /**
     * Convertit un id_carrier API vers id_reference (stocké par PS).
     *
     * @param int $idCarrier
     *
     * @return int
     */
    protected function toCarrierReference($idCarrier)
    {
        $idCarrier = (int) $idCarrier;
        $ref = (int) Db::getInstance()->getValue(
            'SELECT `id_reference` FROM `' . _DB_PREFIX_ . 'carrier` WHERE `id_carrier` = ' . $idCarrier
        );
        if ($ref) {
            return $ref;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT `id_reference` FROM `' . _DB_PREFIX_ . 'carrier`
             WHERE `id_reference` = ' . $idCarrier . ' AND `deleted` = 0'
        );
    }

    /**
     * @param mixed $ids
     * @param int $errorCode
     *
     * @return int[]
     *
     * @throws WebserviceException
     */
    protected function toCarrierReferences($ids, $errorCode = 307)
    {
        $references = [];
        foreach ($this->normalizeIdList($ids) as $carrierId) {
            $ref = $this->toCarrierReference($carrierId);
            if ($ref <= 0) {
                throw new WebserviceException(
                    'Carrier ' . (int) $carrierId . ' does not exist',
                    [$errorCode, 404]
                );
            }
            $references[$ref] = $ref;
        }

        return array_values($references);
    }

    /**
     * @param string $body
     * @param string[] $wrappers
     *
     * @return array
     */
    protected function xmlToArray($body, array $wrappers = [])
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if ($xml === false) {
            return [];
        }

        $node = $xml;
        foreach ($wrappers as $wrapper) {
            if (isset($xml->{$wrapper})) {
                $node = $xml->{$wrapper};
                break;
            }
        }

        return $this->simpleXmlToArray($node);
    }

    /**
     * @param SimpleXMLElement $xml
     *
     * @return array
     */
    protected function simpleXmlToArray($xml)
    {
        $out = [];
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            if ($child->count()) {
                $value = $this->simpleXmlToArray($child);
                if (isset($value['item']) && count($child->children()) === count($child->item)) {
                    $value = $value['item'];
                    if (!is_array($value) || !isset($value[0])) {
                        $value = [$value];
                    }
                }
            } else {
                $value = (string) $child;
            }
            if (isset($out[$name])) {
                if (!isset($out[$name][0]) || !is_array($out[$name]) || !array_key_exists(0, $out[$name])) {
                    $out[$name] = [$out[$name]];
                }
                $out[$name][] = $value;
            } else {
                $out[$name] = $value;
            }
        }

        return $out;
    }

    /**
     * @param string $root
     * @param array $data
     * @param int $depth
     *
     * @return string
     */
    protected function arrayToXml($root, $data, $depth = 0)
    {
        $indent = str_repeat('  ', $depth);
        $xml = $indent . '<' . $root . '>' . "\n";

        foreach ($data as $key => $value) {
            $tag = is_int($key) ? 'item' : preg_replace('/[^a-z0-9_]/i', '', (string) $key);
            if ($tag === '') {
                $tag = 'item';
            }

            if (is_array($value)) {
                $isList = $value === [] || array_keys($value) === range(0, count($value) - 1);
                if ($isList && $this->isScalarList($value)) {
                    $xml .= $indent . '  <' . $tag . '>' . "\n";
                    foreach ($value as $item) {
                        $xml .= $indent . '    <item><![CDATA[' . $this->xmlEscape((string) $item) . ']]></item>' . "\n";
                    }
                    $xml .= $indent . '  </' . $tag . '>' . "\n";
                } else {
                    $xml .= $this->arrayToXml($tag, $value, $depth + 1);
                }
            } else {
                $xml .= $indent . '  <' . $tag . '><![CDATA[' . $this->xmlEscape((string) $value) . ']]></' . $tag . '>' . "\n";
            }
        }

        $xml .= $indent . '</' . $root . '>' . "\n";

        return $xml;
    }

    /**
     * @param array $list
     *
     * @return bool
     */
    protected function isScalarList($list)
    {
        foreach ($list as $item) {
            if (is_array($item) || is_object($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function xmlEscape($value)
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $value);
    }
}
