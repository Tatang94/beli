# 📋 Panduan Instalasi Bot Telegram PHP di cPanel

## Langkah 1: Persiapan Database

### 1.1 Buat Database di cPanel
1. Login ke cPanel hosting Anda
2. Cari dan klik **"MySQL Databases"**
3. Di bagian **"Create New Database"**:
   - Masukkan nama database: `botpulsa` (atau nama lain)
   - Klik **"Create Database"**

### 1.2 Buat User Database
1. Di bagian **"MySQL Users"**:
   - Username: `botuser` (atau nama lain)
   - Password: buat password yang kuat
   - Klik **"Create User"**

### 1.3 Berikan Privileges ke User
1. Di bagian **"Add User to Database"**:
   - Pilih user yang baru dibuat
   - Pilih database yang baru dibuat
   - Klik **"Add"**
2. Centang **"ALL PRIVILEGES"**
3. Klik **"Make Changes"**

### 1.4 Import Database Schema
1. Klik **"phpMyAdmin"** di cPanel
2. Pilih database yang baru dibuat
3. Klik tab **"Import"**
4. Klik **"Choose File"** dan pilih file `setup/database.sql`
5. Klik **"Go"** untuk mengimport
6. Pastikan muncul pesan sukses tanpa error

## Langkah 2: Upload dan Konfigurasi File

### 2.1 Upload File ke Hosting
1. Buka **"File Manager"** di cPanel
2. Masuk ke folder `public_html`
3. Upload semua file dari folder `php_bot/` 
4. Extract jika dalam bentuk zip

### 2.2 Edit Konfigurasi
1. Buka file `config.php`
2. Edit bagian database sesuai yang dibuat tadi:

```php
// Ganti dengan detail database Anda
define('DB_HOST', 'localhost');
define('DB_NAME', 'namauser_botpulsa');     // Format: cpaneluser_namadb
define('DB_USER', 'namauser_botuser');      // Format: cpaneluser_namauser
define('DB_PASS', 'password_anda');         // Password yang dibuat tadi
```

3. Edit pengaturan bot:
```php
// Ganti dengan token bot Anda dari @BotFather
define('BOT_TOKEN', 'XXXXXXXXX:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

// Ganti dengan credentials Digiflazz Anda
define('DIGIFLAZZ_USERNAME', 'username_digiflazz_anda');
define('DIGIFLAZZ_KEY', 'api_key_digiflazz_anda');

// Ganti dengan ID Telegram Anda (dapatkan dari @userinfobot)
define('ADMIN_IDS', [123456789]);

// Ganti dengan domain hosting Anda
define('WEBHOOK_URL', 'https://domain-anda.com/webhook');
```

4. Edit info rekening bank:
```php
define('BANK_NAME', 'Bank BCA');
define('BANK_ACCOUNT', '1234567890');
define('BANK_HOLDER', 'Nama Pemilik Rekening');
```

## Langkah 3: Setup Webhook

### 3.1 Akses Setup Webhook
1. Buka browser dan akses: `https://domain-anda.com/setup/webhook_setup.php`
2. Periksa semua status harus hijau (✅)
3. Jika ada yang merah (❌), perbaiki konfigurasi terlebih dahulu

### 3.2 Setup Webhook
1. Pastikan semua konfigurasi sudah benar
2. Klik tombol **"Setup Webhook"**
3. Tunggu hingga muncul pesan sukses
4. Periksa status webhook harus aktif

## Langkah 4: Update Produk

### 4.1 Update Produk dari Digiflazz
1. Akses: `https://domain-anda.com/admin/update_products.php`
2. Klik **"Test API Digiflazz"** untuk memastikan API berfungsi
3. Jika API OK, klik **"Update Produk Sekarang"**
4. Tunggu proses selesai (bisa 1-5 menit)
5. Pastikan muncul pesan sukses dengan jumlah produk yang diupdate

## Langkah 5: Test Bot

### 5.1 Test Functionality
1. Buka Telegram dan cari bot Anda: `@username_bot_anda`
2. Kirim pesan `/start`
3. Bot harus merespon dengan menu utama
4. Test beberapa menu untuk memastikan berfungsi

### 5.2 Test Admin Menu
1. Kirim `/start` ke bot
2. Harus muncul menu "👑 Admin Menu" (karena ID Anda sudah diset sebagai admin)
3. Test menu admin seperti statistik dan update produk

## Langkah 6: Keamanan dan Cleanup

### 6.1 Hapus File Setup (PENTING!)
Setelah semua berfungsi normal:
1. Hapus folder `setup/` untuk keamanan
2. Atau pindahkan ke folder private di luar public_html

### 6.2 Setup Cron Job (Opsional)
Untuk update produk otomatis:
1. Masuk ke **"Cron Jobs"** di cPanel
2. Tambahkan cron job baru:
   ```
   0 6 * * * curl -s "https://domain-anda.com/admin/update_products.php?action=update&key=KEY_ANDA"
   ```
3. Ganti `KEY_ANDA` dengan key yang ditampilkan di halaman update_products.php

## ✅ Checklist Instalasi

- [ ] Database MySQL dibuat dan user memiliki privileges
- [ ] File `database.sql` berhasil diimport tanpa error
- [ ] File bot sudah diupload ke hosting
- [ ] File `config.php` sudah dikonfigurasi dengan benar
- [ ] Webhook setup berhasil (status hijau semua)
- [ ] Produk berhasil diupdate dari Digiflazz
- [ ] Bot merespon `/start` dengan menu utama
- [ ] Admin menu muncul dan berfungsi
- [ ] Folder `setup/` sudah dihapus untuk keamanan

## 🚨 Troubleshooting

### Database Error
- Periksa nama database, username, dan password di `config.php`
- Pastikan format nama sesuai cPanel: `cpaneluser_namadb`
- Cek apakah semua tabel sudah terimport dengan benar

### Bot Tidak Merespon
- Cek webhook status di `setup/webhook_setup.php`
- Pastikan BOT_TOKEN benar dan bot sudah diaktifkan
- Cek error log di cPanel File Manager

### API Digiflazz Error
- Test API di `admin/update_products.php`
- Pastikan username dan API key Digiflazz benar
- Cek saldo Digiflazz mencukupi

### SSL/HTTPS Error
- Pastikan domain sudah memiliki SSL certificate yang valid
- Jika menggunakan CloudFlare, pastikan SSL mode "Full"

## 📞 Bantuan Lebih Lanjut

Jika masih ada masalah:
1. Cek error log di cPanel → "Error Logs"
2. Screenshot error dan kirimkan untuk debugging
3. Pastikan semua langkah sudah diikuti dengan benar

**Selamat! Bot Telegram PHP Anda sudah siap digunakan! 🎉**