<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email !== '' && $password !== '') {
    $stmt = $pdo->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
      session_regenerate_id(true);
      $_SESSION['user_id'] = (int)$user['user_id'];
      $_SESSION['user_name'] = $user['name'] ?? '';
      $_SESSION['user_role'] = $user['role'] ?? 'user';
      header('Location: profile.php');
      exit;
    } else {
      $error = 'Invalid email or password.';
    }
  } else {
    $error = 'Please fill in all fields.';
  }
}

// Only include the header AFTER all redirects/POST logic
$pageTitle = 'Login — AstroBite';
require_once 'includes/header.php';
?>

<!-- Google Sign-In script removed for custom button -->
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
  <h1>Login</h1>

  <?php if ($error): ?>
    <p class="error-message"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>


  <form class="login-form" action="login.php" method="post" autocomplete="off">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required />

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required />

    <button type="submit">Login</button>
  </form>

  <div class="google-signin-container">
    <span class="google-signin-label">Or log in with Google</span>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <div id="g_id_onload"
      data-client_id="462362756218-jo9mpdum3amaruue21ibblqkngci3i8h.apps.googleusercontent.com"
      data-login_uri="http://localhost:8889/mywebsite/astrobite/login/google_callback.php"
      data-locale="en">
    </div>
    <div class="g_id_signin"
      data-type="standard"
      data-size="large"
      data-theme="outline"
      data-text="signin_with"
      data-shape="rectangular"
      data-locale="en">
    </div>
  </div>

  <p class="register-link">
    Don’t have an account?
    <a href="register.php">Create one</a>.
  </p>
</main>

<?php require_once 'includes/footer.php'; ?>
