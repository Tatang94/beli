<?php
/**
 * Konfigurasi Bot Telegram PHP
 * Sesuaikan dengan pengaturan hosting cPanel Anda
 */

// Konfigurasi Bot Token - Menggunakan environment variables untuk keamanan
define('BOT_TOKEN', $_ENV['TELEGRAM_BOT_TOKEN'] ?? '');

// Konfigurasi Digiflazz API - Menggunakan environment variables untuk keamanan
define('DIGIFLAZZ_USERNAME', $_ENV['DIGIFLAZZ_USERNAME'] ?? '');
define('DIGIFLAZZ_KEY', $_ENV['DIGIFLAZZ_KEY'] ?? '');

// ID Admin Telegram (WAJIB DIGANTI dengan ID Telegram Anda!)
define('ADMIN_IDS', [7044289974]);

// Konfigurasi Database (Sesuaikan dengan cPanel Anda)
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpanel_username_botdb'); // Ganti dengan nama database Anda
define('DB_USER', 'cpanel_username_user');  // Ganti dengan username database Anda
define('DB_PASS', 'password_database');     // Ganti dengan password database Anda

// Konfigurasi Timezone
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Error Reporting (Matikan di production)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// URL Webhook (Sesuaikan dengan domain Anda)
define('WEBHOOK_URL', 'https://domain-anda.com/webhook');

// Rekening Bank untuk Deposit (WAJIB DIGANTI!)
define('BANK_NAME', 'Bank BCA');
define('BANK_ACCOUNT', '1234567890');
define('BANK_HOLDER', 'Nama Pemilik Rekening');
?>