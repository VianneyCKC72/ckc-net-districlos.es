
<?php
class Dtc_FeedHelloWorkJobJobsModuleFrontController extends ModuleFrontController
{
public function initContent()
{
    parent::initContent();

    $feed_url = Configuration::get('DTC_HELLOWORK_FEED_URL');
    $jobs = $this->fetchJobs($feed_url);

    //  Filtrer uniquement les offres de Districlos
    $jobs = array_filter($jobs, function ($job) {
        return isset($job['CompanyName']) && stripos($job['CompanyName'], 'districlos') !== false;
    });

    //  Filtrage AVANT pagination
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

    //  Tri par date décroissante
    usort($jobs, function($a, $b) {
        return strtotime($b['CreateDate']) - strtotime($a['CreateDate']);
    });

    //  Pagination
    $jobs_per_page = (int)Configuration::get('DTC_HELLOWORK_JOBS_PER_PAGE', 16);
    $total_jobs = count($jobs);
    $total_pages = ceil($total_jobs / $jobs_per_page);
    $current_page = max(1, (int)Tools::getValue('page', 1));
    $start = ($current_page - 1) * $jobs_per_page;
    $jobs = array_slice($jobs, $start, $jobs_per_page);

    $this->context->smarty->assign([
        'jobs' => $jobs,
        'search_url' => $this->context->link->getModuleLink('dtc_feedhelloworkjob', 'jobs'),
        'pagination_url' => $this->context->link->getModuleLink('dtc_feedhelloworkjob', 'jobs'),
        'current_page' => $current_page,
        'total_pages' => $total_pages
    ]);

    $this->context->controller->addCSS('module:dtc_feedhelloworkjob/views/css/jobstyle.css');
    $this->context->controller->addJS('module:dtc_feedhelloworkjob/views/js/jobsearch.js');

    $this->setTemplate('module:dtc_feedhelloworkjob/views/templates/front/joblist.tpl');
}


 private function fetchJobs($url)
 {
 $jobs = [];

 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; PrestaShopBot/1.0)');
 curl_setopt($ch, CURLOPT_TIMEOUT, 10);

 $response = curl_exec($ch);
 $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 curl_close($ch);

 if ($httpCode === 200 && $response) {
 $data = json_decode($response, true);
 if (isset($data['Results'])) {
 $jobs = $data['Results'];
 }
 }

 return $jobs;
 }
}
?>
