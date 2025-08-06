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
    `join_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel products
CREATE TABLE IF NOT EXISTS `products` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(500) NOT NULL,
    `price` INT NOT NULL,
    `digiflazz_code` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT,
    `brand` VARCHAR(255),
    `type` VARCHAR(255),
    `seller` VARCHAR(255),
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_brand` (`brand`),
    INDEX `idx_price` (`price`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel transactions
CREATE TABLE IF NOT EXISTS `transactions` (
    `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `product_id` INT NOT NULL,
    `amount` INT NOT NULL,
    `digiflazz_refid` VARCHAR(255) UNIQUE,
    `status` VARCHAR(50) DEFAULT 'pending',
    `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `target_id` VARCHAR(255),
    `response_data` TEXT,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`date`),
    INDEX `idx_refid` (`digiflazz_refid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel deposits
CREATE TABLE IF NOT EXISTS `deposits` (
    `deposit_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `amount` INT NOT NULL,
    `method` VARCHAR(100) DEFAULT 'bank_transfer',
    `status` VARCHAR(50) DEFAULT 'pending',
    `proof` TEXT,
    `admin_note` TEXT,
    `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `confirmed_at` TIMESTAMP NULL,
    `confirmed_by` BIGINT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel settings
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_name` VARCHAR(255) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `description` TEXT,
    `updated_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel user_sessions (untuk tracking conversation state)
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `session_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `session_key` VARCHAR(255) NOT NULL,
    `session_data` TEXT,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_user_session` (`user_id`, `session_key`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `settings` (`setting_name`, `setting_value`, `description`) VALUES 
('margin_percentage', '10', 'Margin keuntungan dalam persen'),
('min_deposit', '10000', 'Minimum amount untuk deposit'),
('max_deposit', '10000000', 'Maximum amount untuk deposit'),
('bot_maintenance', '0', 'Status maintenance bot (0=normal, 1=maintenance)'),
('welcome_message', 'Selamat datang di Bot Pulsa & PPOB Digital!', 'Pesan selamat datang'),
('bank_info', 'BCA|1234567890|Admin Bot', 'Info rekening bank (format: BANK|NOMOR|NAMA)')
ON DUPLICATE KEY UPDATE 
`setting_value` = VALUES(`setting_value`),
`description` = VALUES(`description`);

-- Insert admin user (ganti dengan ID Telegram Anda)
INSERT INTO `users` (`user_id`, `username`, `first_name`, `is_admin`) 
VALUES (7044289974, 'admin_username', 'Admin', 1) 
ON DUPLICATE KEY UPDATE 
`is_admin` = VALUES(`is_admin`),
`username` = VALUES(`username`),
`first_name` = VALUES(`first_name`);

-- Create indexes for better performance
ALTER TABLE `users` ADD INDEX `idx_join_date` (`join_date`);
ALTER TABLE `transactions` ADD INDEX `idx_amount` (`amount`);
ALTER TABLE `deposits` ADD INDEX `idx_amount` (`amount`);

-- Create views for easier reporting
CREATE OR REPLACE VIEW `transaction_summary` AS
SELECT 
    DATE(t.date) as transaction_date,
    COUNT(*) as total_transactions,
    SUM(t.amount) as total_amount,
    COUNT(CASE WHEN t.status = 'success' THEN 1 END) as success_count,
    COUNT(CASE WHEN t.status = 'failed' THEN 1 END) as failed_count,
    COUNT(CASE WHEN t.status = 'pending' THEN 1 END) as pending_count
FROM transactions t
GROUP BY DATE(t.date)
ORDER BY transaction_date DESC;

CREATE OR REPLACE VIEW `user_stats` AS
SELECT 
    u.user_id,
    u.username,
    u.first_name,
    u.balance,
    u.join_date,
    COUNT(t.transaction_id) as total_transactions,
    COALESCE(SUM(t.amount), 0) as total_spent,
    COUNT(d.deposit_id) as total_deposits,
    COALESCE(SUM(CASE WHEN d.status = 'confirmed' THEN d.amount ELSE 0 END), 0) as total_deposited
FROM users u
LEFT JOIN transactions t ON u.user_id = t.user_id
LEFT JOIN deposits d ON u.user_id = d.user_id
GROUP BY u.user_id, u.username, u.first_name, u.balance, u.join_date;

-- Set proper charset for existing tables
ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `products` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `transactions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `deposits` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `settings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_sessions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;