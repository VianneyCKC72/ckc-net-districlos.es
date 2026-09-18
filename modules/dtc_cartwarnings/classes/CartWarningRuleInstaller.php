<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartWarningRuleInstaller
{
    public static function seedDefaultRules()
    {
        if ((int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'dtc_cart_warning_rule`'
        ) > 0) {
            return true;
        }

        $idLangFr = (int) Language::getIdByIso('fr');
        $idLangEn = (int) Language::getIdByIso('en');
        if ($idLangFr <= 0) {
            $idLangFr = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        $messages = self::getDefaultMessages();

        $position = 0;
        foreach ($messages['incompatibilities'] as $ruleData) {
            self::createIncompatibilityRule($ruleData, $idLangFr, $idLangEn, $position++);
        }

        foreach ($messages['missing_simple'] as $ruleData) {
            self::createMissingSimpleRule($ruleData, $idLangFr, $idLangEn, $position++);
        }

        foreach ($messages['missing_multiple'] as $ruleData) {
            self::createMissingMultipleRule($ruleData, $idLangFr, $idLangEn, $position++);
        }

        return true;
    }

    private static function createIncompatibilityRule(array $ruleData, $idLangFr, $idLangEn, $position)
    {
        $rule = new CartWarningRule();
        $rule->id_parent = null;
        $rule->rule_type = CartWarningRule::TYPE_INCOMPATIBILITY;
        $rule->active = true;
        $rule->position = $position;
        $rule->attribute_product_id = !empty($ruleData['attribute_product']) ? (int) $ruleData['attribute_product'] : null;
        $rule->attribute_contains = !empty($ruleData['attribute_contains']) ? $ruleData['attribute_contains'] : null;
        $rule->message_main = self::buildLangField($ruleData['message'], $idLangFr, $idLangEn);
        $rule->message_extra = [];

        if (!$rule->add()) {
            return false;
        }

        CartWarningRule::saveProductIdsByRole((int) $rule->id, [
            CartWarningRule::ROLE_ALL_OF => isset($ruleData['all_of']) ? $ruleData['all_of'] : [],
            CartWarningRule::ROLE_ANY_OF => isset($ruleData['any_of']) ? $ruleData['any_of'] : [],
            CartWarningRule::ROLE_MESSAGE_ON => isset($ruleData['message_on']) ? $ruleData['message_on'] : [],
        ]);

        return true;
    }

    private static function createMissingSimpleRule(array $ruleData, $idLangFr, $idLangEn, $position)
    {
        $rule = new CartWarningRule();
        $rule->id_parent = null;
        $rule->rule_type = CartWarningRule::TYPE_MISSING_SIMPLE;
        $rule->active = true;
        $rule->position = $position;
        $rule->missing_product_id = (int) $ruleData['missing_product'];
        $rule->message_main = self::buildLangField($ruleData['message_main'], $idLangFr, $idLangEn);
        $rule->message_extra = self::buildLangField(
            isset($ruleData['message_extra']) ? $ruleData['message_extra'] : ['fr' => '', 'en' => ''],
            $idLangFr,
            $idLangEn,
            true
        );

        if (!$rule->add()) {
            return false;
        }

        CartWarningRule::saveProductIdsByRole((int) $rule->id, [
            CartWarningRule::ROLE_TRIGGER => $ruleData['trigger_products'],
        ]);

        return true;
    }

    private static function createMissingMultipleRule(array $ruleData, $idLangFr, $idLangEn, $position)
    {
        $rule = new CartWarningRule();
        $rule->id_parent = null;
        $rule->rule_type = CartWarningRule::TYPE_MISSING_MULTIPLE;
        $rule->active = true;
        $rule->position = $position;
        $rule->message_main = self::buildLangField(['fr' => '-', 'en' => '-'], $idLangFr, $idLangEn);
        $rule->message_extra = [];

        if (!$rule->add()) {
            return false;
        }

        CartWarningRule::saveProductIdsByRole((int) $rule->id, [
            CartWarningRule::ROLE_TRIGGER => [$ruleData['trigger_product']],
        ]);

        $childPosition = 0;
        foreach ($ruleData['missing_products'] as $childData) {
            $child = new CartWarningRule();
            $child->id_parent = (int) $rule->id;
            $child->rule_type = CartWarningRule::TYPE_MISSING_ITEM;
            $child->active = true;
            $child->position = $childPosition++;
            $child->missing_product_id = (int) $childData['id'];
            $child->message_main = self::buildLangField($childData['message_main'], $idLangFr, $idLangEn);
            $child->message_extra = self::buildLangField(
                isset($childData['message_extra']) ? $childData['message_extra'] : ['fr' => '', 'en' => ''],
                $idLangFr,
                $idLangEn,
                true
            );
            $child->add();
        }

        return true;
    }

    /**
     * @param array{fr: string, en: string} $texts
     * @param int $idLangFr
     * @param int $idLangEn
     * @param bool $allowEmpty
     *
     * @return array<int, string>
     */
    private static function buildLangField(array $texts, $idLangFr, $idLangEn, $allowEmpty = false)
    {
        $field = [];
        $fr = isset($texts['fr']) ? $texts['fr'] : '';
        $en = isset($texts['en']) ? $texts['en'] : $fr;

        if ($fr !== '' || $allowEmpty) {
            $field[$idLangFr] = $fr;
        }
        if ($idLangEn > 0 && ($en !== '' || $allowEmpty)) {
            $field[$idLangEn] = $en;
        }

        return $field;
    }

    /**
     * @return array<string, array<int, array>>
     */
    private static function getDefaultMessages()
    {
        return [
            'incompatibilities' => [
                [
                    'all_of' => [1182],
                    'any_of' => [1129],
                    'message_on' => [1182],
                    'message' => [
                        'fr' => 'Attention : Les platines choisies ne sont pas compatibles avec le portillon Jardi+.',
                        'en' => 'Warning: The selected base plates are not compatible with the Jardi+ pedestrian gate.',
                    ],
                ],
                [
                    'all_of' => [1213],
                    'any_of' => [332, 2321],
                    'message_on' => [1213],
                    'message' => [
                        'fr' => 'Attention : Les platines choisies ne sont pas compatibles avec le portillon Garden.',
                        'en' => 'Warning: The selected base plates are not compatible with the Garden pedestrian gate.',
                    ],
                ],
                [
                    'all_of' => [1302, 416],
                    'attribute_product' => 416,
                    'attribute_contains' => 'encoches',
                    'message_on' => [1302],
                    'message' => [
                        'fr' => 'Attention : Les poteaux à clip ne sont pas compatibles avec les soubassements à encoche',
                        'en' => 'Warning: Clip posts are not compatible with slotted base panels',
                    ],
                ],
                [
                    'any_of' => [1223, 1285],
                    'all_of' => [416],
                    'attribute_product' => 416,
                    'attribute_contains' => 'clips',
                    'message_on' => [1223, 1285],
                    'message' => [
                        'fr' => 'Attention : Les poteaux à encoche ne sont pas compatibles avec le soubassements à clips',
                        'en' => 'Warning: Slotted posts are not compatible with clip base panels',
                    ],
                ],
            ],
            'missing_simple' => [
                [
                    'trigger_products' => [1305, 1306],
                    'missing_product' => 1130,
                    'message_main' => [
                        'fr' => 'Les crémaillères ne sont pas incluses dans ce moteur.',
                        'en' => 'The racks are not included with this motor.',
                    ],
                    'message_extra' => [
                        'fr' => 'Portail 3m00 : 4 crémaillères - Portail 3m50 et 4m00 : 5 crémaillères',
                        'en' => '3m00 gate: 4 racks - 3m50 and 4m00 gate: 5 racks',
                    ],
                ],
                [
                    'trigger_products' => [1223, 1285],
                    'missing_product' => 1224,
                    'message_main' => [
                        'fr' => 'N\'oubliez pas les bouchons compatibles avec votre poteau.',
                        'en' => 'Don\'t forget the caps compatible with your post.',
                    ],
                ],
                [
                    'trigger_products' => [151],
                    'missing_product' => 422,
                    'message_main' => [
                        'fr' => 'Ce produit nécessite le produit complémentaire suivant.',
                        'en' => 'This product requires the following complementary product.',
                    ],
                ],
            ],
            'missing_multiple' => [
                [
                    'trigger_product' => 1302,
                    'missing_products' => [
                        [
                            'id' => 1303,
                            'message_main' => [
                                'fr' => 'N\'oubliez pas vos clips pour votre poteau.',
                                'en' => 'Don\'t forget your clips for your post.',
                            ],
                        ],
                        [
                            'id' => 1304,
                            'message_main' => [
                                'fr' => 'N\'oubliez pas la pince à clips pour votre poteau.',
                                'en' => 'Don\'t forget the clip pliers for your post.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
