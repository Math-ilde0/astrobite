<?php
/**
 * db.php - Database Connection Initialization
 * 
 * Establishes PDO connection to MySQL database with error handling.
 * Creates global $pdo object used by all application pages.
 * 
 * Configuration:
 * - Host: localhost
 * - Database: astrobite
 * - Charset: utf8 (UTF-8 support)
 * - Auth: root/root (MAMP default)
 * 
 * Error Handling:
 * - Throws PDOException on connection failure
 * - ERRMODE_EXCEPTION enables exception throwing for database errors
 * - Dies with error message if connection fails (security: shows only message)
 * 
 * Usage: require_once 'includes/db.php'; (creates $pdo globally)
 * Prepared Statements: All queries use parameterized statements ($pdo->prepare)
 */

// -------------------------------------------------------
// Database Credentials
// -------------------------------------------------------
$host = 'localhost';
$dbname = 'astrobite';
$username = 'root';
$password = 'root';

// -------------------------------------------------------
// Establish PDO Connection
// -------------------------------------------------------
try {
  // Create PDO connection with UTF-8 charset
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
  
  // Enable exception mode for error handling (throws PDOException on error)
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  // Connection failed - terminate with error message
  die("Connection failed: " . $e->getMessage());
}
?>
