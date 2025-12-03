<?php
/**
 * auth.php - Authentication and Authorization Helpers
 * 
 * Provides functions for session management, user retrieval, login enforcement,
 * and admin role verification. Automatically starts session if not already active.
 * 
 * Functions:
 * - current_user(PDO): Get authenticated user data or null
 * - requireLogin(): Redirect to login if not authenticated
 * - requireAdmin(PDO): Redirect/403 if not admin role
 * 
 * Dependencies: PDO connection (db.php)
 */

// -------------------------------------------------------
// Start Session (if not already started)
// -------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// -------------------------------------------------------
// Function: Get Current Authenticated User
// -------------------------------------------------------
/**
 * Retrieves the authenticated user data from database.
 * Returns null if user_id not in session or user not found.
 * 
 * @param PDO $pdo Database connection
 * @return array|null User data (user_id, name, email, role) or null
 */
function current_user(PDO $pdo): ?array {
  // Return null if no user_id in session
  if (!isset($_SESSION['user_id'])) return null;
  
  // Query user by session user_id
  $stmt = $pdo->prepare('SELECT user_id, name, email, role FROM users WHERE user_id = ?');
  $stmt->execute([$_SESSION['user_id']]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);
  
  // Return user array or null (Elvis operator)
  return $u ?: null;
}

// -------------------------------------------------------
// Function: Enforce Login Requirement
// -------------------------------------------------------
/**
 * Redirects to login page if user not authenticated.
 * Preserves original URL in 'return' query parameter for redirect after login.
 * Terminates script execution with exit.
 * 
 * Usage: requireLogin(); at top of protected pages
 */
function requireLogin(): void {
  if (!isset($_SESSION['user_id'])) {
    // Capture current URL for post-login redirect
    $return = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header("Location: /mywebsite/astrobite/login.php?return=$return");
    exit;
  }
}

// -------------------------------------------------------
// Function: Enforce Admin Role Requirement
// -------------------------------------------------------
/**
 * Verifies user is logged in AND has 'admin' role.
 * Returns 403 Forbidden and terminates if role check fails.
 * 
 * @param PDO $pdo Database connection (to verify admin status)
 * 
 * Usage: requireAdmin($pdo); at top of admin-only pages
 */
function requireAdmin(PDO $pdo): void {
  // First ensure user is logged in
  requireLogin();
  
  // Query user's role
  $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = ?');
  $stmt->execute([$_SESSION['user_id']]);
  
  // Check if role is 'admin'
  if ($stmt->fetchColumn() !== 'admin') {
    // Not admin - return 403 and terminate
    http_response_code(403);
    echo 'Access denied';
    exit;
  }
}
