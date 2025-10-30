-- User Seeder SQL
-- Note: This uses a pre-hashed password for 'admin123'
-- The hash was generated using PHP's password_hash() function
-- To generate a new hash, use the PHP script: sql/seed-users.php

-- Insert admin user
-- Password: admin123 (hashed with bcrypt)
INSERT IGNORE INTO users (name, email, password, role) 
VALUES (
    'Admin User',
    'admin@astrobite.com',
    '$2y$12$aOgeFkFKRobD38NHkWHxG.pA5MpYF1xFtJ2SbaUVzY5Rq2b/qY33i', -- admin123
    'admin'
);

-- Insert a regular test user (optional)
-- Password: user123
INSERT IGNORE INTO users (name, email, password, role) 
VALUES (
    'Test User',
    'user@astrobite.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- user123 (same hash for demo)
    'user'
);

