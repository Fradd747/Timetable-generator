<?php
session_start();

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require_once __DIR__ . '/config.php';

// Create Google client
$client = new Google\Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri($config['redirect_uri']);
$client->setAccessType($config['access_type']);
$client->setPrompt($config['prompt']);
$client->setScopes($config['scopes']);

// Generate authentication URL
$authUrl = $client->createAuthUrl();

// Redirect to Google auth page
header('Location: ' . $authUrl);
exit();
?> 