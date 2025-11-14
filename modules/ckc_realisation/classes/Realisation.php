<?php
if (!defined('_PS_VERSION_')) { exit; }

class Realisation extends ObjectModel
{
    public $id_realisation;
    public $id_shop;
    public $id_customer;
    public $customer_type;
    public $id_product;
    public $filename;
    public $mime;
    public $filesize;
    public $keywords;
    public $source;
    public $status;
    public $date_add;
    public $date_upd;
    public $title;
    public $alt;

    public static $definition = [
        'table'     => 'ckc_realisation',
        'primary'   => 'id_realisation',
        'multilang' => true,
        'fields'    => [
            'id_shop'       => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_customer'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'customer_type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16],
            'id_product'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'filename'      => ['type' => self::TYPE_STRING, 'validate' => 'isFileName',  'required' => true, 'size' => 255],
            'mime'          => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 64],
            'filesize'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'keywords'      => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 512],
            'source'        => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'status'        => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 16],
            'date_add'      => ['type' => self::TYPE_DATE],
            'date_upd'      => ['type' => self::TYPE_DATE],

            // Lang
            'title'         => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'alt'           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
        ],
    ];

    public function add($autoDate = true, $nullValues = false)
    {
        if ($autoDate) { $this->date_add = date('Y-m-d H:i:s'); }
        $this->date_upd = null;
        return parent::add($autoDate, $nullValues);
    }

    public function update($nullValues = false)
    {
        $this->date_upd = date('Y-m-d H:i:s');
        return parent::update($nullValues);
    }
}
