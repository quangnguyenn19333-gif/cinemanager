<?php
session_start();

$clientID = '782712357762-sa1lrjve0cjng6khlgfd6ftr4ejmol2f.apps.googleusercontent.com';
$redirectUri = 'http://localhost/nhap/api/google_callback.php';

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $clientID,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'prompt' => 'select_account'
]);

header('Location: ' . $authUrl);
exit;
?>