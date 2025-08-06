# Bot Telegram PHP untuk Hosting cPanel

Versi PHP lengkap dari bot Telegram untuk penjualan produk digital (pulsa & PPOB) yang dapat dijalankan di hosting shared cPanel dengan struktur yang terorganisir.

## 📁 Struktur File

```
php_bot/
├── config.php              # Konfigurasi utama
├── index.php              # Entry point webhook
├── .htaccess              # Konfigurasi Apache
├── README.md              # Dokumentasi ini
│
├── includes/              # Library dan helper
│   ├── database.php       # Database connection & functions
│   ├── telegram.php       # Telegram API wrapper
│   ├── digiflazz.php     # Digiflazz API integration
│   └── bot_handlers.php   # Message handlers
│
├── admin/                 # Admin tools
│   └── update_products.php # Update produk dari API
│
└── setup/                 # Setup tools (hapus setelah install)
    ├── database.sql       # Database schema
    └── webhook_setup.php   # Webhook configuration
```

## 🚀 Instalasi Cepat

### 1. Upload File
- Upload semua file ke folder `public_html` atau subdirectory
- Pastikan struktur folder tetap sama

### 2. Setup Database
1. Buat database MySQL di cPanel
2. Import file `setup/database.sql`
3. Edit `config.php` sesuaikan pengaturan database

### 3. Konfigurasi Bot
Edit file `config.php`:
```php
// Bot Token dari @BotFather
define('BOT_TOKEN', 'YOUR_BOT_TOKEN');

// API Digiflazz
define('DIGIFLAZZ_USERNAME', 'your_username');
define('DIGIFLAZZ_KEY', 'your_api_key');

// Admin IDs (ID Telegram Anda)
define('ADMIN_IDS', [YOUR_TELEGRAM_ID]);

// Database
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Webhook URL
define('WEBHOOK_URL', 'https://yourdomain.com/webhook');
```

### 4. Setup Webhook
1. Buka `setup/webhook_setup.php` di browser
2. Ikuti panduan setup
3. Test koneksi dan konfigurasi
4. Klik "Setup Webhook"

### 5. Update Produk
1. Buka `admin/update_products.php`
2. Klik "Update Produk Sekarang"
3. Tunggu proses selesai

### 6. Test Bot
1. Chat bot di Telegram
2. Kirim `/start`
3. Bot harus merespon dengan menu

## ⚙️ Fitur

- ✅ **Modular Structure** - Kode terorganisir dalam folder yang logis
- ✅ **Database Class** - OOP database dengan prepared statements
- ✅ **Telegram API Wrapper** - Class lengkap untuk API Telegram
- ✅ **Digiflazz Integration** - Update produk dan transaksi otomatis
- ✅ **Admin Panel** - Tools untuk admin via web interface
- ✅ **Security** - .htaccess protection untuk file sensitif
- ✅ **Error Handling** - Logging dan error handling yang baik
- ✅ **Cron Job Ready** - Script siap untuk automation

## 🔧 Penggunaan

### Update Produk Manual
```
https://yourdomain.com/admin/update_products.php
```

### Update Produk via Cron
```bash
# Setiap hari jam 6 pagi
0 6 * * * curl -s "https://yourdomain.com/admin/update_products.php?action=update&key=YOUR_KEY"
```

### Webhook Info
```
https://yourdomain.com/setup/webhook_setup.php
```

## 📱 Menu Bot

1. **🛍 Beli Produk** - Browse dan beli produk digital
2. **💰 Deposit Saldo** - Top up saldo via transfer bank
3. **💼 Cek Saldo** - Lihat saldo terkini
4. **👑 Admin Menu** (khusus admin):
   - Update produk dari Digiflazz
   - Statistik bot
   - Konfirmasi deposit

## 🛠 Customization

### Menambah Handler Baru
Edit `includes/bot_handlers.php`:
```php
public function handleCustomCommand($chat_id, $user) {
    // Custom logic here
}
```

### Menambah Database Function
Edit `includes/database.php`:
```php
public function customDatabaseFunction($param) {
    $stmt = $this->pdo->prepare("SELECT * FROM table WHERE id = ?");
    $stmt->execute([$param]);
    return $stmt->fetchAll();
}
```

### Menambah API Integration
Buat file baru di `includes/` untuk API eksternal:
```php
class NewAPIIntegration {
    public function makeRequest($data) {
        // API logic here
    }
}
```

## 🔐 Keamanan

### File yang Dilindungi
- `config.php` - Tidak bisa diakses langsung
- `includes/` - Folder dilindungi dari akses web
- `*.sql` - File database dilindungi
- `*.log` - File log dilindungi

### Best Practices
1. **Hapus folder setup/ setelah install**
2. **Jangan share bot token atau API key**
3. **Gunakan HTTPS untuk webhook**
4. **Monitor error log secara rutin**
5. **Backup database berkala**

## 📊 Monitoring

### Error Log
Cek error log di cPanel atau:
```php
error_log("Custom message", 3, "/path/to/error.log");
```

### Database Monitoring
```sql
-- Check recent transactions
SELECT * FROM transactions ORDER BY date DESC LIMIT 10;

-- Check bot statistics
SELECT * FROM user_stats LIMIT 10;
```

## 🔄 Update & Maintenance

### Update Bot Code
1. Backup database dan file
2. Upload file baru
3. Test functionality
4. Update database schema jika perlu

### Database Maintenance
```sql
-- Optimize tables
OPTIMIZE TABLE users, products, transactions, deposits;

-- Clean old sessions
DELETE FROM user_sessions WHERE expires_at < NOW();
```

## 🚨 Troubleshooting

### Bot Tidak Merespon
1. Cek webhook status di `setup/webhook_setup.php`
2. Cek error log di cPanel
3. Pastikan SSL certificate valid
4. Verifikasi bot token

### Database Error
1. Cek koneksi di `config.php`
2. Pastikan user database punya privileges
3. Cek apakah semua tabel sudah ada

### API Digiflazz Error
1. Test API di `admin/update_products.php`
2. Cek username dan key di `config.php`
3. Pastikan saldo Digiflazz mencukupi

## 📞 Support

Jika ada masalah:
1. Cek error log di cPanel
2. Verifikasi semua konfigurasi
3. Test step by step dari webhook setup
4. Periksa dokumentasi Digiflazz

## 📈 Performance Tips

1. **Database Indexing** - Pastikan index sudah optimal
2. **Caching** - Gunakan caching untuk query yang sering
3. **CDN** - Gunakan CDN untuk file static
4. **Monitoring** - Monitor resource usage di cPanel

Selamat menggunakan Bot Telegram PHP yang modular! 🎉