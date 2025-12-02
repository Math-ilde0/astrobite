<?php
/**
 * includes/admin-auth.php
 * Quick admin authentication check
 */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
  // Redirect to login
  header('Location: ' . (isset($_SESSION['user_id']) ? 'login.php' : 'login.php'));
  exit;
}
?>
