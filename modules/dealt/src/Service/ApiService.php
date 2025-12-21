<?php

namespace Dealt\Service;

class ApiService
{
    /*    protected $baseUrl = 'https://api.staging.dealt.tech/api/public/v2/';*/
    protected $baseUrl = 'https://api.dealt.tech/api/public/v2/';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = \Configuration::get('DEALT_API_KEY');
    }

    public function checkAvailability($zipcode, $offerId, $country = 'FR', $storeId = null)
    {
        $params = [
            'country' => $country,
            'zipCode' => $zipcode,
            /*            'city' => 'Paris'*/
        ];

        // API actuelle ne prend pas store_id dans cette route
        $endpoint = "offers/{$offerId}/availability";

        return $this->request('GET', $endpoint, $params);
    }

    public function submitMission(array $data)
    {
        return $this->request('POST', 'missions', [], $data);
    }

    public function cancelMission(string $id_mission)
    {
        $params = [
            'cancellationReason' => "Annulation commande",
        ];

        $endpoint = "missions/{$id_mission}/cancel";

        return $this->request('POST', $endpoint, [], $params);
    }

    private function request(string $method, string $endpoint, array $query = [], array $body = [])
    {
        $url = $this->baseUrl . $endpoint;

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'x-api-key: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        /*        echo $url;
                echo(' ');*/

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);


        if ($error) {
            return [
                'error' => true,
                'message' => 'Erreur CURL : ' . $error,
                'http_code' => $httpCode,
            ];
        }

        $decoded = json_decode($response, true);
//        // Debug temporaire
//        echo '<pre>';
//        echo "URL : " . $url . "\n";
//        echo "Method : " . $method . "\n";
//        echo "Headers : \n" . print_r($headers, true);
//        echo "HTTP Code : " . $httpCode . "\n";
//        echo "Response : \n" . $response . "\n";
//        echo '</pre>';
//        die();
//        if ($httpCode >= 400 || $decoded === null) {
//            return [
//                'error' => true,
//                'http_code' => $httpCode,
//                'message' => $decoded['message'] ?? 'Réponse invalide ou vide',
//                'response' => $response,
//            ];
//        }


        return $decoded;
    }
}

