<?php
/**
 * User Seeder - Run this script to create admin user
 * Usage: php sql/seed-users.php
 */

require_once __DIR__ . '/../includes/db.php';

// Admin user credentials
$adminEmail = 'admin@astrobite.com';
$adminPassword = 'admin123'; // Change this after first login!
$adminName = 'Admin User';
$adminRole = 'admin';

try {
    // Hash the password
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "❌ Admin user already exists with email: $adminEmail\n";
        echo "Would you like to update the password? (This script does not handle updates)\n";
        exit(1);
    }
    
    // Insert admin user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$adminName, $adminEmail, $hashedPassword, $adminRole]);
    
    echo "✅ Admin user created successfully!\n";
    echo "📧 Email: $adminEmail\n";
    echo "🔑 Password: $adminPassword\n";
    echo "⚠️  Please change the password after first login!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

