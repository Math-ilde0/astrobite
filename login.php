<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  if (!empty($email) && !empty($password)) {
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_name'] = $user['name'];
      header('Location: index.php');
      exit;
    } else {
      $error = 'Invalid email or password.';
    }
  } else {
    $error = 'Please fill in all fields.';
  }
}
?>

<main class="login-page">
  <h1>Login</h1>

  <form class="login-form" action="login.php" method="post">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required />

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required />

    <button type="submit">Login</button>
  </form>

  <div class="social-login">
    <p>Or continue with:</p>
    <a href="login-google.php" class="google-login">Login with Google</a>
    <a href="login-facebook.php" class="facebook-login">Login with Facebook</a>
  </div>

  <p class="register-link">Don't have an account? <a href="register.php">Register here</a>.</p>
</main>


<?php require_once 'includes/footer.php'; ?>
