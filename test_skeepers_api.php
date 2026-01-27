<?php
/**
 * Script de test pour l'API Skeepers Purchase Events
 * Test toutes les variantes possibles de sales_channel
 */

require_once dirname(__FILE__) . '/config/config.inc.php';
require_once dirname(__FILE__) . '/init.php';

// Configuration
$websiteId = Configuration::get('DTC_SKEEPERS_WEBSITE_ID');
$apiKey = Configuration::get('DTC_SKEEPERS_API_KEY');
$apiSecret = Configuration::get('DTC_SKEEPERS_API_SECRET');
$authUrl = Configuration::get('DTC_SKEEPERS_AUTH_URL');

if (empty($websiteId) || empty($apiKey) || empty($apiSecret)) {
    die("❌ Configuration manquante. Vérifiez les credentials dans le module.\n");
}

echo "🔧 Configuration:\n";
echo "Website ID: " . $websiteId . "\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Auth URL: " . $authUrl . "\n\n";

// Fonction d'authentification
function authenticateSkeepers($authUrl, $apiKey, $apiSecret) {
    $credentials = base64_encode($apiKey . ':' . $apiSecret);
    
    echo "Credentials encodés: " . substr($credentials, 0, 20) . "...\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $authUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials&scope=openid',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded',
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "Code HTTP auth: " . $httpCode . "\n";
    if ($curlError) {
        echo "Erreur cURL auth: " . $curlError . "\n";
    }
    echo "Réponse auth: " . $response . "\n\n";

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return isset($data['access_token']) ? $data['access_token'] : null;
    }

    return null;
}

// Fonction de test
function testPurchaseEvent($token, $websiteId, $salesChannelVariant, $variantName, $minimal = false) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "🧪 TEST: " . $variantName . "\n";
    echo str_repeat("=", 80) . "\n";
    
    if ($minimal) {
        // Payload vraiment minimal - juste les champs requis
        $purchaseEvent = array(
            'purchase_reference' => 'TEST-MIN-' . time(),
            'purchase_date' => date('c'),
            'price' => 0.01,
            'consumer' => new stdClass(),
            'products' => array(),
            'sales_channel' => $salesChannelVariant,
        );
    } else {
        // Payload de base
        $purchaseEvent = array(
            'purchase_reference' => 'TEST-' . time(),
            'purchase_date' => date('c'),
            'price' => 0.01,
            'consumer' => array(
                'email' => 'test@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
            ),
            'products' => array(
                array(
                    'product_ref' => array('reference' => 'TEST-001'),
                    'name' => 'Produit de test',
                ),
            ),
            'sales_channel' => $salesChannelVariant,
        );
    }

    $apiUrl = 'https://api.skeepers.io/purchase-event/websites/' . $websiteId . '/purchase_events/bulk_sync_insert';

    $jsonPayload = json_encode(array($purchaseEvent), JSON_PRETTY_PRINT);
    echo "📤 Payload envoyé:\n";
    echo $jsonPayload . "\n\n";

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => json_encode(array($purchaseEvent)),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "📥 Réponse:\n";
    echo "Code HTTP: " . $httpCode . "\n";
    
    if ($curlError) {
        echo "❌ Erreur cURL: " . $curlError . "\n";
        return false;
    }

    $data = json_decode($response, true);
    echo "Body: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";

    if ($httpCode === 201 || $httpCode === 206) {
        if (isset($data[0]['id'])) {
            echo "✅ SUCCÈS ! Purchase Event ID: " . $data[0]['id'] . "\n";
            return true;
        } else {
            echo "⚠️  Code 201/206 mais pas d'ID dans la réponse\n";
            return false;
        }
    } else {
        if (isset($data[0]['error_description'])) {
            echo "❌ ERREUR: " . $data[0]['error_description'] . "\n";
        }
        return false;
    }
}

// Authentification
echo "🔐 Authentification...\n";
$token = authenticateSkeepers($authUrl, $apiKey, $apiSecret);

if (!$token) {
    die("❌ Échec d'authentification\n");
}

echo "✅ Authentification réussie\n";

// Liste des variantes à tester
$variants = array(
    array(array('channel' => 'online'), '✅ SOLUTION SUPPORT: Objet {"channel": "online"}'),
    array('online', 'Chaîne "online" (minuscules) - Payload minimal', true),
    array('online', 'Chaîne "online" (minuscules)'),
    array('ONLINE', 'Chaîne "ONLINE" (majuscules)'),
    array('Online', 'Chaîne "Online" (capitalisée)'),
    array('offline', 'Chaîne "offline"'),
    array('webtostore', 'Chaîne "webtostore"'),
    array(array('type' => 'online'), 'Objet {"type": "online"}'),
    array(array('type' => 'ONLINE'), 'Objet {"type": "ONLINE"}'),
    array((object)array(), 'Objet vide {}'),
    array(new stdClass(), 'stdClass vide'),
    array(array('name' => 'Website', 'type' => 'online'), 'Objet avec name et type'),
);

// Tester chaque variante
$results = array();
foreach ($variants as $variant) {
    $minimal = isset($variant[2]) ? $variant[2] : false;
    $result = testPurchaseEvent($token, $websiteId, $variant[0], $variant[1], $minimal);
    $results[] = array(
        'name' => $variant[1],
        'success' => $result,
    );
    
    // Pause entre les tests pour éviter le rate limiting
    sleep(2);
}

// Résumé
echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 RÉSUMÉ DES TESTS\n";
echo str_repeat("=", 80) . "\n";

foreach ($results as $result) {
    $status = $result['success'] ? '✅ SUCCÈS' : '❌ ÉCHEC';
    echo $status . " - " . $result['name'] . "\n";
}

echo "\n✨ Tests terminés !\n";
