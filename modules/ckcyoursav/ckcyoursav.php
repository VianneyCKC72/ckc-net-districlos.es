<?php
/**
 * 2007-2024 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2024 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ckcyoursav extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name          = 'ckcyoursav';
        $this->tab           = 'administration';
        $this->version       = '1.0.0';
        $this->author        = 'ckc-net';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('CKC Your SAV');
        $this->description = $this->l('CKC Your SAV synchronization');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CKCYOURSAV_LIVE_MODE', false);

        return parent::install() &&
               $this->registerHook('header') &&
               $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CKCYOURSAV_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitCkcyoursavModule')) == true) {
            $this->postProcess();
        }

        if (((bool)Tools::isSubmit('submitSynchronizeSAV')) == true) {
            $this->run();
        }

        $moduleAdminLink = $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name]);

        $this->context->smarty->assign([
                                           'module_dir'      => $this->_path,
                                           'moduleAdminLink' => $moduleAdminLink,
                                       ]);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    public function run()
    {
        $audioDirectory = _PS_ROOT_DIR_ . '/audio';
        $outputDirectory = $audioDirectory . '/output';
        $files = scandir($audioDirectory);

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'wav') {
                $filePath = $audioDirectory . '/' . $file;

                // Découper le fichier en segments
                $this->splitAudioFile($filePath, $outputDirectory, 58); // Segments de 60 secondes

                $segments = scandir($outputDirectory);
                $transcript = '';

                foreach ($segments as $segment) {
                    if (pathinfo($segment, PATHINFO_EXTENSION) === 'wav') {
                        $segmentPath = $outputDirectory . '/' . $segment;
                        $fileUrl = 'https://vianney.cloture-discount.fr/audio/output/' . $segment;

                        // Transcrire chaque segment
                        $transcriptSegment = $this->transcribeAudio($segmentPath, $fileUrl);
                        $transcript .= $transcriptSegment . ' ';
                    }
                }

                $this->insertIntoDatabase(trim($transcript), $file);
                echo "Transcription de $file insérée dans la base de données.<br>";

                // Nettoyer le dossier des segments après utilisation
                array_map('unlink', glob("$outputDirectory/*"));
            }
        }
    }

    function transcribeAudio($audioFilePath, $fileUrl)
    {
        if (!class_exists('getID3', false)) {
            require_once __DIR__ . "/lib/ID3/getid3.php";
        }

        // Utilisation de getID3 pour obtenir les métadonnées du fichier audio
        $getID3   = new getID3;
        $fileInfo = $getID3->analyze($audioFilePath);

        // Récupérer le taux d'échantillonnage du fichier audio
        if (isset($fileInfo['audio']['sample_rate'])) {
            $sampleRateHertz = $fileInfo['audio']['sample_rate'];
            echo "Taux d'échantillonnage récupéré : $sampleRateHertz Hz<br>";
        }
        else {
            // Si impossible de récupérer, utiliser une valeur par défaut (ajuster en fonction de votre cas)
            $sampleRateHertz = 16000;
            echo "Taux d'échantillonnage non trouvé, utilisation de la valeur par défaut : $sampleRateHertz Hz<br>";
        }



        // Continuer avec le reste du code
        $audioContent = file_get_contents($audioFilePath);
        $audioBase64  = base64_encode($audioContent);

        $postData = json_encode([
                                    "config" => [
                                        "encoding"        => "LINEAR16",
                                        "sampleRateHertz" => $sampleRateHertz,
                                        "languageCode"    => "fr-FR",
                                        "model"=> "phone_call",
                                        "enableAutomaticPunctuation"=> true,
                                    ],
                                    "audio"  => [
                                        "content" => $audioBase64
                                    ]
                                ]);

        $url = "https://speech.googleapis.com/v1/speech:recognize?key=AIzaSyBjyQ2_zoT3P96ML-eazYyQ42xVyO5qtLE";

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Active le débogage cURL
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_STDERR, fopen('php://stderr', 'w'));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erreur Curl: ' . curl_error($ch);
        }
        else {
            // Séparer les en-têtes du corps de la réponse
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body        = substr($response, $header_size);

            // Afficher le corps brut de la réponse
            echo "<hr>Response body:<pre>" . htmlspecialchars($body) . "</pre><hr>";
        }

        curl_close($ch);

        $responseDecoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo 'Erreur de décodage JSON : ' . json_last_error_msg() . "<br>";
        }
        elseif (isset($responseDecoded['error'])) {
            echo "Erreur de l'API Google: " . htmlspecialchars($responseDecoded['error']['message']) . "<br>";
        }
        elseif (isset($responseDecoded['results'])) {
            $transcript = '';
            foreach ($responseDecoded['results'] as $result) {
                $transcript .= $result['alternatives'][0]['transcript'];
            }
            echo "Transcription : " . htmlspecialchars($transcript) . "<br>";
        }
        else {
            echo "Pas de résultats de transcription pour le fichier $audioFilePath.<br>";
        }

        if (isset($responseDecoded['error'])) {
            echo "Erreur de l'API Google: " . $responseDecoded['error']['message'] . "<br>";
        }
        else if (isset($responseDecoded['results'])) {
            $transcript = '';
            foreach ($responseDecoded['results'] as $result) {
                $transcript .= $result['alternatives'][0]['transcript'];
            }
            echo "Transcription : " . htmlspecialchars($transcript) . "<br>";
        }
        else {
            echo "Erreur de transcription pour le fichier $audioFilePath.<br>";
        }


        exit;
        return $transcript;
    }


    private function insertIntoDatabase($transcript, $audioFileName)
    {
        $sql = "INSERT INTO " . _DB_PREFIX_ . "ckc_yoursav (audio_file_name, transcription) VALUES ('" . pSQL($audioFileName) . "', '" . pSQL($transcript) . "')";
        Db::getInstance()->execute($sql);
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $helper->module                   = $this;
        $helper->default_form_language    = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier    = $this->identifier;
        $helper->submit_action = 'submitCkcyoursavModule';
        $helper->currentIndex  = $this->context->link->getAdminLink('AdminModules', false)
                                 . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token         = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Google API Key'),
                        'name'     => 'CKCYOURSAV_GOOGLE_API_KEY',
                        'size'     => 40,
                        'required' => true,
                        'desc'     => $this->l('Enter your Google API Key for Speech-to-Text service'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            'CKCYOURSAV_GOOGLE_API_KEY' => Configuration::get('CKCYOURSAV_GOOGLE_API_KEY', ''),
        ];
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function splitAudioFile($inputFile, $outputDir, $segmentDuration = 60)
    {
        // Assurez-vous que le dossier de sortie existe
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Utiliser ffmpeg pour obtenir la durée totale du fichier audio
        $command = "ffmpeg -i $inputFile 2>&1 | grep 'Duration' | awk '{print $2}' | tr -d ,";
        $duration = shell_exec($command);
        list($hours, $minutes, $seconds) = sscanf($duration, "%d:%d:%d");
        $totalDuration = $hours * 3600 + $minutes * 60 + $seconds;

        // Calculer le nombre de segments nécessaires
        $segments = ceil($totalDuration / $segmentDuration);

        // Découper le fichier en segments
        for ($i = 0; $i < $segments; $i++) {
            $start = gmdate("H:i:s", $i * $segmentDuration);
            $outputFile = $outputDir . '/segment_' . $i . '.wav';
            $command = "ffmpeg -i $inputFile -ss $start -t $segmentDuration -c copy $outputFile";
            shell_exec($command);
            echo "Segment $i créé : $outputFile<br>";
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }
}
