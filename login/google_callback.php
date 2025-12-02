<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// GIS sends POST, not GET
if (!isset($_POST['credential'])) {
    die("No credential received.");
}

$jwt = $_POST['credential'];

// Decode JWT (Google Identity Services)
$parts = explode('.', $jwt);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

if (!$payload || !isset($payload['email'])) {
    die("Invalid token.");
}

$providerId = $payload['sub'];
$email = $payload['email'];
$name = $payload['name'] ?? 'Google User';

// Look for existing user

$stmt = $pdo->prepare('SELECT user_id, name FROM users WHERE provider = ? AND provider_id = ?');
$stmt->execute(['google', $providerId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // fallback: match by email
    $stmt = $pdo->prepare('SELECT user_id, name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stmt = $pdo->prepare('UPDATE users SET provider = ?, provider_id = ? WHERE user_id = ?');
        $stmt->execute(['google', $providerId, $user['user_id']]);
    } else {
        // Create new user
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, provider, provider_id, role) VALUES (?, ?, "", "google", ?, "user")');
        $stmt->execute([$name, $email, $providerId]);
        $user = ['user_id' => $pdo->lastInsertId(), 'name' => $name];
    }
}

// Login
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_name'] = $user['name'];

// Success
header('Location: /mywebsite/astrobite/index.php');
exit;
?>
