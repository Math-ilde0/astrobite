<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/oauth.php';

if (!isset($_GET['code']) || !isset($_GET['state']) || !isset($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
  http_response_code(400);
  echo 'Invalid OAuth state.';
  exit;
}

unset($_SESSION['oauth2state']);

// Exchange code for token
$ch = curl_init('https://graph.facebook.com/v15.0/oauth/access_token?' . http_build_query([
  'client_id' => FACEBOOK_CLIENT_ID,
  'client_secret' => FACEBOOK_CLIENT_SECRET,
  'redirect_uri' => FACEBOOK_REDIRECT_URI,
  'code' => $_GET['code']
]));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]);
$resp = curl_exec($ch);
if ($resp === false) { echo 'Token request failed'; exit; }
$tokenResponse = json_decode($resp, true);
curl_close($ch);

if (!isset($tokenResponse['access_token'])) { echo 'No access token'; exit; }

// Fetch user info
$ch = curl_init('https://graph.facebook.com/me?fields=id,name,email&access_token=' . urlencode($tokenResponse['access_token']));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]);
$userInfoJson = curl_exec($ch);
if ($userInfoJson === false) { echo 'Userinfo request failed'; exit; }
$userInfo = json_decode($userInfoJson, true);
curl_close($ch);

$providerId = $userInfo['id'] ?? null;
$email = $userInfo['email'] ?? null; // email may be null if not granted
$name = $userInfo['name'] ?? 'Facebook User';

if (!$providerId) { echo 'Missing profile data'; exit; }

$stmt = $pdo->prepare('SELECT id, name FROM users WHERE provider = ? AND provider_id = ?');
$stmt->execute(['facebook', $providerId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  if ($email) {
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
  }
  if ($user) {
    $stmt = $pdo->prepare('UPDATE users SET provider = ?, provider_id = ? WHERE id = ?');
    $stmt->execute(['facebook', $providerId, $user['id']]);
  } else {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, provider, provider_id, role) VALUES (?, ?, "", "facebook", ?, "user")');
    $stmt->execute([$name, $email, $providerId]);
    $user = ['id' => $pdo->lastInsertId(), 'name' => $name];
  }
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
header('Location: /mywebsite/astrobite/index.php');
exit;
?>

