<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
class Image extends ImageCore
{
    public function add($autoDate = true, $nullValues = false)
    {
        $db = Db::getInstance();
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'image` WHERE `id_image` = 0');
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'image_shop` WHERE `id_image` = 0');
        $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'image_lang` WHERE `id_image` = 0');

        try {
            $result = parent::add($autoDate, $nullValues);
        } catch (\Throwable $e) {
            $result = false;
        }

        if (empty($this->id) || (int)$this->id === 0) {
            $realId = (int)$db->getValue(
                'SELECT MAX(`id_image`) FROM `' . _DB_PREFIX_ . 'image` WHERE `id_image` > 0'
            );
            if ($realId > 0) {
                $this->id = $realId;
                $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'image_shop` WHERE `id_image` = 0');
                $db->insert('image_shop', [
                    'id_image' => $realId,
                    'id_shop' => (int)Context::getContext()->shop->id,
                    'cover' => $this->cover ? 1 : 0,
                ], false, true, Db::INSERT_IGNORE);
                return true;
            }
            return false;
        }

        return $result;
    }
}
