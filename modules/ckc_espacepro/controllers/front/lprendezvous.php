<?php
/**
 * Contrôleur front : landing page "Rendez-vous PRO" (slug lp-pro-rendez-vous).
 * Page entièrement vierge (sans header ni footer PrestaShop).
 * Accessible sur pro.cloture-discount.fr/lp-pro-rendez-vous
 *             et devpro.cloture-discount.fr/lp-pro-rendez-vous
 */
class Ckc_EspaceproLprendezvousModuleFrontController extends ModuleFrontController
{
    public $display_header       = false;
    public $display_footer       = false;
    public $display_column_left  = false;
    public $display_column_right = false;

    public function initContent()
    {
        parent::initContent();

        $lpDir     = $this->module->getLocalPath() . 'lp-pro-rendez-vous/';
        $indexPath = $lpDir . 'index.php';

        if (!is_file($indexPath)) {
            header('HTTP/1.0 404 Not Found');
            return;
        }

        $baseUrl = (Tools::usingSecureMode() ? 'https' : 'http')
            . '://' . Tools::getHttpHost()
            . __PS_BASE_URI__
            . 'modules/ckc_espacepro/lp-pro-rendez-vous/';

        $isConfirm = (bool) Tools::getValue('remerciements');

        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: public, max-age=300');

        include $indexPath;
        exit;
    }
}
