<?php
/**
 * login.php - User Login Page
 * 
 * Purpose: Authenticate user credentials and establish session
 * 
 * Features:
 * - Email/password authentication form
 * - Secure password verification using bcrypt
 * - Session regeneration on successful login
 * - User role and name stored in session
 * - Google OAuth2 sign-in integration
 * - Form validation (email and password required)
 * - Error messaging for failed login attempts
 * 
 * Security Measures:
 * - Prepared statements prevent SQL injection
 * - password_verify() for secure password comparison
 * - Session regeneration prevents session fixation attacks
 * - No password exposure in error messages (generic "Invalid email or password")
 * - htmlspecialchars() escaping on error display for XSS prevention
 * 
 * POST Parameters:
 * - email: User email address
 * - password: User password (plaintext, compared against bcrypt hash)
 * 
 * Session Variables Created on Success:
 * - user_id: Unique user identifier
 * - user_name: User's display name
 * - user_role: User role (admin, user, etc.)
 * 
 * Dependencies: db.php (PDO), header.php, footer.php
 * OAuth: Google Sign-In API (client ID configured)
 */

session_start();
require_once 'includes/db.php';

// -------------------------------------------------------
// 1) Initialize Error Message Variable
// -------------------------------------------------------
$error = '';

// -------------------------------------------------------
// 2) Handle POST Form Submission
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get and trim email input
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  // -------------------------------------------------------
  // 3) Validate Form Fields
  // -------------------------------------------------------
  if ($email !== '' && $password !== '') {
    // -------------------------------------------------------
    // 4) Query User by Email
    // -------------------------------------------------------
    // Fetch user credentials from database
    $stmt = $pdo->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // -------------------------------------------------------
    // 5) Verify Password & Create Session
    // -------------------------------------------------------
    // Use password_verify() to compare plaintext password against bcrypt hash
    if ($user && password_verify($password, $user['password'])) {
      // Regenerate session ID to prevent session fixation attacks
      session_regenerate_id(true);
      
      // Store user information in session for subsequent requests
      $_SESSION['user_id'] = (int)$user['user_id'];
      $_SESSION['user_name'] = $user['name'] ?? '';
      $_SESSION['user_role'] = $user['role'] ?? 'user';
      
      // Redirect to profile page after successful login
      header('Location: profile.php');
      exit;
    } else {
      // Generic error message prevents user enumeration attacks
      $error = 'Invalid email or password.';
    }
  } else {
    // Show error if required fields are empty
    $error = 'Please fill in all fields.';
  }
}

// -------------------------------------------------------
// 6) Prepare SEO Metadata
// -------------------------------------------------------
// Only include header AFTER all POST logic/redirects (prevents output buffering issues)
$pageTitle = 'Login — AstroBite';
require_once 'includes/header.php';
?>

<!-- Google Sign-In styles -->
<style>
.google-signin-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 2rem;
  margin-bottom: 2rem;
}
</style>

<main class="login-page container">
  <!-- ========== LOGIN FORM TITLE ========== -->
  <h1>Login</h1>

  <!-- ========== ERROR MESSAGE DISPLAY ========== -->
  <!-- Shows validation or authentication errors if any -->
  <?php if ($error): ?>
    <p class="error-message"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <!-- ========== EMAIL/PASSWORD LOGIN FORM ========== -->
  <!-- Traditional email/password authentication -->
  <form class="login-form" action="login.php" method="post" autocomplete="off">
    <!-- Email input field -->
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required />

    <!-- Password input field -->
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required />

    <!-- Login submit button -->
    <button type="submit">Login</button>
  </form>

  <!-- ========== GOOGLE OAUTH2 SIGN-IN ========== -->
  <!-- Alternative authentication using Google credentials -->
  <div class="google-signin-container">
    <span class="google-signin-label">Or log in with Google</span>
    <!-- Google Sign-In JavaScript SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <!-- Google Sign-In container with client configuration -->
    <div id="g_id_onload"
      data-client_id="462362756218-jo9mpdum3amaruue21ibblqkngci3i8h.apps.googleusercontent.com"
      data-login_uri="http://localhost:8889/mywebsite/astrobite/login/google_callback.php"
      data-locale="en">
    </div>
    <!-- Google Sign-In button (styled) -->
    <div class="g_id_signin"
      data-type="standard"
      data-size="large"
      data-theme="outline"
      data-text="signin_with"
      data-shape="rectangular"
      data-locale="en">
    </div>
  </div>

  <!-- ========== REGISTRATION LINK ========== -->
  <!-- Quick link to create new account if user is not registered -->
  <p class="register-link">
    Don't have an account?
    <a href="register.php">Create one</a>.
  </p>
</main>

<?php require_once 'includes/footer.php'; ?>
