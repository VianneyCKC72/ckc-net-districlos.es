<?php
/**
 * Extension webservice Customer : KBIS (fileUpload) + RIB (rib).
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

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        if (self::dtcOdooHasProDocColumns()) {
            self::$definition['fields']['fileUpload'] = [
                'type' => self::TYPE_STRING,
                'required' => false,
                'size' => 255,
                'allow_null' => true,
            ];
            self::$definition['fields']['rib'] = [
                'type' => self::TYPE_STRING,
                'required' => false,
                'size' => 255,
                'allow_null' => true,
            ];
        }

        parent::__construct($id);
    }

    /**
     * @return bool
     */
    protected static function dtcOdooHasProDocColumns()
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'customer`');
        if (!$columns) {
            $exists = false;

            return false;
        }

        $names = [];
        foreach ($columns as $column) {
            if (isset($column['Field'])) {
                $names[] = $column['Field'];
            }
        }

        $exists = in_array('fileUpload', $names, true) && in_array('rib', $names, true);

        return $exists;
    }
}
