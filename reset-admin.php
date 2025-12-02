<?php
/**
 * reset-admin.php
 * Script de réinitialisation du compte admin
 * Supprime ce fichier après utilisation!
 */

require_once 'includes/db.php';

// Hash du mot de passe "password"
$password_hash = password_hash('password', PASSWORD_BCRYPT);

try {
  // Réinitialiser ou créer le compte admin
  $stmt = $pdo->prepare("
    INSERT INTO users (name, email, password, role, provider)
    VALUES (?, ?, ?, 'admin', 'password')
    ON DUPLICATE KEY UPDATE
      password = VALUES(password),
      role = 'admin',
      provider = 'password'
  ");
  
  $stmt->execute(['Admin User', 'admin@astrobite.com', $password_hash]);
  
  echo "<div style='background: #0a3c2a; color: #a3ff70; padding: 2rem; border-radius: 8px; max-width: 500px; margin: 2rem auto; font-family: Arial; text-align: center;'>";
  echo "<h1>✅ Succès!</h1>";
  echo "<p><strong>Email:</strong> admin@astrobite.com</p>";
  echo "<p><strong>Mot de passe:</strong> password</p>";
  echo "<p style='margin-top: 1.5rem; color: #ff7a7a;'><strong>⚠️ IMPORTANT:</strong> Supprime ce fichier (reset-admin.php) après l'avoir utilisé!</p>";
  echo "<p><a href='login.php' style='color: #5dd9ff; text-decoration: none; font-weight: bold;'>→ Aller à la page de login</a></p>";
  echo "</div>";
  
} catch (Exception $e) {
  echo "<div style='background: #3a0f12; color: #ff7a7a; padding: 2rem; border-radius: 8px; max-width: 500px; margin: 2rem auto; font-family: Arial;'>";
  echo "<h1>❌ Erreur</h1>";
  echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
  echo "</div>";
}
?>
