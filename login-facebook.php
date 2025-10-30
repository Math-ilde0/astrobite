<?php
require_once __DIR__ . '/includes/oauth.php';

$state = bin2hex(random_bytes(16));
session_start();
$_SESSION['oauth2state'] = $state;

$params = [
  'client_id' => FACEBOOK_CLIENT_ID,
  'redirect_uri' => FACEBOOK_REDIRECT_URI,
  'state' => $state,
  'response_type' => 'code',
  'scope' => 'email,public_profile'
];

$authUrl = 'https://www.facebook.com/v15.0/dialog/oauth?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
?>

