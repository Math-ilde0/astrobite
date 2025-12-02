<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function current_user(PDO $pdo): ?array {
  if (!isset($_SESSION['user_id'])) return null;
  $stmt = $pdo->prepare('SELECT user_id, name, email, role FROM users WHERE user_id = ?');
  $stmt->execute([$_SESSION['user_id']]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);
  return $u ?: null;
}

function requireLogin(): void {
  if (!isset($_SESSION['user_id'])) {
    $return = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header("Location: /mywebsite/astrobite/login.php?return=$return");
    exit;
  }
}

function requireAdmin(PDO $pdo): void {
  requireLogin();
  $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = ?');
  $stmt->execute([$_SESSION['user_id']]);
  if ($stmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    echo 'Access denied';
    exit;
  }
}
