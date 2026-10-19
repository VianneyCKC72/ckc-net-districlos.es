<?php
/**
 * Extension webservice Customer : KBIS (fileUpload), RIB (rib), Code NAF (naf).
 *
 * @author    DISTRICLOS
 * @copyright DISTRICLOS
 * @license   AFL-3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Customer extends CustomerCore
{
    /** @var string|null Nom fichier KBIS */
    public $fileUpload;

    /** @var string|null Nom fichier RIB */
    public $rib;

    /** @var string|null Code NAF (espace pro) */
    public $naf;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $columns = self::dtcOdooGetCustomerColumns();

        if (in_array('fileUpload', $columns, true)) {
            self::$definition['fields']['fileUpload'] = [
                'type' => self::TYPE_STRING,
                'required' => false,
                'size' => 255,
                'allow_null' => true,
            ];
        }
        if (in_array('rib', $columns, true)) {
            self::$definition['fields']['rib'] = [
                'type' => self::TYPE_STRING,
                'required' => false,
                'size' => 255,
                'allow_null' => true,
            ];
        }
        if (in_array('naf', $columns, true)) {
            self::$definition['fields']['naf'] = [
                'type' => self::TYPE_STRING,
                'required' => false,
                'size' => 16,
                'allow_null' => true,
            ];
        }

        parent::__construct($id);
    }

    /**
     * @return string[]
     */
    protected static function dtcOdooGetCustomerColumns()
    {
        static $names = null;
        if ($names !== null) {
            return $names;
        }

        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'customer`');
        $names = [];
        if ($columns) {
            foreach ($columns as $column) {
                if (isset($column['Field'])) {
                    $names[] = $column['Field'];
                }
            }
        }

        return $names;
    }
}
