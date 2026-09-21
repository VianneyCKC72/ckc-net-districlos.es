<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/DtcClosyConfig.php';

/**
 * Service de génération et de cache des questions produit IA.
 *
 * Coût indicatif (gpt-4o-mini, 2026):
 *   - input  : 0,15 $ / 1M tokens
 *   - output : 0,60 $ / 1M tokens
 *   - ~0,0002 € / fiche (3 questions)
 */
class DtcProductQuestionsService
{
    const TABLE_QUESTIONS = 'dtc_product_questions';
    const TABLE_LOG = 'dtc_product_questions_log';

    const MAX_QUESTIONS = 3;
    const MAX_QUESTION_LENGTH = 80;

    const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    const DEFAULT_MODEL = 'gpt-4o-mini';

    /** Termes à éviter dans les questions (attributs / déclinaisons PrestaShop). */
    const DECLINATION_FORBIDDEN_TERMS = [
        'déclinaison',
        'declinaison',
        'déclinaisons',
        'combinaison',
        'attribut',
        'variante',
        'couleur disponible',
        'taille disponible',
    ];

    /** Termes interdits dans les questions IA (gérés ailleurs : transporteurs, livraison...). */
    const FORBIDDEN_TERMS = [
        'livraison',
        'livrer',
        'transporteur',
        'transport',
        'délai',
        'delai',
        'expédition',
        'expedition',
        'colis',
        'paiement',
        'rembours',
    ];

