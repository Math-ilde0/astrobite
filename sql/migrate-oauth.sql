-- Migration: Add social login columns
ALTER TABLE users 
  ADD COLUMN IF NOT EXISTS provider VARCHAR(20) NULL AFTER password,
  ADD COLUMN IF NOT EXISTS provider_id VARCHAR(255) NULL UNIQUE AFTER provider;

-- Backfill provider for existing email/password users
UPDATE users SET provider = COALESCE(provider, 'password');


