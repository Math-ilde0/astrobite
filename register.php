<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  if ($name === '' || $email === '' || $password === '' || $confirm === '') {
    $error = 'Please fill in all fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email format.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } else {
    try {
      $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $error = 'An account with this email already exists.';
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "user")');
        $stmt->execute([$name, $email, $hash]);
        $success = 'Account created! You can now login.';
      }
    } catch (PDOException $e) {
      $error = 'An error occurred. Please try again later.';
    }
  }
}
?>

<main class="container login-page">
  <h1>Create Account</h1>

  <?php if ($error): ?>
    <p class="error-message"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <?php if ($success): ?>
    <p class="success-message"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>

  <form class="login-form" action="register.php" method="post">
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
  <p class="register-link">Already have an account? <a href="login.php">Login</a>.</p>
</main>

<?php require_once 'includes/footer.php'; ?>

