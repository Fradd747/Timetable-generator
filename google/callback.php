<?php
session_start();

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Google\Service\Oauth2 as Google_Service_Oauth2;

// Load configuration
$config = require_once __DIR__ . '/config.php';

// Create Google client
$client = new Google\Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri($config['redirect_uri']);

// Exchange authorization code for access token
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);
    
    // Store token in session
    $_SESSION['google_token'] = $token;
    
    // Get user information
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();
    
    $_SESSION['google_user'] = [
        'email' => $userInfo->getEmail(),
        'name' => $userInfo->getName(),
        'picture' => $userInfo->getPicture()
    ];
    
    // Redirect to the main index page
    header('Location: ../index.php');
    exit();
} else {
    // If no code, redirect to auth page
    header('Location: auth.php');
    exit();
}
?> 