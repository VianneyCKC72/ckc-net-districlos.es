<?php

class AdminCustomerThreadsController extends AdminCustomerThreadsControllerCore
{
    public function __construct()
    {
        parent::__construct();

        // Colonne "Boutique" (filtrable) — alias table 'ss' défini dans renderList()
        $this->fields_list['ckc_store_name'] = [
            'title'          => $this->trans('Boutique', [], 'Admin.Global'),
            'filter_key'     => 'ss!name',   // important pour WHERE sur l'alias 'ss'
            'tmpTableFilter' => true,
            'orderby'        => false,
            'align'          => 'text-left',
        ];
    }

    /**
     * Liste: on joint la dernière commande du client => boutique, + restriction sécurité.
     */
    public function renderList()
    {
        // Sous-requête: dernière commande pour a.id_customer
        $sub = '(
            SELECT o2.id_order
            FROM '._DB_PREFIX_.'orders o2
            WHERE o2.id_customer = a.id_customer
            ORDER BY o2.date_add DESC
            LIMIT 1
        )';

        // Expose le nom de boutique
        $this->_select .= ',
            ss.name AS ckc_store_name
        ';

        // Jointures: dernière commande + table boutiques
        $this->_join .= '
            LEFT JOIN '._DB_PREFIX_.'orders o_last
                ON o_last.id_order = '.$sub.'
            LEFT JOIN '._DB_PREFIX_.'ckcstockstores_stores ss
                ON ss.id_store = o_last.id_store
        ';

        // SÉCURITÉ: limiter l’affichage selon les boutiques autorisées,
        // tout en laissant passer les threads sans commande (o_last.id_store IS NULL)
        $allowed = $this->getAllowedStoreIdsForEmployee((int)$this->context->employee->id);

        if ($allowed === null) {
            // Politique "aucun droit => aucun résultat" (comme ton grid Clients)
            $this->_where .= ' AND 1=0 ';
        } elseif (!empty($allowed)) {
            $ids = implode(',', array_map('intval', $allowed));
            $this->_where .= ' AND (o_last.id_store IS NULL OR o_last.id_store IN ('.$ids.')) ';
        } else {
            // $allowed === [] signifie "toutes boutiques autorisées" (si tu préfères)
            // Ne rien ajouter dans ce cas.
        }

        // Le listing legacy regroupe déjà par thread via:
        // $this->_group = 'GROUP BY cm.id_customer_thread'; (déjà dans Core)
        return parent::renderList();
    }

    protected function getAllowedStoreIdsForEmployee(int $employeeId): ?array
    {
        $rows = \Db::getInstance()->executeS('
            SELECT id_store
            FROM '._DB_PREFIX_.'ckcstockstores_employee_store
            WHERE id_employee='.(int)$employeeId);

        return array_map('intval', array_column($rows, 'id_store'));
    }
}
