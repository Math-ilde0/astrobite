<?php
require_once __DIR__ . '/includes/oauth.php';

$state = bin2hex(random_bytes(16));
session_start();
$_SESSION['oauth2state'] = $state;

$params = [
  'response_type' => 'code',
  'client_id' => GOOGLE_CLIENT_ID,
  'redirect_uri' => GOOGLE_REDIRECT_URI,
  'scope' => 'openid email profile',
  'state' => $state,
  'access_type' => 'offline',
  'prompt' => 'consent'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
?>

