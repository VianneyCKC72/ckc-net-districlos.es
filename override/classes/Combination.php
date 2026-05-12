<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
class Combination extends CombinationCore
{
    public function add($autoDate = true, $nullValues = false)
    {
        $db = Db::getInstance();
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'product_attribute` WHERE `id_product_attribute` = 0');
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'product_attribute_shop` WHERE `id_product_attribute` = 0');
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'product_attribute_combination` WHERE `id_product_attribute` = 0');

        try {
            $result = parent::add($autoDate, $nullValues);
        } catch (\Throwable $e) {
            $result = false;
        }

        if (empty($this->id) || (int)$this->id === 0) {
            $realId = (int)$db->getValue(
                'SELECT MAX(`id_product_attribute`) FROM `' . _DB_PREFIX_ . 'product_attribute`
                 WHERE `id_product_attribute` > 0'
            );
            if ($realId > 0) {
                $this->id = $realId;
                $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'product_attribute_shop` WHERE `id_product_attribute` = 0');
                $db->insert('product_attribute_shop', [
                    'id_product_attribute' => $realId,
                    'id_shop' => (int)Context::getContext()->shop->id,
                ], false, true, Db::INSERT_IGNORE);
                return true;
            }
            return false;
        }

        return $result;
    }
}
