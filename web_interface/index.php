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

// Database connection SQLite untuk development
try {
    $pdo = new PDO("sqlite:../bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
}

// Ambil data produk dari database
$products_count = 0;
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $products_count = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $products_count = 565; // Fallback berdasarkan data real
    }
} else {
    $products_count = 565; // Fallback berdasarkan data real
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
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
            border-radius: 0;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
            box-shadow: 0 4px 15px rgba(238, 90, 36, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
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
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .message {
            max-width: 90%;
            padding: 18px 22px;
            border-radius: 25px;
            position: relative;
            word-wrap: break-word;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .message-bot {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 248, 255, 0.95) 100%);
            align-self: flex-start;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-left: 4px solid #667eea;
            position: relative;
        }
        
        .message-bot::before {
            content: '🤖';
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            background: #667eea;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }
        
        .message-user {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
            color: white;
            align-self: flex-end;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            border-right: 4px solid #764ba2;
            position: relative;
        }
        
        .message-user::after {
            content: '👤';
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
            background: #764ba2;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(118, 75, 162, 0.3);
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
            gap: 12px;
            margin-top: 20px;
        }
        
        .menu-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .menu-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .menu-btn:hover::before {
            left: 100%;
        }
        
        .menu-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .menu-btn.secondary {
            background: linear-gradient(135deg, #00c9ff 0%, #92fe9d 100%);
            box-shadow: 0 4px 15px rgba(0, 201, 255, 0.3);
        }
        
        .menu-btn.secondary:hover {
            box-shadow: 0 8px 25px rgba(0, 201, 255, 0.4);
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
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 248, 255, 0.95) 100%);
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(20px);
            box-shadow: 0 -4px 15px rgba(0,0,0,0.1);
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 18px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 25px;
            outline: none;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }
        
        .chat-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        
        .send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .send-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message {
            animation: fadeInUp 0.5s ease-out;
        }
        
        @media (max-width: 480px) {
            .chat-container {
                max-width: 100%;
                height: 100vh;
            }
            
            .message {
                max-width: 95%;
                padding: 15px 18px;
            }
            
            .menu-btn {
                padding: 14px 16px;
                font-size: 13px;
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
        
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be dynamically added here -->
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
                case 'pascabayar':
                    menuText = '💳 Pascabayar & Tagihan';
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
                                <button class="menu-btn" onclick="selectCategory('pulsa')">📱 Pulsa Reguler (193 produk)</button>
                                <button class="menu-btn" onclick="selectCategory('data')">📶 Paket Internet (161 produk)</button>
                                <button class="menu-btn" onclick="selectCategory('pln')">⚡ Token PLN (2 produk)</button>
                                <button class="menu-btn" onclick="selectCategory('emoney')">💳 E-Money & QRIS (105 produk)</button>
                                <button class="menu-btn" onclick="selectCategory('games')">🎮 Voucher Game (78 produk)</button>
                                <button class="menu-btn" onclick="selectCategory('voucher')">📺 Voucher Digital (8 produk)</button>
                                <button class="menu-btn secondary" onclick="window.location.href='pascabayar.php'">💳 Pascabayar & Tagihan</button>
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
                    case 'pascabayar':
                        responseContent = `
                            <div>💳 <strong>Layanan Pascabayar & Tagihan</strong></div>
                            <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                                Bayar tagihan bulanan dengan mudah dan cepat:
                            </div>
                            <div class="menu-buttons" style="margin-top: 15px;">
                                <button class="menu-btn" onclick="window.location.href='pascabayar.php?category=pln_pascabayar'">⚡ PLN Pascabayar</button>
                                <button class="menu-btn" onclick="window.location.href='pascabayar.php?category=pdam'">💧 PDAM / Air</button>
                                <button class="menu-btn" onclick="window.location.href='pascabayar.php?category=hp_pascabayar'">📱 Telepon Pascabayar</button>
                                <button class="menu-btn" onclick="window.location.href='pascabayar.php?category=internet_pascabayar'">🌐 Internet Pascabayar</button>
                                <button class="menu-btn" onclick="window.location.href='pascabayar.php?category=bpjs_kesehatan'">🏥 BPJS Kesehatan</button>
                                <button class="menu-btn secondary" onclick="window.location.href='pascabayar.php'">📋 Lihat Semua Pascabayar</button>
                            </div>
                            <div style="margin-top: 15px; background: #e3f2fd; padding: 10px; border-radius: 8px; font-size: 12px; color: #1976d2;">
                                ℹ️ <strong>Info:</strong> Pascabayar adalah layanan bayar setelah pakai, seperti listrik PLN, air PDAM, telepon rumah, dll.
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

                            </div>
                            
                            <div style="margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 8px; font-size: 11px; color: #666;">
                                ⏰ Online 24/7 • 🚀 Proses Instan • 💯 Terpercaya<br>
                                📊 Total Produk: <?php echo number_format($products_count); ?> items
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
        
        function selectCategory(category) {
            const messagesContainer = document.querySelector('.chat-messages');
            const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
            
            // Add user selection message
            const userMessage = document.createElement('div');
            userMessage.className = 'message message-user';
            
            let categoryName = '';
            switch(category) {
                case 'pulsa': categoryName = '📱 Pulsa Reguler'; break;
                case 'data': categoryName = '📶 Paket Internet'; break;
                case 'pln': categoryName = '⚡ Token PLN'; break;
                case 'emoney': categoryName = '💳 E-Money & QRIS'; break;
                case 'games': categoryName = '🎮 Voucher Game'; break;
                case 'voucher': categoryName = '📺 Voucher Digital'; break;
            }
            
            userMessage.innerHTML = `
                <div>${categoryName}</div>
                <div class="message-time">${currentTime} ✓✓</div>
            `;
            messagesContainer.appendChild(userMessage);
            
            // Add bot response with organized brands
            setTimeout(() => {
                const botMessage = document.createElement('div');
                botMessage.className = 'message message-bot';
                
                let responseContent = '';
                
                if (category === 'pulsa') {
                    responseContent = `
                        <div>📱 <strong>Pulsa Reguler - Pilih Operator</strong></div>
                        <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                            Silakan pilih operator seluler:
                        </div>
                        <div class="menu-buttons" style="margin-top: 15px;">
                            <button class="menu-btn" onclick="window.location.href='products.php?category=pulsa&search=Telkomsel'">
                                🔴 Telkomsel <span style="font-size: 11px; opacity: 0.8;">(166 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=pulsa&search=Indosat'">
                                🟡 Indosat <span style="font-size: 11px; opacity: 0.8;">(78 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=pulsa&search=Tri'">
                                🔵 Tri <span style="font-size: 11px; opacity: 0.8;">(40 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=pulsa&search=Smartfren'">
                                🟣 Smartfren <span style="font-size: 11px; opacity: 0.8;">(37 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=pulsa&search=XL'">
                                🔷 XL <span style="font-size: 11px; opacity: 0.8;">(36 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=pulsa&search=Axis'">
                                ⚫ Axis <span style="font-size: 11px; opacity: 0.8;">(17 produk)</span>
                            </button>
                        </div>
                    `;
                } else if (category === 'data') {
                    responseContent = `
                        <div>📶 <strong>Paket Internet - Pilih Operator</strong></div>
                        <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                            Pilih operator untuk paket internet:
                        </div>
                        <div class="menu-buttons" style="margin-top: 15px;">
                            <button class="menu-btn" onclick="window.location.href='products.php?category=data&search=Telkomsel'">
                                🔴 Telkomsel Data <span style="font-size: 11px; opacity: 0.8;">(105 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=data&search=Tri'">
                                🔵 Tri Data <span style="font-size: 11px; opacity: 0.8;">(90 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=data&search=Indosat'">
                                🟡 Indosat Data <span style="font-size: 11px; opacity: 0.8;">(63 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=data&search=Axis'">
                                ⚫ Axis Data <span style="font-size: 11px; opacity: 0.8;">(38 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=data&search=Smartfren'">
                                🟣 Smartfren Data <span style="font-size: 11px; opacity: 0.8;">(27 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=data&search=XL'">
                                🔷 XL Data <span style="font-size: 11px; opacity: 0.8;">(14 produk)</span>
                            </button>
                        </div>
                    `;
                } else if (category === 'emoney') {
                    responseContent = `
                        <div>💳 <strong>E-Money & Digital Wallet</strong></div>
                        <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                            Pilih platform pembayaran digital:
                        </div>
                        <div class="menu-buttons" style="margin-top: 15px;">
                            <button class="menu-btn" onclick="window.location.href='products.php?category=emoney&search=GoPay'">
                                💚 GoPay <span style="font-size: 11px; opacity: 0.8;">(8 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=emoney&search=OVO'">
                                🟣 OVO <span style="font-size: 11px; opacity: 0.8;">(8 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=emoney&search=DANA'">
                                💙 DANA <span style="font-size: 11px; opacity: 0.8;">(8 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=emoney&search=LinkAja'">
                                ❤️ LinkAja <span style="font-size: 11px; opacity: 0.8;">(8 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=emoney&search=ShopeePay'">
                                🧡 ShopeePay <span style="font-size: 11px; opacity: 0.8;">(8 produk)</span>
                            </button>
                            <button class="menu-btn secondary" onclick="window.location.href='products.php?category=emoney'">📋 Lihat Semua E-Money</button>
                        </div>
                    `;
                } else if (category === 'games') {
                    responseContent = `
                        <div>🎮 <strong>Voucher Game - Pilih Game</strong></div>
                        <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                            Pilih game favorit Anda:
                        </div>
                        <div class="menu-buttons" style="margin-top: 15px;">
                            <button class="menu-btn" onclick="window.location.href='products.php?category=games&search=Mobile+Legends'">
                                ⚔️ Mobile Legends <span style="font-size: 11px; opacity: 0.8;">(29 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=games&search=Free+Fire'">
                                🔥 Free Fire <span style="font-size: 11px; opacity: 0.8;">(23 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=games&search=PUBG'">
                                🎯 PUBG Mobile <span style="font-size: 11px; opacity: 0.8;">(14 produk)</span>
                            </button>
                            <button class="menu-btn" onclick="window.location.href='products.php?category=games&search=Valorant'">
                                💥 Valorant <span style="font-size: 11px; opacity: 0.8;">(8 produk)</span>
                            </button>
                            <button class="menu-btn secondary" onclick="window.location.href='products.php?category=games'">🕹️ Lihat Semua Game</button>
                        </div>
                    `;
                } else {
                    // Default redirect to products page
                    responseContent = `
                        <div>📋 <strong>Produk ${categoryName}</strong></div>
                        <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                            Mengarahkan ke halaman produk...
                        </div>
                        <div class="menu-buttons" style="margin-top: 15px;">
                            <button class="menu-btn" onclick="window.location.href='products.php?category=${category}'">📋 Lihat Semua Produk</button>
                        </div>
                    `;
                }
                
                responseContent += `<div class="message-time">${currentTime}</div>`;
                
                botMessage.innerHTML = responseContent;
                messagesContainer.appendChild(botMessage);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 1000);
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function backToMenu() {
            const messagesContainer = document.querySelector('.chat-messages');
            const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
            
            const botMessage = document.createElement('div');
            botMessage.className = 'message message-bot';
            botMessage.innerHTML = `
                <div>🏠 <strong>Menu Utama Bot Pulsa</strong></div>
                <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.4;">
                    Silakan pilih layanan yang Anda butuhkan:
                </div>
                
                <div class="menu-buttons" style="margin-top: 15px;">
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
                </div>
                
                <div style="margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 8px; font-size: 11px; color: #666;">
                    ⏰ Online 24/7 • 🚀 Proses Instan • 💯 Terpercaya
                </div>
                
                <div class="message-time">${currentTime}</div>
            `;
            messagesContainer.appendChild(botMessage);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Enter key support
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Initialize chat with welcome message
        window.addEventListener('load', function() {
            const messagesContainer = document.querySelector('.chat-messages');
            const currentTime = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
            
            // Welcome message
            const welcomeMessage = document.createElement('div');
            welcomeMessage.className = 'message message-bot';
            welcomeMessage.innerHTML = `
                <div style="font-size: 16px; font-weight: bold; color: #667eea;">🚀 Halo! Saya PulsaBot AI</div>
                <div style="margin-top: 12px; color: #555; font-size: 13px; line-height: 1.6;">
                    Asisten digital terpercaya untuk semua kebutuhan pulsa, paket data, dan pembayaran online Anda!
                </div>
                <div style="margin-top: 15px; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); padding: 12px; border-radius: 15px; border-left: 4px solid #667eea;">
                    <div style="font-size: 12px; font-weight: bold; color: #667eea; margin-bottom: 8px;">✨ Yang bisa saya bantu:</div>
                    <div style="font-size: 11px; color: #666; line-height: 1.4;">
                        🎯 Pulsa All Operator • 🌐 Paket Internet • ⚡ Token PLN<br>
                        💳 Top Up E-Money • 🎮 Voucher Gaming • 📺 Streaming
                    </div>
                </div>
                <div style="margin-top: 12px; text-align: center; font-size: 11px; color: #999;">
                    💬 Cukup ketik <span style="background: rgba(102, 126, 234, 0.1); padding: 2px 6px; border-radius: 4px; color: #667eea; font-weight: bold;">"beli pulsa mas"</span>
                </div>
                <div class="message-time">${currentTime}</div>
            `;
            messagesContainer.appendChild(welcomeMessage);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    </script>
</body>
</html>