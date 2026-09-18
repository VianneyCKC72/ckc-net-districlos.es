<?php
/**
 * Category Filter Wizard — Recherche catégorie (AJAX)
 *
 * Retourne des catégories actives correspondant à un terme de recherche.
 * Utilisé par l'autocomplétion back-office (au lieu d'une liste déroulante énorme).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class dtc_categoryfilterwizardCategorysearchModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json; charset=utf-8');

        // Sécurité : endpoint AJAX utilisé depuis le back-office.
        // En front, Context()->employee peut être absent, donc Tools::getAdminTokenLite()
        // peut produire un token faux. On sécurise donc comme suit :
        // - si employee est dispo : on valide le token
        // - sinon : on valide le contexte via HTTP_REFERER (AdminModules / config du module)
        $token     = (string) Tools::getValue('dtc_token', '');
        $employee  = Context::getContext()->employee ?? null;
        $referer   = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $refererOk  = (strpos($referer, 'AdminModules') !== false) || (strpos($referer, 'configure=dtc_categoryfilterwizard') !== false);

        if ($employee && !empty($employee->id)) {
            $expected = Tools::getAdminTokenLite('AdminModules');
            if (empty($token) || !hash_equals($expected, $token)) {
                die(json_encode(['error' => 'Forbidden']));
            }
        } elseif (!$refererOk) {
            die(json_encode(['error' => 'Forbidden']));
        }

        $q = (string) Tools::getValue('q', '');
        $limit = (int) Tools::getValue('limit', 15);
        if ($limit <= 0) {
            $limit = 15;
        }
        $q = trim($q);
        if (Tools::strlen($q) < 2) {
            die(json_encode(['results' => []]));
        }

        $idLang = (int) $this->context->language->id;

        // NOTE : on limite la charge en utilisant uniquement category_lang.name.
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT c.id_category, cl.name, c.level_depth
             FROM ' . _DB_PREFIX_ . 'category c
             INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl
               ON cl.id_category = c.id_category AND cl.id_lang = ' . $idLang . '
             WHERE c.active = 1
               AND c.id_category > 2
               AND cl.name LIKE "%' . pSQL(Tools::strtolower($q)) . '%"
             ORDER BY c.level_depth ASC, cl.name ASC
             LIMIT ' . (int) $limit
        );

        $payload = [
            'results' => array_map(static function ($row) {
                $id = (int) $row['id_category'];
                $name = (string) $row['name'];

                return [
                    'id_category' => $id,
                    'label'       => $name . ' (id: ' . $id . ')',
                ];
            }, $results ?: []),
        ];

        die(json_encode($payload));
    }
}

