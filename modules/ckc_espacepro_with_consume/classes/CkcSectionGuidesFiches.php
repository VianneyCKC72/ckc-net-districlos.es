<?php

class CkcSectionGuidesFiches extends ObjectModel
{
    public $id_section;
    public $id_category;
    public $title_section;
    public $ordering;
    public $active;
    public $created_at;
    public $updated_at;

    public static $definition = [
        'table' => 'ckc_sections_guides_fiches',
        'primary' => 'id_section',
        'fields' => [
            'id_category'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'title_section' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'ordering'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'active'        => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'created_at'    => ['type' => self::TYPE_DATE],
            'updated_at'    => ['type' => self::TYPE_DATE],
        ],
    ];

    public static function getSections()
    {
          return Db::getInstance()->executeS('
                    SELECT * FROM `'._DB_PREFIX_.'ckc_sections_guides_fiches`
                    ORDER BY ordering ASC
                ');
    }

    public static function getCategories()
    {
        $categories = Category::getCategories((int)Context::getContext()->language->id, true, false);
        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category['id_category'],
                'name' => $category['name'],
            ];
        }

        return $result;
    }


}
