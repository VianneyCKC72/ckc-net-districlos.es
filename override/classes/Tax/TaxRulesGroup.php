<?php

class TaxRulesGroup extends TaxRulesGroupCore
{
    /** @var array<string, array|false> */
    private static $cache = [];

    public static function getTaxRulesGroups($only_active = true)
    {
        $key = 'groups_' . (int) $only_active;
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = parent::getTaxRulesGroups($only_active);
        }

        return self::$cache[$key];
    }

    public static function getTaxRulesGroupsForOptions()
    {
        $key = 'options';
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = parent::getTaxRulesGroupsForOptions();
        }

        return self::$cache[$key];
    }
}
