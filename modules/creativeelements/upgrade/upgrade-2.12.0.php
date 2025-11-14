<?php
/**
 * Creative Elements - live Theme & Page Builder
 *
 * @author    WebshopWorks
 * @copyright 2019-2024 WebshopWorks.com
 * @license   One domain support license
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_12_0($module)
{
    Shop::isFeatureActive() && Shop::setContext(Shop::CONTEXT_ALL);

    // Remove useless files
    Tools::deleteDirectory(_CE_PATH_ . 'views/lib/e-gallery');
    array_map('unlink', array_filter([
        _CE_PATH_ . 'modules/catalog/widgets/listing/page-title.php',
    ], 'file_exists'));

    // Clear caches
    $module->hookActionClearCompileCache();
    Db::getInstance()->delete('ce_meta', "`name` = '_tr%_elementor_remote_info_api_data_%'");
    CE\Plugin::instance()->files_manager->clearCache();
    Media::clearCache();

    return true;
}
