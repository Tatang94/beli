# Bot Telegram PHP untuk Hosting cPanel

Versi PHP dari bot Telegram untuk penjualan produk digital (pulsa & PPOB) yang dapat dijalankan di hosting shared cPanel.

## 📋 Fitur

- ✅ Menu interaktif dengan inline keyboard
- ✅ Sistem user registration otomatis
- ✅ Manajemen saldo user
- ✅ Katalog produk dari Digiflazz API
- ✅ Sistem deposit dengan konfirmasi admin
- ✅ Panel admin lengkap
- ✅ Update produk otomatis
- ✅ Transaksi realtime melalui Digiflazz
- ✅ Setting margin profit
- ✅ Statistik bot

## 🚀 Cara Install di cPanel

### 1. Persiapan

1. **Buat Database MySQL di cPanel:**
   - Masuk ke cPanel → MySQL Databases
   - Buat database baru (contoh: `username_botdb`)
   - Buat user database dan berikan semua privileges

2. **Upload File ke Hosting:**
   - Upload semua file PHP ke folder public_html atau subdirectory
   - Pastikan semua file terupload dengan benar

### 2. Konfigurasi Database

1. **Import Database:**
   - Masuk ke phpMyAdmin
   - Pilih database yang sudah dibuat
   - Import file `database.sql`

2. **Edit Config:**
   - Buka file `config.php`
   - Sesuaikan pengaturan database:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'cpanel_username_botdb');  // Nama database Anda
   define('DB_USER', 'cpanel_username_user');   // Username database
   define('DB_PASS', 'password_database');      // Password database
   ```

### 3. Konfigurasi Bot

1. **Setting Bot Token:**
   - Buat bot baru di @BotFather
   - Copy token dan paste ke `config.php`:
   ```php
   define('BOT_TOKEN', 'TOKEN_BOT_ANDA');
   ```

2. **Setting Digiflazz API:**
   - Daftar di Digiflazz.com
   - Dapatkan username dan API key
   - Masukkan ke `config.php`:
   ```php
   define('DIGIFLAZZ_USERNAME', 'username_digiflazz');
   define('DIGIFLAZZ_KEY', 'api_key_digiflazz');
   ```

3. **Setting Admin:**
   - Dapatkan ID Telegram Anda dari @userinfobot
   - Masukkan ke `config.php`:
   ```php
   define('ADMIN_IDS', [123456789]); // ID Telegram Anda
   ```

### 4. Setup Webhook

1. **Jalankan Setup Webhook:**
   - Buka browser: `https://domain-anda.com/webhook_setup.php`
   - Klik "Setup Webhook"
   - Pastikan webhook berhasil diatur

2. **Test Bot:**
   - Chat bot Anda di Telegram
   - Ketik `/start`
   - Bot harus merespon dengan menu utama

### 5. Update Produk

1. **Update Manual:**
   - Buka: `https://domain-anda.com/update_products.php`
   - Klik "Update Produk Sekarang"
   - Tunggu sampai selesai

2. **Setup Auto Update (Opsional):**
   - Masuk ke cPanel → Cron Jobs
   - Tambahkan cron job:
   ```
   0 6 * * * /usr/local/bin/php /path/to/your/update_products.php
   ```

## 📁 Struktur File

```
/
├── bot.php              # File utama bot
├── config.php           # Konfigurasi
├── database.sql         # Schema database
├── webhook_setup.php    # Setup webhook
├── update_products.php  # Update produk
├── .htaccess           # Konfigurasi Apache
└── README_PHP.md       # Panduan ini
```

## ⚙️ Konfigurasi Penting

### config.php
```php
// Bot Token dari @BotFather
define('BOT_TOKEN', 'YOUR_BOT_TOKEN');

// API Digiflazz
define('DIGIFLAZZ_USERNAME', 'your_username');
define('DIGIFLAZZ_KEY', 'your_api_key');

// Admin IDs
define('ADMIN_IDS', [YOUR_TELEGRAM_ID]);

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// Webhook URL
define('WEBHOOK_URL', 'https://your-domain.com/webhook');
```

## 🔧 Troubleshooting

### Bot Tidak Merespon
1. Cek webhook status di `webhook_setup.php`
2. Cek error log di cPanel
3. Pastikan SSL certificate valid
4. Cek token bot di config.php

### Database Error
1. Pastikan database sudah dibuat
2. Cek koneksi database di config.php
3. Import ulang database.sql
4. Cek privileges user database

### API Digiflazz Error
1. Cek username dan key di config.php
2. Pastikan akun Digiflazz aktif
3. Cek saldo Digiflazz
4. Test API di update_products.php

## 🔐 Keamanan

1. **Hapus file setup setelah selesai:**
   ```bash
   rm webhook_setup.php
   rm update_products.php  # atau pindah ke folder private
   ```

2. **Protect file sensitif dengan .htaccess:**
   ```apache
   <Files "config.php">
       Order allow,deny
       Deny from all
   </Files>
   ```

3. **Gunakan environment variables jika hosting mendukung**

## 📞 Support

Jika mengalami masalah:
1. Cek error log di cPanel
2. Pastikan semua konfigurasi sudah benar
3. Test step by step dari webhook setup
4. Cek dokumentasi Digiflazz API

## 🚨 Catatan Penting

- **WAJIB ganti semua konfigurasi di config.php**
- **Jangan share token bot dan API key**
- **Backup database secara berkala**
- **Monitor error log secara rutin**
- **Update produk minimal 1x sehari**

## 📈 Optimasi

1. **Setup Cron Job untuk update produk otomatis**
2. **Gunakan CDN jika traffic tinggi**
3. **Optimize database dengan index**
4. **Monitor resource usage di cPanel**

Selamat menggunakan Bot Telegram PHP! 🎉