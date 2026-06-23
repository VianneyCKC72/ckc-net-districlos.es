<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CkcTranslateAi
{
    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $model;

    /** @var float */
    protected $temperature;

    /** @var int */
    protected $maxTokens;

    /** @var string */
    protected $globalPrompt;

    public function __construct($apiKey, $model, $temperature, $maxTokens, $globalPrompt)
    {
        $this->apiKey = (string) $apiKey;
        $this->model = (string) $model;
        $this->temperature = (float) $temperature;
        $this->maxTokens = (int) $maxTokens;
        $this->globalPrompt = (string) $globalPrompt;
    }

    public function isConfigured()
    {
        return $this->apiKey !== '';
    }

    public function requestJson($systemPrompt, $userPrompt)
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $payload = array(
            'model' => $this->model ?: 'gpt-4.1-mini',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => trim($this->globalPrompt . "\n\n" . $systemPrompt),
                ),
                array(
                    'role' => 'user',
                    'content' => $userPrompt,
                ),
            ),
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        );

        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        curl_setopt_array($ch, array(
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ));

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $decoded = json_decode($response, true);

        if (!isset($decoded['choices'][0]['message']['content'])) {
            return null;
        }

        $content = (string) $decoded['choices'][0]['message']['content'];

        if (!preg_match('/\{.*\}/s', $content, $matches)) {
            return null;
        }

        $json = json_decode($matches[0], true);

        return is_array($json) ? $json : null;
    }
}