<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function requireLogin(): void {
  if (!isset($_SESSION['user_id'])) {
    header('Location: /mywebsite/astrobite/login.php');
    exit;
  }
}

function requireAdmin(PDO $pdo): void {
  requireLogin();
  $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
  $stmt->execute([$_SESSION['user_id']]);
  $role = $stmt->fetchColumn();
  if ($role !== 'admin') {
    http_response_code(403);
    echo 'Access denied';
    exit;
  }
}
?>

