<?php
/**
 * register.php
 * 
 * User Registration Page
 * 
 * Purpose:
 * - Provides user registration form for creating new accounts
 * - Handles account creation with validation and security checks
 * - Implements password hashing and email uniqueness validation
 * - Auto-logs in user upon successful registration
 * 
 * Key Features:
 * - Form validation (name, email, password requirements)
 * - Email format validation using PHP filter functions
 * - Secure password hashing using PASSWORD_DEFAULT (bcrypt)
 * - Duplicate email prevention (unique constraint)
 * - Session regeneration for security after registration
 * - Flash message notification system
 * 
 * Security Measures:
 * - Parameterized queries (PDO prepared statements) prevent SQL injection
 * - Password hashing with bcrypt before database storage
 * - Session regeneration after successful login to prevent fixation attacks
 * - HTML escaping for error output prevents XSS
 * 
 * Dependencies:
 * - includes/db.php: Database connection ($pdo)
 * - includes/header.php: Page header with navigation
 * - includes/footer.php: Page footer
 * - assets/css/style.css: All styling
 */

session_start();
require_once 'includes/db.php';

// Initialize error message variable for form feedback
$error = '';

// ========== HANDLE REGISTRATION FORM SUBMISSION ==========
// Process POST request when user submits the registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Extract and trim form input to remove whitespace
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  // ========== VALIDATION CHECKS ==========
  // Check if all required fields are filled
  if ($name === '' || $email === '' || $password === '' || $confirm === '') {
    $error = 'Please fill in all fields.';
  } 
  // Validate email format using PHP's built-in email validation filter
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email format.';
  } 
  // Enforce minimum password length for security
  elseif (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters.';
  } 
  // Verify password and confirmation match
  elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } 
  // If all validations pass, proceed with registration
  else {
    try {
      // Check if email already exists in database to prevent duplicate accounts
      $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $error = 'An account with this email already exists.';
      } else {
        // Hash password using bcrypt (PASSWORD_DEFAULT algorithm)
        // This is the secure way to store passwords - never store plain text
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user record with hashed password
        // Default role is "user" and provider is "password" (not OAuth)
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, provider) VALUES (?, ?, ?, "user", "password")');
        $stmt->execute([$name, $email, $hash]);

        // ========== AUTO-LOGIN NEW USER ==========
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        // Store user info in session for authenticated pages to access
        $_SESSION['user_id'] = (int)$pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        // Set flash success message to display on next page load
        $_SESSION['flash_success'] = 'Your account is ready. Welcome aboard!';

        // Redirect to profile page after successful registration
        header('Location: profile.php');
        exit;
      }
    } catch (PDOException $e) {
      // Catch database errors and show generic message to user
      $error = 'An error occurred. Please try again later.';
    }
  }
}

// ========== PAGE HEADER ==========
// Include header AFTER all POST processing and redirects
// This ensures headers can be modified before HTML output begins
$pageTitle = 'Create Account — AstroBite';
require_once 'includes/header.php';
?>
<!-- ========== GOOGLE SIGN-IN STYLING ==========
     Custom button styling for Google OAuth integration
     Note: Google Sign-In script removed - custom button only
     Can be repurposed for other OAuth provider integration -->
<style>
/* Container for Google sign-in options, centered below main form */
.google-signin-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 2rem;
  margin-bottom: 2rem;
}

/* Label text above Google sign-in button */
.google-signin-label {
  font-size: 1rem;
  color: #444;
  margin-bottom: 0.5rem;
  text-align: center;
}

/* Custom Google sign-in button styling */
/* Mimics standard OAuth button design with hover effects */
.custom-google-btn {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 0.5rem 1.2rem;
  display: flex;
  align-items: center;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(60,60,60,0.07);
  transition: box-shadow 0.2s;
  margin-top: 0.5rem;
}

/* Enhanced shadow and border on hover for visual feedback */
.custom-google-btn:hover {
  box-shadow: 0 2px 8px rgba(60,60,60,0.13);
  border-color: #aaa;
}

/* Google logo icon styling */
.google-logo {
  width: 18px;
  height: 18px;
  display: block;
}
</style>
<?php
?>

<!-- ========== REGISTRATION FORM ==========
     User account creation form with validation feedback
     Uses POST method for secure form submission -->
<main class="container login-page">
  <h1>Create Account</h1>

  <!-- Display error message if validation fails or registration error occurs -->
  <?php if ($error): ?>
    <p class="error-message"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <!-- Registration form submission handler: register.php POST method -->
  <!-- autocomplete="off" prevents browser from autofilling sensitive fields -->
  <form class="login-form" action="register.php" method="post" autocomplete="off">
    <!-- User full name input field -->
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required />

    <!-- User email input field (must be unique in database) -->
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required />

    <!-- Password input field (minimum 8 characters enforced server-side) -->
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required />

    <!-- Password confirmation field (must match password field) -->
    <label for="confirm">Confirm Password</label>
    <input type="password" id="confirm" name="confirm" required />

    <!-- Submit button to register user account -->
    <button type="submit">Register</button>
  </form>

  <!-- Link to login page for existing users -->
  <p class="register-link">
    Already have an account?
    <a href="login.php">Log in</a>.
  </p>
</main>

<!-- Include page footer with site information -->
