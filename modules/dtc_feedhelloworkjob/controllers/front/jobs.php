<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__DIR__, 2) . '/classes/CkcHelloworkI18nHelper.php';

class Dtc_FeedHelloWorkJobJobsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $jobs = [];
        if (isset($this->module) && method_exists($this->module, 'getJobsFromFeed')) {
            $jobs = $this->module->getJobsFromFeed();
        }

        CkcHelloworkI18nHelper::syncFromFeed($jobs);
        $jobs = CkcHelloworkI18nHelper::applyTranslations($jobs);

        $city = Tools::getValue('city');
        $jobTitle = Tools::getValue('job');

        if ($city) {
            $jobs = array_filter($jobs, function ($job) use ($city) {
                return isset($job['City']) && stripos($job['City'], $city) !== false;
            });
        }

        if ($jobTitle) {
            $jobs = array_filter($jobs, function ($job) use ($jobTitle) {
                return isset($job['NameAdvertise']) && stripos($job['NameAdvertise'], $jobTitle) !== false;
            });
        }

        $jobs = array_values($jobs);

        usort($jobs, function ($a, $b) {
            return strtotime($b['CreateDate'] ?? '') - strtotime($a['CreateDate'] ?? '');
        });

        $jobs_per_page = (int) Configuration::get('DTC_HELLOWORK_JOBS_PER_PAGE');
        if ($jobs_per_page < 1) {
            $jobs_per_page = 16;
        }
        $total_jobs = count($jobs);
        $total_pages = (int) ceil($total_jobs / $jobs_per_page);
        $current_page = max(1, (int) Tools::getValue('page', 1));
        $start = ($current_page - 1) * $jobs_per_page;
        $jobs = array_slice($jobs, $start, $jobs_per_page);

        $this->context->smarty->assign([
            'jobs' => $jobs,
            'search_url' => $this->context->link->getModuleLink('dtc_feedhelloworkjob', 'jobs'),
            'pagination_url' => $this->context->link->getModuleLink('dtc_feedhelloworkjob', 'jobs'),
            'current_page' => $current_page,
            'total_pages' => $total_pages,
        ]);

        $this->context->controller->addCSS('module:dtc_feedhelloworkjob/views/css/jobstyle.css');
        $this->context->controller->addJS('module:dtc_feedhelloworkjob/views/js/jobsearch.js');

        $this->setTemplate('module:dtc_feedhelloworkjob/views/templates/front/joblist.tpl');
    }

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $title = $this->trans('Offres d\'emploi', [], 'Shop.Theme.Customglobal');
        if (!isset($page['meta']) || !is_array($page['meta'])) {
            $page['meta'] = [];
        }
        $page['meta']['title'] = $title;

        return $page;
    }
}
