-- Create database (optional; create via phpMyAdmin if needed)
-- CREATE DATABASE IF NOT EXISTS pastry_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE pastry_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Profiles table
CREATE TABLE IF NOT EXISTS profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(80) NULL,
    postal_code VARCHAR(20) NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;