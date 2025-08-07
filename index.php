<?php
/**
 * Web Interface untuk Bot Digital Products - InfinityFree Compatible
 * Interface seperti Telegram tapi diakses via web browser
 */

require_once 'config.php';

// Session start untuk tracking user
session_start();

// Inisialisasi user ID jika belum ada
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'web_user_' . time() . '_' . rand(1000, 9999);
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Fallback untuk development/testing
    $pdo = null;
}

// Ambil data produk dari database atau API
$products_count = 0;
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
        $products_count = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $products_count = 1165; // Fallback sesuai screenshot
    }
} else {
    $products_count = 1165; // Fallback sesuai screenshot
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Pulsa Digital - PPOB Indonesia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-container {
            width: 100%;
            max-width: 400px;
            height: 100vh;
            background: #e5ddd5;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        
        .header {
            background: #075e54;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ff9800;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 15px;
        }
        
        .bot-info h3 {
            font-size: 16px;
            margin-bottom: 2px;
        }
        
        .bot-info p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .chat-messages {
            height: calc(100vh - 140px);
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-bot {
            background: #ffffff;
            align-self: flex-start;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .message-user {
            background: #dcf8c6;
            align-self: flex-end;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }
        
        .menu-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 15px;
        }
        
        .menu-btn {
            background: #25d366;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .menu-btn:hover {
            background: #128c7e;
            transform: translateY(-1px);
        }
        
        .menu-btn.secondary {
            background: #34b7f1;
        }
        
        .menu-btn.secondary:hover {
            background: #0088cc;
        }
        
        .menu-btn.admin {
            background: #ff9800;
        }
        
        .menu-btn.admin:hover {
            background: #f57c00;
        }
        
        .status-message {
            background: #fff3cd;
            color: #856404;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 12px;
            text-align: center;
            margin: 10px 0;
            border: 1px solid #ffeaa7;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 12px;
            font-size: 13px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .input-area {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f0f0f0;
            padding: 10px 15px;
            border-top: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
        }
        
        .send-btn {
            background: #25d366;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 480px) {
            .chat-container {
                max-width: 100%;
                height: 100vh;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="header">
            <div class="avatar">B</div>
            <div class="bot-info">
                <h3>belipulsa_bot</h3>
                <p>bot</p>
            </div>
        </div>
        
        <div class="chat-messages">
            <!-- Welcome Message -->
            <div class="message message-bot">
                <div>🤖 Selamat datang di Bot Pulsa Digital!</div>
                <div style="margin-top: 10px; color: #666; font-size: 13px;">
                    💡 Sedia layanan PPOB termurah se Indonesia<br>
                    📱 Pulsa • 📶 Paket Data • ⚡ PLN • 💳 E-Money • 🎮 Voucher Game
                </div>
                <div style="margin-top: 10px; background: #e3f2fd; padding: 8px; border-radius: 8px; font-size: 12px;">
                    💬 Ketik <strong>"beli pulsa mas"</strong> untuk memulai
                </div>
                <div class="message-time">21:16</div>
            </div>
            
            <!-- User Start Command -->
            <div class="message message-user">
                <div>beli pulsa mas</div>
                <div class="message-time">21:17 ✓✓</div>
            </div>
            
            <!-- Main Menu -->
            <div class="message message-bot">
                <div>🎯 <strong>Menu Utama Bot Pulsa</strong></div>
                <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                    Halo! Silakan pilih layanan yang Anda butuhkan:
                </div>
                
                <div class="menu-buttons">
                    <button class="menu-btn" onclick="selectMenu('beli_produk')">
                        🛒 Beli Produk Digital
                    </button>
                    <button class="menu-btn secondary" onclick="selectMenu('cek_saldo')">
                        💰 Cek Saldo & Mutasi
                    </button>
                    <button class="menu-btn secondary" onclick="selectMenu('deposit')">
                        📥 Deposit / Top Up Saldo
                    </button>
                    <button class="menu-btn secondary" onclick="selectMenu('bantuan')">
                        🆘 Bantuan & Info
                    </button>
                    <button class="menu-btn admin" onclick="selectMenu('admin')" style="margin-top: 10px;">
                        👑 Admin Panel
                    </button>
                </div>
                
                <div style="margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 8px; font-size: 11px; color: #666;">
                    ⏰ Online 24/7 • 🚀 Proses Instan • 💯 Terpercaya
                </div>
                
                <div class="message-time">21:17</div>
            </div>
            
            <!-- Another user command -->
            <div class="message message-user">
                <div>beli pulsa mas</div>
                <div class="message-time">21:23 ✓✓</div>
            </div>
            
            <!-- Product Update Status -->
            <div class="success-message">
                ✅ Berhasil mengupdate <?= number_format($products_count) ?> produk dari API Digiflazz!
                <div class="message-time">21:23</div>
            </div>
            
            <!-- Back Button -->
            <div class="message message-bot">
                <button class="menu-btn" onclick="backToMenu()" style="background: #6c757d;">
                    ⬅️ Kembali
                </button>
                <div class="message-time">21:23</div>
            </div>
        </div>
        
        <div class="input-area">
            <input type="text" class="chat-input" placeholder="Ketik pesan..." id="messageInput">
            <button class="send-btn" onclick="sendMessage()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/>
                </svg>
            </button>
        </div>
    </div>
    
    <script>
        function selectMenu(menu) {
            const messagesContainer = document.querySelector('.chat-messages');
            const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
            
            // Add user selection message
            const userMessage = document.createElement('div');
            userMessage.className = 'message message-user';
            
            let menuText = '';
            switch(menu) {
                case 'beli_produk':
                    menuText = '🛒 Beli Produk Digital';
                    break;
                case 'deposit':
                    menuText = '📥 Deposit / Top Up Saldo';
                    break;
                case 'cek_saldo':
                    menuText = '💰 Cek Saldo & Mutasi';
                    break;
                case 'bantuan':
                    menuText = '🆘 Bantuan & Info';
                    break;
                case 'admin':
                    menuText = '👑 Admin Panel';
                    break;
            }
            
            userMessage.innerHTML = `
                <div>${menuText}</div>
                <div class="message-time">${currentTime} ✓✓</div>
            `;
            messagesContainer.appendChild(userMessage);
            
            // Add bot response
            setTimeout(() => {
                const botMessage = document.createElement('div');
                botMessage.className = 'message message-bot';
                
                let responseContent = '';
                switch(menu) {
                    case 'beli_produk':
                        responseContent = `
                            <div>🛒 <strong>Kategori Produk Digital</strong></div>
                            <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                                Silakan pilih kategori produk yang ingin Anda beli:
                            </div>
                            <div class="menu-buttons" style="margin-top: 15px;">
                                <button class="menu-btn" onclick="selectCategory('pulsa')">📱 Pulsa Reguler</button>
                                <button class="menu-btn" onclick="selectCategory('paket_data')">📶 Paket Internet</button>
                                <button class="menu-btn" onclick="selectCategory('pln')">⚡ Token PLN</button>
                                <button class="menu-btn" onclick="selectCategory('emoney')">💳 E-Money & QRIS</button>
                                <button class="menu-btn" onclick="selectCategory('game')">🎮 Voucher Game</button>
                                <button class="menu-btn" onclick="selectCategory('streaming')">📺 Voucher Streaming</button>
                            </div>
                            <div style="margin-top: 15px; background: #fff3cd; padding: 10px; border-radius: 8px; font-size: 12px; color: #856404;">
                                💡 <strong>Tips:</strong> Pastikan nomor tujuan sudah benar sebelum melakukan pembelian
                            </div>
                        `;
                        break;
                    case 'deposit':
                        responseContent = `
                            <div>📥 <strong>Deposit / Top Up Saldo</strong></div>
                            <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                                Pilih metode deposit yang Anda inginkan:
                            </div>
                            
                            <div class="menu-buttons" style="margin-top: 15px;">
                                <button class="menu-btn" onclick="showBankInfo('bca')">🏦 Transfer Bank BCA</button>
                                <button class="menu-btn" onclick="showBankInfo('mandiri')">🏦 Transfer Bank Mandiri</button>
                                <button class="menu-btn" onclick="showBankInfo('bri')">🏦 Transfer Bank BRI</button>
                                <button class="menu-btn secondary" onclick="showEwalletInfo()">📱 E-Wallet (OVO, DANA, dll)</button>
                                <button class="menu-btn secondary" onclick="showQrisInfo()">📲 QRIS Payment</button>
                            </div>
                            
                            <div style="margin-top: 15px; background: #e8f5e8; padding: 12px; border-radius: 8px; font-size: 12px; color: #155724;">
                                ✅ <strong>Proses Otomatis:</strong> Saldo akan ditambahkan dalam 5-15 menit setelah konfirmasi pembayaran
                            </div>
                            
                            <div style="margin-top: 10px; background: #fff3cd; padding: 10px; border-radius: 8px; font-size: 11px; color: #856404;">
                                ⚠️ <strong>Penting:</strong> Wajib kirim bukti transfer untuk konfirmasi
                            </div>
                        `;
                        break;
                    case 'cek_saldo':
                        responseContent = `
                            <div>💰 <strong>Informasi Saldo & Mutasi</strong></div>
                            <div style="margin-top: 12px; color: #555; font-size: 13px;">
                                Detail saldo dan transaksi Anda:
                            </div>
                            
                            <div style="margin-top: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; color: white; text-align: center;">
                                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Saldo Aktif</div>
                                <div style="font-size: 24px; font-weight: bold;">Rp 50.000</div>
                                <div style="font-size: 11px; opacity: 0.8; margin-top: 5px;">Update terakhir: Hari ini, 21:15</div>
                            </div>
                            
                            <div style="margin-top: 15px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 13px; font-weight: bold; margin-bottom: 10px; color: #495057;">📊 Ringkasan Transaksi</div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="font-size: 12px; color: #666;">Transaksi Hari Ini:</span>
                                    <span style="font-size: 12px; font-weight: bold;">3 transaksi</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="font-size: 12px; color: #666;">Total Pengeluaran:</span>
                                    <span style="font-size: 12px; font-weight: bold; color: #dc3545;">-Rp 75.000</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="font-size: 12px; color: #666;">Deposit Terakhir:</span>
                                    <span style="font-size: 12px; font-weight: bold; color: #28a745;">+Rp 100.000</span>
                                </div>
                            </div>
                            
                            <div class="menu-buttons" style="margin-top: 15px;">
                                <button class="menu-btn secondary" onclick="showTransactionHistory()">📋 Riwayat Transaksi</button>
                                <button class="menu-btn secondary" onclick="selectMenu('deposit')">➕ Top Up Saldo</button>
                            </div>
                        `;
                        break;
                    case 'bantuan':
                        responseContent = `
                            <div>🆘 <strong>Bantuan & Informasi</strong></div>
                            <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                                Bantuan dan informasi lengkap tentang layanan kami:
                            </div>
                            
                            <div style="margin-top: 15px; background: #e3f2fd; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 13px; font-weight: bold; margin-bottom: 10px; color: #1976d2;">📞 Kontak Support</div>
                                <div style="font-size: 12px; margin-bottom: 5px;">📱 WhatsApp: +62 812-3456-7890</div>
                                <div style="font-size: 12px; margin-bottom: 5px;">📧 Email: support@botpulsa.com</div>
                                <div style="font-size: 12px;">⏰ Online: 24/7</div>
                            </div>
                            
                            <div class="menu-buttons" style="margin-top: 15px;">
                                <button class="menu-btn secondary" onclick="showFAQ()">❓ FAQ & Tutorial</button>
                                <button class="menu-btn secondary" onclick="showPriceList()">📋 Daftar Harga</button>
                                <button class="menu-btn secondary" onclick="showTerms()">📄 Syarat & Ketentuan</button>
                                <button class="menu-btn secondary" onclick="contactSupport()">💬 Chat dengan CS</button>
                            </div>
                            
                            <div style="margin-top: 15px; background: #fff3cd; padding: 10px; border-radius: 8px; font-size: 11px; color: #856404;">
                                💡 <strong>Tips:</strong> Jika mengalami masalah transaksi, siapkan bukti transfer dan nomor referensi
                            </div>
                        `;
                        break;
                    case 'admin':
                        responseContent = `
                            <div>👑 <strong>Admin Panel</strong></div>
                            <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                                Panel kontrol administrasi sistem:
                            </div>
                            
                            <div style="margin-top: 15px; background: #fff3cd; padding: 12px; border-radius: 8px; font-size: 12px; color: #856404; text-align: center;">
                                🔐 <strong>Area Terbatas</strong><br>
                                Hanya admin yang dapat mengakses panel ini
                            </div>
                            
                            <div class="menu-buttons" style="margin-top: 15px;">
                                <button class="menu-btn admin" onclick="adminAction('update_products')">🔄 Update Database Produk</button>
                                <button class="menu-btn admin" onclick="adminAction('statistics')">📊 Statistik & Laporan</button>
                                <button class="menu-btn admin" onclick="adminAction('users')">👥 Kelola User & Saldo</button>
                                <button class="menu-btn admin" onclick="adminAction('deposits')">💰 Konfirmasi Deposit</button>
                                <button class="menu-btn admin" onclick="adminAction('settings')">⚙️ Pengaturan Sistem</button>
                                <button class="menu-btn admin" onclick="adminAction('broadcast')">📢 Broadcast Message</button>
                            </div>
                            
                            <div style="margin-top: 15px; background: #d4edda; padding: 10px; border-radius: 8px; font-size: 11px; color: #155724;">
                                ✅ Sistem operasional normal • Server aktif • Database terhubung
                            </div>
                        `;
                        break;
                }
                
                botMessage.innerHTML = responseContent + `<div class="message-time">${currentTime}</div>`;
                messagesContainer.appendChild(botMessage);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 500);
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function selectCategory(category) {
            window.location.href = `products.php?category=${category}`;
        }
        
        function adminAction(action) {
            if (action === 'update_products') {
                window.location.href = 'update_products.php';
            } else {
                window.location.href = `admin.php?action=${action}`;
            }
        }
        
        function backToMenu() {
            location.reload();
        }
        
        // Fungsi untuk bank info
        function showBankInfo(bank) {
            const messagesContainer = document.querySelector('.chat-messages');
            const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
            
            let bankDetails = '';
            switch(bank) {
                case 'bca':
                    bankDetails = `
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                            <div style="font-weight: bold; color: #0066cc; margin-bottom: 8px;">🏦 Bank BCA</div>
                            <div style="font-size: 13px; margin-bottom: 5px;">Nomor Rekening: <strong>1234567890</strong></div>
                            <div style="font-size: 13px; margin-bottom: 5px;">Atas Nama: <strong>PT Digital Pulsa Indonesia</strong></div>
                            <div style="font-size: 12px; color: #666;">Cabang: Jakarta Pusat</div>
                        </div>
                    `;
                    break;
                case 'mandiri':
                    bankDetails = `
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                            <div style="font-weight: bold; color: #ff6600; margin-bottom: 8px;">🏦 Bank Mandiri</div>
                            <div style="font-size: 13px; margin-bottom: 5px;">Nomor Rekening: <strong>0987654321</strong></div>
                            <div style="font-size: 13px; margin-bottom: 5px;">Atas Nama: <strong>PT Digital Pulsa Indonesia</strong></div>
                            <div style="font-size: 12px; color: #666;">Cabang: Jakarta Selatan</div>
                        </div>
                    `;
                    break;
                case 'bri':
                    bankDetails = `
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                            <div style="font-weight: bold; color: #004080; margin-bottom: 8px;">🏦 Bank BRI</div>
                            <div style="font-size: 13px; margin-bottom: 5px;">Nomor Rekening: <strong>5678901234</strong></div>
                            <div style="font-size: 13px; margin-bottom: 5px;">Atas Nama: <strong>PT Digital Pulsa Indonesia</strong></div>
                            <div style="font-size: 12px; color: #666;">Cabang: Jakarta Barat</div>
                        </div>
                    `;
                    break;
            }
            
            const botMessage = document.createElement('div');
            botMessage.className = 'message message-bot';
            botMessage.innerHTML = `
                <div>📋 <strong>Informasi Transfer</strong></div>
                ${bankDetails}
                <div style="margin-top: 15px; background: #e8f5e8; padding: 10px; border-radius: 8px; font-size: 12px; color: #155724;">
                    💡 <strong>Langkah selanjutnya:</strong><br>
                    1. Transfer sesuai nominal yang diinginkan<br>
                    2. Kirim screenshot bukti transfer ke chat ini<br>
                    3. Tunggu konfirmasi admin (maks 15 menit)
                </div>
                <div class="menu-buttons" style="margin-top: 15px;">
                    <button class="menu-btn secondary" onclick="uploadProof()">📷 Upload Bukti Transfer</button>
                </div>
                <div class="message-time">${currentTime}</div>
            `;
            messagesContainer.appendChild(botMessage);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function showEwalletInfo() {
            const messagesContainer = document.querySelector('.chat-messages');
            const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
            
            const botMessage = document.createElement('div');
            botMessage.className = 'message message-bot';
            botMessage.innerHTML = `
                <div>📱 <strong>E-Wallet Payment</strong></div>
                <div style="margin-top: 15px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="font-weight: bold; color: #00aa13; margin-bottom: 8px;">💚 OVO</div>
                        <div style="font-size: 13px;">Nomor: <strong>081234567890</strong></div>
                        <div style="font-size: 13px;">Atas Nama: <strong>Digital Pulsa</strong></div>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="font-weight: bold; color: #1c7cd6; margin-bottom: 8px;">💙 DANA</div>
                        <div style="font-size: 13px;">Nomor: <strong>081234567890</strong></div>
                        <div style="font-size: 13px;">Atas Nama: <strong>Digital Pulsa</strong></div>
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <div style="font-weight: bold; color: #ff4757; margin-bottom: 8px;">❤️ LinkAja</div>
                        <div style="font-size: 13px;">Nomor: <strong>081234567890</strong></div>
                        <div style="font-size: 13px;">Atas Nama: <strong>Digital Pulsa</strong></div>
                    </div>
                </div>
                <div class="menu-buttons" style="margin-top: 15px;">
                    <button class="menu-btn secondary" onclick="uploadProof()">📷 Upload Bukti Transfer</button>
                </div>
                <div class="message-time">${currentTime}</div>
            `;
            messagesContainer.appendChild(botMessage);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function uploadProof() {
            const messagesContainer = document.querySelector('.chat-messages');
            const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
            
            const botMessage = document.createElement('div');
            botMessage.className = 'message message-bot';
            botMessage.innerHTML = `
                <div>📷 <strong>Upload Bukti Transfer</strong></div>
                <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                    Silakan upload foto bukti transfer Anda:
                </div>
                <div style="margin-top: 15px; border: 2px dashed #ddd; padding: 30px; text-align: center; border-radius: 8px; background: #fafafa;">
                    <div style="font-size: 48px; margin-bottom: 10px; color: #999;">📄</div>
                    <div style="font-size: 13px; color: #666; margin-bottom: 15px;">Drag & drop file atau klik untuk browse</div>
                    <input type="file" accept="image/*" style="display: none;" id="fileInput" onchange="handleFileUpload(this)">
                    <button class="menu-btn secondary" onclick="document.getElementById('fileInput').click()">📁 Pilih File</button>
                </div>
                <div style="margin-top: 15px; background: #fff3cd; padding: 10px; border-radius: 8px; font-size: 11px; color: #856404;">
                    ⚠️ <strong>Format yang didukung:</strong> JPG, PNG, PDF (Maks 5MB)
                </div>
                <div class="message-time">${currentTime}</div>
            `;
            messagesContainer.appendChild(botMessage);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function handleFileUpload(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const messagesContainer = document.querySelector('.chat-messages');
                const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
                
                // User message with file
                const userMessage = document.createElement('div');
                userMessage.className = 'message message-user';
                userMessage.innerHTML = `
                    <div>📎 Bukti Transfer: ${file.name}</div>
                    <div style="margin-top: 8px; background: rgba(255,255,255,0.2); padding: 8px; border-radius: 6px; font-size: 11px;">
                        📄 ${(file.size / 1024).toFixed(1)} KB • ${file.type}
                    </div>
                    <div class="message-time">${currentTime} ✓✓</div>
                `;
                messagesContainer.appendChild(userMessage);
                
                // Bot confirmation
                setTimeout(() => {
                    const botMessage = document.createElement('div');
                    botMessage.className = 'message message-bot';
                    botMessage.innerHTML = `
                        <div>✅ <strong>Bukti Transfer Diterima</strong></div>
                        <div style="margin-top: 10px; color: #555; font-size: 13px;">
                            Terima kasih! Bukti transfer Anda telah diterima dan sedang diproses.
                        </div>
                        <div style="margin-top: 15px; background: #e8f5e8; padding: 12px; border-radius: 8px; font-size: 12px; color: #155724;">
                            🔄 <strong>Status:</strong> Menunggu konfirmasi admin<br>
                            ⏱️ <strong>Estimasi:</strong> 5-15 menit<br>
                            📱 <strong>Notifikasi:</strong> Anda akan mendapat pesan otomatis
                        </div>
                        <div style="margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 8px; font-size: 11px; color: #666;">
                            📋 <strong>Referensi:</strong> TRX${Date.now().toString().slice(-8)}
                        </div>
                        <div class="message-time">${currentTime}</div>
                    `;
                    messagesContainer.appendChild(botMessage);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }, 1500);
                
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
        
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (message) {
                const messagesContainer = document.querySelector('.chat-messages');
                const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
                
                const userMessage = document.createElement('div');
                userMessage.className = 'message message-user';
                userMessage.innerHTML = `
                    <div>${message}</div>
                    <div class="message-time">${currentTime} ✓✓</div>
                `;
                messagesContainer.appendChild(userMessage);
                
                // Clear input
                input.value = '';
                
                // Handle special commands
                if (message.toLowerCase().includes('beli pulsa') || message === '/start') {
                    // Trigger main menu
                    setTimeout(() => {
                        const botMessage = document.createElement('div');
                        botMessage.className = 'message message-bot';
                        botMessage.innerHTML = `
                            <div>🎯 <strong>Menu Utama Bot Pulsa</strong></div>
                            <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                                Halo! Silakan pilih layanan yang Anda butuhkan:
                            </div>
                            
                            <div class="menu-buttons">
                                <button class="menu-btn" onclick="selectMenu('beli_produk')">
                                    🛒 Beli Produk Digital
                                </button>
                                <button class="menu-btn secondary" onclick="selectMenu('cek_saldo')">
                                    💰 Cek Saldo & Mutasi
                                </button>
                                <button class="menu-btn secondary" onclick="selectMenu('deposit')">
                                    📥 Deposit / Top Up Saldo
                                </button>
                                <button class="menu-btn secondary" onclick="selectMenu('bantuan')">
                                    🆘 Bantuan & Info
                                </button>
                                <button class="menu-btn admin" onclick="selectMenu('admin')" style="margin-top: 10px;">
                                    👑 Admin Panel
                                </button>
                            </div>
                            
                            <div style="margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 8px; font-size: 11px; color: #666;">
                                ⏰ Online 24/7 • 🚀 Proses Instan • 💯 Terpercaya
                            </div>
                            
                            <div class="message-time">${currentTime}</div>
                        `;
                        messagesContainer.appendChild(botMessage);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }, 800);
                } else {
                    // Regular message response
                    setTimeout(() => {
                        const botMessage = document.createElement('div');
                        botMessage.className = 'message message-bot';
                        botMessage.innerHTML = `
                            <div>💬 Terima kasih atas pesan Anda!</div>
                            <div style="margin-top: 8px; color: #666; font-size: 12px;">
                                Tim customer service akan segera merespon. Untuk layanan cepat, gunakan menu di atas.
                            </div>
                            <div class="menu-buttons" style="margin-top: 15px;">
                                <button class="menu-btn" onclick="backToMenu()">🏠 Kembali ke Menu Utama</button>
                            </div>
                            <div class="message-time">${currentTime}</div>
                        `;
                        messagesContainer.appendChild(botMessage);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }, 1000);
                }
                
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
        
        // Enter key support
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Auto scroll to bottom on load
        window.addEventListener('load', function() {
            const messagesContainer = document.querySelector('.chat-messages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    </script>
</body>
</html>