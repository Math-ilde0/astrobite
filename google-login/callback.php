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

// Exchange code for tokens
$tokenResponse = null;
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
  CURLOPT_POSTFIELDS => http_build_query([
    'code' => $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
  ])
]);
$resp = curl_exec($ch);
if ($resp === false) { echo 'Token request failed'; exit; }
$tokenResponse = json_decode($resp, true);
curl_close($ch);

if (!isset($tokenResponse['access_token'])) { echo 'No access token'; exit; }

// Fetch user info
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenResponse['access_token']]
]);
$userInfoJson = curl_exec($ch);
if ($userInfoJson === false) { echo 'Userinfo request failed'; exit; }
$userInfo = json_decode($userInfoJson, true);
curl_close($ch);

// Create or find user
$providerId = $userInfo['sub'] ?? null;
$email = $userInfo['email'] ?? null;
$name = $userInfo['name'] ?? ($userInfo['given_name'] ?? 'Google User');

if (!$providerId || !$email) { echo 'Missing profile data'; exit; }

$stmt = $pdo->prepare('SELECT id, name FROM users WHERE provider = ? AND provider_id = ?');
$stmt->execute(['google', $providerId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  // If provider_id not found, fallback by email
  $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($user) {
    $stmt = $pdo->prepare('UPDATE users SET provider = ?, provider_id = ? WHERE id = ?');
    $stmt->execute(['google', $providerId, $user['id']]);
  } else {
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, provider, provider_id, role) VALUES (?, ?, "", "google", ?, "user")');
    $stmt->execute([$name, $email, $providerId]);
    $user = ['id' => $pdo->lastInsertId(), 'name' => $name];
  }
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
header('Location: /mywebsite/astrobite/index.php');
exit;
?>

