<?php
/**
 * logout.php - User Logout Handler
 * 
 * Purpose: Handle user logout by clearing session data and cookies
 * 
 * Process:
 * 1. Destroy all session data from $_SESSION array
 * 2. Clear session cookie from browser (if cookies are enabled)
 * 3. Destroy server-side session
 * 4. Redirect to homepage
 * 
 * Security:
 * - Clears all session variables
 * - Properly removes session cookie with same parameters as creation
 * - Destroys server-side session data
 * - No HTML output (prevents output buffering issues)
 * 
 * Note: This is a simple redirect script with no user-facing content
 */

session_start();

// -------------------------------------------------------
// 1) Clear All Session Data
// -------------------------------------------------------
// Empty the $_SESSION array to remove all stored user data
$_SESSION = [];

// -------------------------------------------------------
// 2) Destroy Session Cookie (if cookies enabled)
// -------------------------------------------------------
// Remove the session cookie from the user's browser
if (ini_get("session.use_cookies")) {
  // Get current session cookie parameters for secure removal
  $params = session_get_cookie_params();
  
  // Set cookie with expiration in the past to delete it
  setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
  );
}

// -------------------------------------------------------
// 3) Destroy Server-Side Session
// -------------------------------------------------------
// Delete session data from server (temp directory)
session_destroy();

// -------------------------------------------------------
// 4) Redirect to Homepage
// -------------------------------------------------------
// Send user back to homepage after logout
header('Location: /mywebsite/astrobite/index.php');
exit;
