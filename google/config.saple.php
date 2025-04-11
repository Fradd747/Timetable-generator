<?php
// Google API Configuration
return [
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'redirect_uri' => 'http://harmonogram.skauting.cz/google/callback.php',
    'scopes' => [
        'https://www.googleapis.com/auth/calendar.readonly',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ],
    'access_type' => 'offline',
    'prompt' => 'consent',
];
?> 