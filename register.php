<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  if ($name === '' || $email === '' || $password === '' || $confirm === '') {
    $error = 'Please fill in all fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email format.';
  } elseif (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } else {
    try {
      $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $error = 'An account with this email already exists.';
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, provider) VALUES (?, ?, ?, "user", "password")');
        $stmt->execute([$name, $email, $hash]);

        // Auto-login + flash confirm
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        $_SESSION['flash_success'] = 'Your account is ready. Welcome aboard!';

        header('Location: profile.php');
        exit;
      }
    } catch (PDOException $e) {
      $error = 'An error occurred. Please try again later.';
    }
  }
}

// Only include the header AFTER all redirects/POST logic
$pageTitle = 'Create Account — AstroBite';
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
.google-signin-label {
  font-size: 1rem;
  color: #444;
  margin-bottom: 0.5rem;
  text-align: center;
}
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
.custom-google-btn:hover {
  box-shadow: 0 2px 8px rgba(60,60,60,0.13);
  border-color: #aaa;
}
.google-logo {
  width: 18px;
  height: 18px;
  display: block;
}
</style>
<?php
?>

<main class="container login-page">
  <h1>Create Account</h1>

  <?php if ($error): ?>
    <p class="error-message"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form class="login-form" action="register.php" method="post" autocomplete="off">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required />

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required />

    <label for="confirm">Confirm Password</label>
    <input type="password" id="confirm" name="confirm" required />

    <button type="submit">Register</button>
  </form>

  <p class="register-link">
    Already have an account?
    <a href="login.php">Log in</a>.
  </p>
</main>

<?php require_once 'includes/footer.php'; ?>
