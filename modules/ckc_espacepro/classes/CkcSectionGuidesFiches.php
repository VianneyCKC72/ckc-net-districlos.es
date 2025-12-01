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
        $id_lang = (int)Context::getContext()->language->id;

        $sql = 'SELECT
                c.id_category,
                cl.name,
                CONCAT(
                    IFNULL(
                        (
                            SELECT GROUP_CONCAT(cl2.name ORDER BY c2.level_depth SEPARATOR " > ")
                            FROM '._DB_PREFIX_.'category c2
                            INNER JOIN '._DB_PREFIX_.'category_lang cl2
                                ON (cl2.id_category = c2.id_category AND cl2.id_lang = '.(int)$id_lang.')
                            WHERE c.nleft > c2.nleft 
                                AND c.nright < c2.nright
                                AND c2.id_category > 2
                        ),
                        ""
                    ),
                    CASE 
                        WHEN EXISTS(
                            SELECT 1 
                            FROM '._DB_PREFIX_.'category c3 
                            WHERE c.nleft > c3.nleft 
                                AND c.nright < c3.nright
                                AND c3.id_category > 2
                        )
                        THEN CONCAT(" > ", cl.name)
                        ELSE cl.name
                    END
                ) AS full_path
            FROM '._DB_PREFIX_.'category c
            INNER JOIN '._DB_PREFIX_.'category_lang cl
                ON (cl.id_category = c.id_category AND cl.id_lang = '.(int)$id_lang.')
            WHERE c.active = 1
                AND c.id_category > 2
            GROUP BY c.id_category
            ORDER BY full_path ASC';

        $categories = Db::getInstance()->executeS($sql);

        return $categories;
    }


}
