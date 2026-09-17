<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcLogoLangHelper
{
    const CONFIG_PREFIX = 'CKC_LOGO_LANG_';
    const ALLOWED_EXT = ['gif', 'jpg', 'jpeg', 'jpe', 'png', 'webp', 'svg'];

    public static function configKey($idLang)
    {
        return self::CONFIG_PREFIX . (int) $idLang;
    }

    public static function getFilename($idLang, $idShop = null)
    {
        $idLang = (int) $idLang;
        if ($idLang <= 0) {
            return '';
        }

        $filename = (string) Configuration::get(
            self::configKey($idLang),
            null,
            null,
            $idShop !== null ? (int) $idShop : null
        );

        if ($filename === '' || !self::fileExists($filename)) {
            return '';
        }

        return $filename;
    }

    public static function getLogoDetails($context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        if (empty($context->language->id)) {
            return [];
        }

        $filename = self::getFilename(
            (int) $context->language->id,
            isset($context->shop->id) ? (int) $context->shop->id : null
        );
        if ($filename === '') {
            return [];
        }

        $width = 178;
        $height = 48;
        $path = _PS_IMG_DIR_ . $filename;
        if (is_file($path)) {
            $size = @getimagesize($path);
            if (is_array($size)) {
                $width = (int) $size[0];
                $height = (int) $size[1];
            }
        }

        $urls = [];
        if (isset($context->controller) && method_exists($context->controller, 'getTemplateVarUrls')) {
            $urls = $context->controller->getTemplateVarUrls();
        }
        $psImageUrl = $urls['img_ps_url'] ?? _PS_IMG_;

        return [
            'src' => $psImageUrl . $filename,
            'width' => $width,
            'height' => $height,
            'filename' => $filename,
        ];
    }

    public static function fileExists($filename)
    {
        $filename = basename((string) $filename);
        if ($filename === '' || strpos($filename, '..') !== false) {
            return false;
        }

        return is_file(_PS_IMG_DIR_ . $filename);
    }

    public static function saveUploads($idShop)
    {
        $errors = [];
        $idShop = (int) $idShop;
        $languages = Language::getLanguages(false, $idShop ?: false);

        foreach ($languages as $language) {
            $idLang = (int) $language['id_lang'];
            $deleteKey = 'ckc_logo_lang_delete_' . $idLang;
            $fileKey = 'ckc_logo_lang_' . $idLang;

            if (Tools::getValue($deleteKey)) {
                Configuration::updateValue(self::configKey($idLang), '');
                continue;
            }

            if (empty($_FILES[$fileKey]['name']) || empty($_FILES[$fileKey]['tmp_name'])) {
                continue;
            }

            $upload = $_FILES[$fileKey];
            if (!empty($upload['error'])) {
                $errors[] = sprintf('Langue %s : fichier invalide.', $language['iso_code']);
                continue;
            }

            $ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, self::ALLOWED_EXT, true)) {
                $errors[] = sprintf('Langue %s : format non autorisé (png, jpg, svg, webp, gif).', $language['iso_code']);
                continue;
            }

            if ($ext !== 'svg') {
                $error = ImageManager::validateUpload($upload, 0);
                if ($error) {
                    $errors[] = sprintf('Langue %s : %s', $language['iso_code'], $error);
                    continue;
                }
            }

            $filename = 'logo_lang_' . $idShop . '_' . $idLang . '_' . time() . '.' . $ext;
            $dest = _PS_IMG_DIR_ . $filename;
            if (!move_uploaded_file($upload['tmp_name'], $dest)) {
                $errors[] = sprintf('Langue %s : impossible d’enregistrer le fichier.', $language['iso_code']);
                continue;
            }

            @chmod($dest, 0644);
            Configuration::updateValue(self::configKey($idLang), $filename);
        }

        return $errors;
    }

    public static function seedEnglishDefault()
    {
        $defaultFile = 'logo_multilingue.png';
        if (!self::fileExists($defaultFile)) {
            return;
        }

        foreach (Language::getLanguages(false) as $language) {
            $iso = strtolower((string) $language['iso_code']);
            if ($iso !== 'en' && $iso !== 'gb') {
                continue;
            }
            $key = self::configKey((int) $language['id_lang']);
            if (!Configuration::get($key)) {
                Configuration::updateValue($key, $defaultFile);
            }
        }
    }
}
