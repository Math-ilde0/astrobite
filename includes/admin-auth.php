<?php
/**
 * admin-auth.php - Lightweight Admin Authentication Guard
 * 
 * Quick authentication and authorization check for admin pages.
 * Verifies user is logged in AND has 'admin' role, redirects to login if not.
 * Simpler than auth.php requireAdmin() - does not query database (uses session role).
 * 
 * Session Requirements:
 * - user_id: Must be set (indicates logged-in user)
 * - user_role: Must equal 'admin' (set during login/session creation)
 * 
 * Behavior:
 * - If not logged in OR role not 'admin': redirect to login.php
 * - If authenticated AND admin: continue page execution
 * - Terminates script with exit after redirect
 * 
 * Usage: require_once 'includes/admin-auth.php'; (at top of admin pages)
 * 
 * Difference from auth.php:
 * - admin-auth.php: Session-based (no DB query, faster)
 * - auth.php requireAdmin(): Database query (verifies current role from DB)
 */

// -------------------------------------------------------
// Start Session
// -------------------------------------------------------
session_start();

// -------------------------------------------------------
// Verify Admin Authentication
// -------------------------------------------------------
// Check: user_id exists in session AND user_role equals 'admin'
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
  // Not authenticated or not admin - redirect to login
  header('Location: login.php');
  exit;
}

// If code reaches here: user is logged in AND has admin role
?>
