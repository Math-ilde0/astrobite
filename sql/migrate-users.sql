-- Migration: Add name and role fields to existing users table
-- Run this if your users table already exists without these fields

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS name VARCHAR(255) AFTER id,
ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user' AFTER password,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- If name column doesn't exist and you need to add it
-- Note: This will set existing users' names to their email if name is NULL
UPDATE users SET name = email WHERE name IS NULL OR name = '';

