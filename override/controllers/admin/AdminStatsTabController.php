<?php
/**
 * Override pour personnaliser l'ordre des modules de statistiques
 * et masquer le calendrier pour certains modules
 */
class AdminStatsTabController extends AdminStatsTabControllerCore
{
    /**
     * Liste des modules qui n'ont pas besoin du calendrier de sélection de dates
     * @var array
     */
    private $modules_without_calendar = [
        'dtc_stats_stores',
        'dtc_stats_performances',
    ];

    /**
     * Initialisation du contenu
     * Masque le calendrier pour certains modules DASHBOARD
     */
    public function initContent()
    {
        if ($this->ajax) {
            return;
        }

        $this->toolbar_title = $this->trans('Stats', [], 'Admin.Stats.Feature');

        if ($this->display == 'view') {
            // Some controllers use the view action without an object
            if ($this->className) {
                $this->loadObject(true);
            }
            $this->content .= $this->renderView();
        }

        // Menu des modules de stats
        $this->content .= $this->displayMenu();

        // Calendrier : affiché uniquement si le module actuel n'est pas dans la liste d'exclusion
        $current_module = Tools::getValue('module');
        if (!in_array($current_module, $this->modules_without_calendar)) {
            // Avec calendrier (structure normale de PrestaShop)
            $this->content .= $this->displayCalendar();
            $this->content .= $this->displayStats();
        } else {
            // Sans calendrier : on ouvre statsContainer manuellement
            $this->content .= '<div id="statsContainer" class="col-md-9">';
            $this->content .= $this->displayStats();
        }

        $this->context->smarty->assign([
            'content' => $this->content,
        ]);
    }

    /**
     * Tri personnalisé : ordre des modules DASHBOARD puis tri alphabétique
     * 1. DASHBOARD - Général (statsforecast)
     * 2. DASHBOARD - Magasins (dtc_stats_stores)
     * 3. DASHBOARD - Performances (dtc_stats_performances)
     * 4. Autres modules triés alphabétiquement
     *
     * @param array $a Premier module à comparer
     * @param array $b Second module à comparer
     * @return int Résultat de la comparaison
     */
    public function checkModulesNames($a, $b): int
    {
        // Définir l'ordre des modules DASHBOARD
        $priority_modules = [
            'dtc_stats_general' => 1,
            'dtc_stats_stores' => 2,
            'dtc_stats_performances' => 3,
        ];

        $priority_a = isset($priority_modules[$a['name']]) ? $priority_modules[$a['name']] : 999;
        $priority_b = isset($priority_modules[$b['name']]) ? $priority_modules[$b['name']] : 999;

        // Si les deux modules ont une priorité définie, comparer les priorités
        if ($priority_a !== 999 || $priority_b !== 999) {
            if ($priority_a !== $priority_b) {
                return $priority_a - $priority_b;
            }
        }

        // Pour les autres modules, tri alphabétique normal
        return strcasecmp($a['displayName'], $b['displayName']);
    }
}

