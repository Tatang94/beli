-- Database schema untuk Telegram Bot PHP
-- Jalankan script ini di phpMyAdmin atau MySQL

CREATE DATABASE IF NOT EXISTS `telegram_bot` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `telegram_bot`;

-- Tabel users
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` BIGINT PRIMARY KEY,
    `username` VARCHAR(255) DEFAULT NULL,
    `first_name` VARCHAR(255) DEFAULT NULL,
    `last_name` VARCHAR(255) DEFAULT NULL,
    `balance` INT DEFAULT 0,
    `is_admin` TINYINT(1) DEFAULT 0,
    `join_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel products
CREATE TABLE IF NOT EXISTS `products` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(500) NOT NULL,
    `price` INT NOT NULL,
    `digiflazz_code` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `brand` VARCHAR(255),
    `type` VARCHAR(255),
    `seller` VARCHAR(255),
    INDEX `idx_type` (`type`),
    INDEX `idx_brand` (`brand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel transactions
CREATE TABLE IF NOT EXISTS `transactions` (
    `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `product_id` INT NOT NULL,
    `amount` INT NOT NULL,
    `digiflazz_refid` VARCHAR(255),
    `status` VARCHAR(50) DEFAULT 'pending',
    `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `target_id` VARCHAR(255),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel deposits
CREATE TABLE IF NOT EXISTS `deposits` (
    `deposit_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `amount` INT NOT NULL,
    `method` VARCHAR(100) DEFAULT 'bank_transfer',
    `status` VARCHAR(50) DEFAULT 'pending',
    `proof` TEXT,
    `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel settings
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_name` VARCHAR(255) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `updated_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default margin setting
INSERT INTO `settings` (`setting_name`, `setting_value`) 
VALUES ('margin_percentage', '10') 
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Insert admin user (ganti dengan ID Telegram Anda)
INSERT INTO `users` (`user_id`, `username`, `first_name`, `is_admin`) 
VALUES (7044289974, 'admin_username', 'Admin', 1) 
ON DUPLICATE KEY UPDATE `is_admin` = VALUES(`is_admin`);