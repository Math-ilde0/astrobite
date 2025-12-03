<?php
/**
 * google_callback.php - Google OAuth2 Authentication Callback
 * 
 * Handles JWT credential from Google Sign-In, decodes payload, creates/updates user,
 * and establishes session. JWT (not token) sent via POST from Google Identity Services.
 * 
 * POST: credential (JWT from Google)
 * Dependencies: db.php (PDO)
 */

session_start();
require_once __DIR__ . '/../includes/db.php';

// -------------------------------------------------------
// 1) Verify Google Credential Received
// -------------------------------------------------------
// GIS sends POST, not GET
if (!isset($_POST['credential'])) {
    die("No credential received.");
}

$jwt = $_POST['credential'];

// -------------------------------------------------------
// 2) Decode JWT and Extract Payload
// -------------------------------------------------------
// JWT format: header.payload.signature (base64 encoded)
$parts = explode('.', $jwt);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

// Validate payload contains email
if (!$payload || !isset($payload['email'])) {
    die("Invalid token.");
}

// -------------------------------------------------------
// 3) Extract User Information from JWT
// -------------------------------------------------------
$providerId = $payload['sub'];           // Google's unique user ID
$email = $payload['email'];              // Email from Google
$name = $payload['name'] ?? 'Google User'; // Display name (or default)

// -------------------------------------------------------
// 4) Find Existing User by Google Provider ID
// -------------------------------------------------------
$stmt = $pdo->prepare('SELECT user_id, name FROM users WHERE provider = ? AND provider_id = ?');
$stmt->execute(['google', $providerId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // -------------------------------------------------------
    // 5) Fallback: Match Existing User by Email
    // -------------------------------------------------------
    $stmt = $pdo->prepare('SELECT user_id, name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Link Google provider to existing account
        $stmt = $pdo->prepare('UPDATE users SET provider = ?, provider_id = ? WHERE user_id = ?');
        $stmt->execute(['google', $providerId, $user['user_id']]);
    } else {
        // -------------------------------------------------------
        // 6) Create New User Account
        // -------------------------------------------------------
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, provider, provider_id, role) VALUES (?, ?, "", "google", ?, "user")');
        $stmt->execute([$name, $email, $providerId]);
        $user = ['user_id' => $pdo->lastInsertId(), 'name' => $name];
    }
}

// -------------------------------------------------------
// 7) Create Session and Redirect
// -------------------------------------------------------
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_name'] = $user['name'];

// Redirect to homepage
header('Location: /mywebsite/astrobite/index.php');
exit;
?>
