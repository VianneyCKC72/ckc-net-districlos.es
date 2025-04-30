<?php

// Gateway for FB Conversion API calls
const API_VERSION = "v13.0";
const PIXEL_ID = "919176128149032";
const ACCESS_TOKEN = "EAADyKEhxZAcsBAJHecrOS8PdHL5QpH7EdpvRt8w8sgqv0NlJMXmimvVIyh3D5F9sdrKSieCtorLRju7RExFZAd1ho6lRnpymxjf0rQ0wavgiDR0eD9lJbKuidO5paYtG07SwpZBYyxCN0evHxUfyfeAkFZClbjcaV9TgBF6aoTmJhh1pO5fB";

$endpoint = "https://graph.facebook.com/". API_VERSION . "/". PIXEL_ID ."/events?access_token=". ACCESS_TOKEN;

if(isset($_POST) && !empty($_POST)) 
{
    // Set timestamp
    $_POST['event_time'] = time();

    // Set User IP and User-Agent
    if(isset($_POST['user_data'])) {
        $_POST['user_data']['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_POST['user_data']['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    // Hash personal user data (SHA256)
    if(isset($_POST['user_data']['em'][0])) {
        $_POST['user_data']['em'][0] = hash('sha256', $_POST['user_data']['em'][0]);
        $_POST['user_data']['ph'][0] = hash('sha256', $_POST['user_data']['ph'][0]);
        $_POST['user_data']['ln'][0] = hash('sha256', $_POST['user_data']['ln'][0]);
        $_POST['user_data']['fn'][0] = hash('sha256', $_POST['user_data']['fn'][0]);
    }

    // Prepare data
    // WARNING: Remove &test_event_code=TEST34530 parameter in production
    $payload = "data=[". json_encode($_POST) ."]";

    // Make POST request
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);
    curl_close($ch);

    echo json_encode($output);
}