    /**
     * Récupère les questions IA d'un produit (depuis le cache BDD).
     *
     * @return array Liste de questions actives, indexée par position (1..3).
     */
    public function getQuestions(int $idProduct, int $idLang): array
    {
        if ($idProduct <= 0) {
            return [];
        }

        $sql = 'SELECT `position`, `question` FROM `' . _DB_PREFIX_ . self::TABLE_QUESTIONS . '`
                WHERE `id_product` = ' . (int) $idProduct . '
                AND `id_lang` = ' . (int) $idLang . '
                AND `active` = 1
                ORDER BY `position` ASC
                LIMIT ' . (int) self::MAX_QUESTIONS;

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Génère et persiste les 3 questions pour un produit.
     * Si la fiche n'a pas changé (hash identique) on ne régénère pas.
     */
    public function generateForProduct(int $idProduct, int $idLang, bool $force = false): array
    {
        $product = new Product((int) $idProduct, false, (int) $idLang);
        if (!Validate::isLoadedObject($product) || !$product->active) {
            return ['status' => 'skipped', 'reason' => 'product_not_found_or_inactive'];
        }

        $payload = $this->buildProductPayload($product, $idLang);
        $hash = sha1(json_encode($payload, JSON_UNESCAPED_UNICODE));

        if (!$force && $this->hasFreshQuestions($idProduct, $idLang, $hash)) {
            return ['status' => 'skipped', 'reason' => 'fresh_cache'];
        }

        $apiKey = (string) DtcClosyConfig::get('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            $this->log($idProduct, $idLang, 'error', 0, 0, 'Missing OpenAI API key');

            return ['status' => 'error', 'reason' => 'missing_api_key'];
        }

        $model = (string) DtcClosyConfig::get('OPENAI_MODEL', '');
        if ($model === '') {
            $model = self::DEFAULT_MODEL;
        }

        $prompt = $this->buildPrompt($payload);

        try {
            $response = $this->callOpenAI($apiKey, $model, $prompt);
        } catch (Exception $e) {
            $this->log($idProduct, $idLang, 'error', 0, 0, $e->getMessage());

            return ['status' => 'error', 'reason' => $e->getMessage()];
        }

        $questions = $this->validateQuestions($response['questions'] ?? []);
        if (count($questions) === 0) {
            $this->log(
                $idProduct,
                $idLang,
                'error',
                (int) ($response['tokens_input'] ?? 0),
                (int) ($response['tokens_output'] ?? 0),
                'No valid questions after validation'
            );

            return ['status' => 'error', 'reason' => 'no_valid_questions'];
        }

        $this->persistQuestions($idProduct, $idLang, $questions, $hash);
        $this->log(
            $idProduct,
            $idLang,
            'success',
            (int) ($response['tokens_input'] ?? 0),
            (int) ($response['tokens_output'] ?? 0),
            null
        );

        return ['status' => 'success', 'questions' => $questions];
    }

    /**
     * Cron batch : traite N produits actifs sans questions ou avec un hash périmé.
     */
    public function generateBatch(int $limit = 50, ?int $idLang = null): array
    {
        if ($idLang === null) {
            $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        $limit = max(1, min(200, (int) $limit));

        $sql = 'SELECT p.id_product
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . self::TABLE_QUESTIONS . '` q
                    ON q.id_product = p.id_product AND q.id_lang = ' . (int) $idLang . '
                WHERE p.active = 1
                GROUP BY p.id_product
                HAVING COUNT(q.id_question) < ' . (int) self::MAX_QUESTIONS . '
                ORDER BY p.id_product ASC
                LIMIT ' . (int) $limit;

        $rows = Db::getInstance()->executeS($sql);
        $report = ['processed' => 0, 'success' => 0, 'skipped' => 0, 'error' => 0];

        if (!is_array($rows)) {
            return $report;
        }

        foreach ($rows as $row) {
            $result = $this->generateForProduct((int) $row['id_product'], (int) $idLang);
            $report['processed']++;
            $report[$result['status']] = ($report[$result['status']] ?? 0) + 1;
        }

        return $report;
    }

    /**
     * Construit un payload léger à partir du produit (utilisé pour le hash et le prompt).
     */
    private function buildProductPayload(Product $product, int $idLang): array
    {
        $descShort = is_array($product->description_short) ? ($product->description_short[$idLang] ?? '') : (string) $product->description_short;
        $descLong = is_array($product->description) ? ($product->description[$idLang] ?? '') : (string) $product->description;
        $name = is_array($product->name) ? ($product->name[$idLang] ?? '') : (string) $product->name;

        $descShort = Tools::substr(strip_tags((string) $descShort), 0, 400);
        $descLong = Tools::substr(strip_tags((string) $descLong), 0, 1200);

        $idCategory = (int) $product->id_category_default;
        $categoryName = '';
        if ($idCategory > 0) {
            $category = new Category($idCategory, $idLang);
            if (Validate::isLoadedObject($category)) {
                $categoryName = (string) $category->name;
            }
        }

        $features = [];
        foreach ($product->getFrontFeatures($idLang) as $feature) {
            $features[] = trim(($feature['name'] ?? '') . ' : ' . ($feature['value'] ?? ''));
            if (count($features) >= 20) {
                break;
            }
        }

        $attachments = [];
        foreach ($product->getAttachments($idLang) as $att) {
            if (!is_array($att)) {
                continue;
            }
            $an = isset($att['name']) ? strip_tags((string) $att['name']) : '';
            $ad = isset($att['description']) ? strip_tags((string) $att['description']) : '';
            $line = trim($an . ($an !== '' && $ad !== '' ? ' — ' : '') . $ad);
            if ($line === '') {
                continue;
            }
            $attachments[] = Tools::substr($line, 0, 280);
            if (count($attachments) >= 12) {
                break;
            }
        }

        $faqText = $this->buildFaqPlainText((int) $product->id);

        return [
            'name' => $name,
            'category' => $categoryName,
            'description_short' => $descShort,
            'description' => $descLong,
            'features' => $features,
            'attachments' => $attachments,
            'faq' => $faqText,
        ];
    }

    /**
     * Texte FAQ (module productfaq si installé / méthode connue).
     */
    private function buildFaqPlainText(int $idProduct): string
    {
        if ($idProduct <= 0 || !class_exists('Module', false)) {
            return '';
        }
        if (!Module::isInstalled('productfaq')) {
            return '';
        }
        $m = Module::getInstanceByName('productfaq');
        if (!$m || !$m->active || !method_exists($m, 'getFaqsByProduct')) {
            return '';
        }
        try {
            $raw = $m->getFaqsByProduct($idProduct, true);
        } catch (Throwable $e) {
            return '';
        }

        return $this->flattenFaqDataToPlainText($raw);
    }

    /**
     * @param mixed $data
     */
    private function flattenFaqDataToPlainText($data): string
    {
        if ($data === null || $data === false || $data === '') {
            return '';
        }
        if (is_string($data)) {
            return Tools::substr(trim(preg_replace('/\s+/', ' ', strip_tags($data))), 0, 3500);
        }
        if (is_object($data)) {
            $data = (array) $data;
        }
        if (!is_array($data)) {
            return '';
        }
        $lines = [];
        foreach ($data as $item) {
            if (is_string($item)) {
                $t = trim(strip_tags($item));
                if ($t !== '') {
                    $lines[] = $t;
                }
                continue;
            }
            if (is_object($item)) {
                $item = (array) $item;
            }
            if (!is_array($item)) {
                continue;
            }
            $q = '';
            $a = '';
            foreach (['question', 'faq_question', 'title', 'name', 'libelle'] as $k) {
                if (!empty($item[$k])) {
                    $q = trim(strip_tags((string) $item[$k]));
                    break;
                }
            }
            foreach (['answer', 'faq_answer', 'response', 'content', 'description', 'reponse'] as $k) {
                if (!empty($item[$k])) {
                    $a = trim(strip_tags((string) $item[$k]));
                    break;
                }
            }
            if ($q !== '' || $a !== '') {
                $lines[] = trim($q . ($q !== '' && $a !== '' ? ' — ' : '') . $a);
            }
        }
        $text = implode("\n", array_filter($lines));

        return Tools::substr($text, 0, 3500);
    }

    /**
     * Vérifie qu'on a déjà 3 questions à jour pour ce hash.
     */
    private function hasFreshQuestions(int $idProduct, int $idLang, string $hash): bool
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . self::TABLE_QUESTIONS . '`
                WHERE `id_product` = ' . (int) $idProduct . '
                AND `id_lang` = ' . (int) $idLang . '
                AND `hash_source` = "' . pSQL($hash) . '"
                AND `active` = 1';

        return (int) Db::getInstance()->getValue($sql) >= self::MAX_QUESTIONS;
    }

    /**
     * Persistance : on supprime les anciennes questions du produit/langue puis on réinsère.
     */
    private function persistQuestions(int $idProduct, int $idLang, array $questions, string $hash): void
    {
        Db::getInstance()->delete(
            self::TABLE_QUESTIONS,
            '`id_product` = ' . (int) $idProduct . ' AND `id_lang` = ' . (int) $idLang
        );

        $now = date('Y-m-d H:i:s');
        $position = 1;
        foreach ($questions as $question) {
            Db::getInstance()->insert(self::TABLE_QUESTIONS, [
                'id_product' => (int) $idProduct,
                'id_lang' => (int) $idLang,
                'position' => (int) $position,
                'question' => pSQL($question),
                'hash_source' => pSQL($hash),
                'generated_at' => pSQL($now),
                'active' => 1,
            ]);
            $position++;
            if ($position > self::MAX_QUESTIONS) {
                break;
            }
        }
    }

    /**
     * Construit le prompt envoyé à OpenAI (réponse JSON strict).
     */
    private function buildPrompt(array $payload): string
    {
        $featuresBlock = '';
        if (!empty($payload['features'])) {
            $lines = [];
            foreach ($payload['features'] as $line) {
                $lines[] = '- ' . $line;
            }
            $featuresBlock = implode("\n", $lines);
        }

        $attachBlock = '';
        if (!empty($payload['attachments'])) {
            $lines = [];
            foreach ($payload['attachments'] as $line) {
                $lines[] = '- ' . $line;
            }
            $attachBlock = implode("\n", $lines);
        }

        $faqBlock = isset($payload['faq']) ? (string) $payload['faq'] : '';

        $sections = "--- FICHE PRODUIT (descriptions) ---\n"
            . 'Nom : ' . $payload['name'] . "\n"
            . 'Catégorie : ' . $payload['category'] . "\n"
            . 'Description courte : ' . $payload['description_short'] . "\n"
            . 'Description longue : ' . $payload['description'] . "\n";

        $sections .= "\n--- DÉTAILS DU PRODUIT (caractéristiques / onglet type PrestaShop « Détails du produit ») ---\n";
        $sections .= $featuresBlock !== '' ? $featuresBlock : '(aucune caractéristique listée)';

        $sections .= "\n\n--- DOCUMENTS JOINTS (fiches techniques : titres et légendes des pièces jointes, pas le contenu binaire des fichiers) ---\n";
        $sections .= $attachBlock !== '' ? $attachBlock : '(aucun document joint)';

        $sections .= "\n\n--- FAQ PRODUIT (questions / réponses associées au produit) ---\n";
        $sections .= $faqBlock !== '' ? $faqBlock : '(aucune FAQ pour ce produit)';

        return "Tu es un expert e-commerce Districlos (clôtures, grillages, portails).\n"
            . "À partir de TOUTES les sections ci-dessous, génère exactement 3 questions courtes\n"
            . '(max 60 caractères chacune), en français, qu\'un client poserait AVANT d\'acheter ce produit.' . "\n\n"
            . "Règles STRICTES :\n"
            . "- Interdit : livraison, transporteurs, délais, paiement, SAV (déjà gérés ailleurs).\n"
            . "- NE pose AUCUNE question sur les déclinaisons PrestaShop, combinaisons, variantes, attributs à choisir (couleur, taille, liste d'options), ni sur « quelle variante choisir ».\n"
            . "- Tu peux t'appuyer sur : descriptions, bloc « Détails du produit » (caractéristiques), libellés des documents joints (fiche technique), et FAQ.\n"
            . "- Pas de doublons entre elles, pas de questions génériques sans lien avec ce produit.\n"
            . "- Privilégie : pose, outillage, fixation, normes, usage terrain, sécurité, entretien, pièces, longévité, réglages, compatibilité chantier.\n"
            . "- Réponse UNIQUEMENT en JSON strict, sans texte avant ni après :\n"
            . '  {"questions":["...","...","..."]}' . "\n\n"
            . $sections;
    }

    /**
     * Appel API OpenAI Chat Completions, retourne le tableau {questions, tokens_input, tokens_output}.
     *
     * @throws Exception
     */
    private function callOpenAI(string $apiKey, string $model, string $prompt): array
    {
        $body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu génères uniquement du JSON strict, sans markdown, sans commentaire.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.3,
            'max_tokens' => 480,
            'response_format' => ['type' => 'json_object'],
        ];

        $ch = curl_init(self::OPENAI_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new Exception('OpenAI cURL error: ' . $err);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new Exception('OpenAI invalid JSON response (HTTP ' . $http . ')');
        }

        if ($http >= 400 || isset($data['error'])) {
            $msg = isset($data['error']['message']) ? $data['error']['message'] : ('HTTP ' . $http);
            throw new Exception('OpenAI error: ' . $msg);
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode((string) $content, true);
        if (!is_array($parsed) || !isset($parsed['questions']) || !is_array($parsed['questions'])) {
            throw new Exception('OpenAI returned malformed JSON content');
        }

        return [
            'questions' => $parsed['questions'],
            'tokens_input' => (int) ($data['usage']['prompt_tokens'] ?? 0),
            'tokens_output' => (int) ($data['usage']['completion_tokens'] ?? 0),
        ];
    }

    /**
     * Filtre les questions : longueur, doublons, termes interdits.
     */
    private function validateQuestions(array $rawQuestions): array
    {
        $out = [];
        $seen = [];

        foreach ($rawQuestions as $q) {
            if (!is_string($q)) {
                continue;
            }
            $q = trim($q);
            $q = preg_replace('/\s+/', ' ', $q);
            $q = (string) $q;

            if ($q === '') {
                continue;
            }
            if (Tools::strlen($q) > self::MAX_QUESTION_LENGTH) {
                continue;
            }

            $lower = Tools::strtolower($q);
            foreach (self::FORBIDDEN_TERMS as $term) {
                if (strpos($lower, $term) !== false) {
                    continue 2;
                }
            }
            foreach (self::DECLINATION_FORBIDDEN_TERMS as $term) {
                if (strpos($lower, Tools::strtolower($term)) !== false) {
                    continue 2;
                }
            }
            if (in_array($lower, $seen, true)) {
                continue;
            }

            $seen[] = $lower;
            $out[] = $q;

            if (count($out) >= self::MAX_QUESTIONS) {
                break;
            }
        }

        return $out;
    }

    /**
     * Trace l'appel (pour suivi du coût et debug).
     */
    private function log(int $idProduct, int $idLang, string $status, int $tokensIn, int $tokensOut, ?string $error): void
    {
        Db::getInstance()->insert(self::TABLE_LOG, [
            'id_product' => (int) $idProduct,
            'id_lang' => (int) $idLang,
            'status' => pSQL($status),
            'tokens_input' => (int) $tokensIn,
            'tokens_output' => (int) $tokensOut,
            'error_message' => $error !== null ? pSQL($error, true) : null,
            'created_at' => pSQL(date('Y-m-d H:i:s')),
        ]);
    }
}
