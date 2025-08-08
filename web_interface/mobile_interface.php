<?php
/**
 * Mobile Interface untuk Bot Digital Products - Android Style
 * UI/UX yang mobile-first dengan Material Design
 */

// Session start untuk tracking user (harus sebelum output apapun)
session_start();
require_once 'config.php';

// Inisialisasi user ID jika belum ada
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'web_user_' . time() . '_' . rand(1000, 9999);
}

// Database connection SQLite untuk development
try {
    $pdo = new PDO("sqlite:bot_database.db");
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
        $products_count = 0;
    }
} else {
    $products_count = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Bot Pulsa Digital - Mobile App</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
            height: 100vh;
            overflow: hidden;
            position: fixed;
            width: 100%;
        }
        
        /* App Container */
        .app-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Status Bar */
        .status-bar {
            height: 24px;
            background: rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px;
            font-size: 12px;
            color: white;
            font-weight: 500;
        }
        
        /* App Header */
        .app-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: relative;
            z-index: 10;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .app-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .header-title {
            color: white;
        }
        
        .header-title h1 {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .header-title p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .header-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }
        
        .action-btn:active {
            transform: scale(0.95);
            background: rgba(255,255,255,0.3);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            background: white;
            border-radius: 24px 24px 0 0;
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Chat Header */
        .chat-header {
            padding: 20px 20px 16px;
            background: white;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
            padding: 16px;
            border-radius: 16px;
            border-left: 4px solid #667eea;
            margin-bottom: 16px;
        }
        
        .welcome-message h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .welcome-message p {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .stats-row {
            display: flex;
            gap: 12px;
            margin-top: 12px;
        }
        
        .stat-item {
            flex: 1;
            background: white;
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            font-weight: 400;
        }
        
        /* Chat Messages */
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .message-bot {
            background: white;
            padding: 16px;
            border-radius: 16px 16px 16px 4px;
            max-width: 85%;
            align-self: flex-start;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            position: relative;
        }
        
        .message-bot::before {
            content: '🤖';
            position: absolute;
            left: -8px;
            top: -8px;
            width: 24px;
            height: 24px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .message-text {
            color: #333;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 8px;
        }
        
        .message-time {
            font-size: 11px;
            color: #999;
            text-align: right;
        }
        
        /* Menu Buttons */
        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }
        
        .menu-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 20px 16px;
            border-radius: 16px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        
        .menu-item:active {
            transform: scale(0.98);
        }
        
        .menu-item:active::before {
            transform: translateX(100%);
        }
        
        .menu-icon {
            font-size: 28px;
            margin-bottom: 4px;
        }
        
        .menu-text {
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            line-height: 1.2;
        }
        
        .menu-desc {
            font-size: 10px;
            opacity: 0.8;
            text-align: center;
        }
        
        /* Special menu styles */
        .menu-item.pulsa {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }
        
        .menu-item.data {
            background: linear-gradient(135deg, #00c9ff 0%, #92fe9d 100%);
            box-shadow: 0 4px 12px rgba(0, 201, 255, 0.3);
        }
        
        .menu-item.games {
            background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
            box-shadow: 0 4px 12px rgba(252, 70, 107, 0.3);
        }
        
        .menu-item.emoney {
            background: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%);
            box-shadow: 0 4px 12px rgba(253, 187, 45, 0.3);
        }
        
        .menu-item.pln {
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            box-shadow: 0 4px 12px rgba(253, 203, 110, 0.3);
        }
        
        .menu-item.voucher {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
            box-shadow: 0 4px 12px rgba(253, 121, 168, 0.3);
        }
        
        .menu-item.lainnya {
            background: linear-gradient(135deg, #81ecec 0%, #00b894 100%);
            box-shadow: 0 4px 12px rgba(129, 236, 236, 0.3);
        }
        
        .menu-item.all {
            background: linear-gradient(135deg, #fab1a0 0%, #e17055 100%);
            box-shadow: 0 4px 12px rgba(250, 177, 160, 0.3);
        }
        
        /* Secondary Menu Styling */
        .menu-grid.secondary .menu-item {
            padding: 16px 12px;
            min-height: 85px;
        }
        
        .menu-grid.secondary .menu-icon {
            font-size: 24px;
        }
        
        .menu-grid.secondary .menu-text {
            font-size: 12px;
        }
        
        .menu-grid.secondary .menu-desc {
            font-size: 10px;
        }
        
        /* Brand Carousel */
        .brand-carousel {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 4px 0 12px 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .brand-carousel::-webkit-scrollbar {
            display: none;
        }
        
        .brand-chip {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 140px;
            flex-shrink: 0;
        }
        
        .brand-chip:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
        }
        
        .brand-chip:active {
            transform: scale(0.98);
        }
        
        .brand-logo {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: white;
        }
        
        .brand-logo.telkomsel {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .brand-logo.indosat {
            background: linear-gradient(135deg, #f1c40f 0%, #f39c12 100%);
        }
        
        .brand-logo.tri {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        }
        
        .brand-logo.axis {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }
        
        .brand-logo.smartfren {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
        }
        
        .brand-logo.xl {
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
        }
        
        .brand-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .brand-name {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }
        
        .brand-count {
            font-size: 11px;
            color: #666;
        }
        
        /* Bottom Input */
        .input-container {
            background: white;
            padding: 16px 20px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
        }
        
        .chat-input {
            flex: 1;
            background: #f5f5f5;
            border: none;
            padding: 12px 16px;
            border-radius: 24px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }
        
        .chat-input:focus {
            background: white;
            box-shadow: 0 0 0 2px #667eea20;
        }
        
        .send-button {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .send-button:active {
            transform: scale(0.95);
        }
        
        /* Responsive Adjustments */
        @media (max-height: 600px) {
            .chat-header {
                padding: 16px 20px 12px;
            }
            
            .welcome-message {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .chat-messages {
                padding: 16px 20px;
            }
        }
        
        /* Loading Animation */
        .loading-dots {
            display: inline-flex;
            gap: 4px;
        }
        
        .loading-dots span {
            width: 6px;
            height: 6px;
            background: #667eea;
            border-radius: 50%;
            animation: loadingDots 1.4s infinite ease-in-out both;
        }
        
        .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
        .loading-dots span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes loadingDots {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        /* Ripple Effect */
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .ripple:active::after {
            width: 200px;
            height: 200px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Status Bar -->
        <div class="status-bar">
            <span id="currentTime"></span>
            <span>🔋 100% 📶</span>
        </div>
        
        <!-- App Header -->
        <div class="app-header">
            <div class="header-left">
                <div class="app-icon">🤖</div>
                <div class="header-title">
                    <h1>Bot Pulsa Digital</h1>
                    <p>Online • Siap Melayani</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="action-btn" onclick="toggleNotifications()">
                    <span class="material-icons" style="font-size: 20px;">notifications</span>
                </button>
                <button class="action-btn" onclick="showMenu()">
                    <span class="material-icons" style="font-size: 20px;">more_vert</span>
                </button>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="welcome-message">
                    <h3>Selamat Datang! 👋</h3>
                    <p>Platform terpercaya untuk semua kebutuhan digital Anda. Pilih layanan di bawah ini:</p>
                    
                    <div class="stats-row">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($products_count); ?></div>
                            <div class="stat-label">Produk</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">24/7</div>
                            <div class="stat-label">Online</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">⚡</div>
                            <div class="stat-label">Instant</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <div class="message-bot">
                    <div class="message-text">
                        <strong>Halo! 👋</strong><br>
                        Saya adalah Bot Digital Products yang siap membantu Anda membeli pulsa, paket data, voucher game, dan berbagai layanan digital lainnya.
                        <br><br>
                        <strong>Pilih layanan yang Anda butuhkan:</strong>
                    </div>
                    
                    <!-- Primary Categories Grid -->
                    <div class="menu-grid primary-categories">
                        <button class="menu-item data ripple" onclick="selectCategory('Data')">
                            <div class="menu-icon">🌐</div>
                            <div class="menu-text">Paket Data</div>
                            <div class="menu-desc">Semua Operator</div>
                        </button>
                        
                        <button class="menu-item games ripple" onclick="selectCategory('Game')">
                            <div class="menu-icon">🎮</div>
                            <div class="menu-text">Voucher Game</div>
                            <div class="menu-desc">ML, FF, PUBG, Call of Duty</div>
                        </button>
                        
                        <button class="menu-item emoney ripple" onclick="selectCategory('E-Money')">
                            <div class="menu-icon">💳</div>
                            <div class="menu-text">E-Money</div>
                            <div class="menu-desc">OVO, DANA, GoPay, BRIZZI</div>
                        </button>
                        
                        <button class="menu-item pulsa ripple" onclick="selectCategory('Pulsa')">
                            <div class="menu-icon">📱</div>
                            <div class="menu-text">Pulsa</div>
                            <div class="menu-desc">Semua Operator</div>
                        </button>
                    </div>
                    
                    <!-- Additional Categories -->
                    <div class="additional-categories" style="margin-top: 16px;">
                        <div class="category-title" style="color: #666; font-size: 13px; margin-bottom: 12px; font-weight: 500;">
                            <span class="material-icons" style="font-size: 16px; vertical-align: middle; margin-right: 4px;">category</span>
                            Kategori Lainnya
                        </div>
                        <div class="menu-grid secondary">
                            <button class="menu-item pln ripple" onclick="selectCategory('PLN')">
                                <div class="menu-icon">⚡</div>
                                <div class="menu-text">Token PLN</div>
                                <div class="menu-desc">Token Listrik</div>
                            </button>
                            
                            <button class="menu-item all ripple" onclick="window.location.href='products_detailed.php'">
                                <div class="menu-icon">📊</div>
                                <div class="menu-text">Struktur Hierarki</div>
                                <div class="menu-desc">Category → Brand → Produk</div>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Popular Brands Section -->
                    <div class="popular-brands" style="margin-top: 20px;">
                        <div class="category-title" style="color: #666; font-size: 13px; margin-bottom: 12px; font-weight: 500;">
                            <span class="material-icons" style="font-size: 16px; vertical-align: middle; margin-right: 4px;">star</span>
                            Brand Populer
                        </div>
                        <div class="brand-carousel">
                            <button class="brand-chip" onclick="selectBrand('TELKOMSEL')">
                                <span class="brand-logo telkomsel">T</span>
                                <span class="brand-info">
                                    <span class="brand-name">Telkomsel</span>
                                    <span class="brand-count">277 produk</span>
                                </span>
                            </button>
                            <button class="brand-chip" onclick="selectBrand('INDOSAT')">
                                <span class="brand-logo indosat">I</span>
                                <span class="brand-info">
                                    <span class="brand-name">Indosat</span>
                                    <span class="brand-count">203 produk</span>
                                </span>
                            </button>
                            <button class="brand-chip" onclick="selectBrand('TRI')">
                                <span class="brand-logo tri">3</span>
                                <span class="brand-info">
                                    <span class="brand-name">Tri</span>
                                    <span class="brand-count">161 produk</span>
                                </span>
                            </button>
                            <button class="brand-chip" onclick="selectBrand('AXIS')">
                                <span class="brand-logo axis">A</span>
                                <span class="brand-info">
                                    <span class="brand-name">Axis</span>
                                    <span class="brand-count">68 produk</span>
                                </span>
                            </button>
                            <button class="brand-chip" onclick="selectBrand('SMARTFREN')">
                                <span class="brand-logo smartfren">S</span>
                                <span class="brand-info">
                                    <span class="brand-name">Smartfren</span>
                                    <span class="brand-count">66 produk</span>
                                </span>
                            </button>
                            <button class="brand-chip" onclick="selectBrand('XL')">
                                <span class="brand-logo xl">XL</span>
                                <span class="brand-info">
                                    <span class="brand-name">XL</span>
                                    <span class="brand-count">53 produk</span>
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="message-time" id="messageTime"></div>
                </div>
            </div>
            
            <!-- Input Container -->
            <div class="input-container">
                <input type="text" class="chat-input" placeholder="Ketik pesan Anda..." id="userInput">
                <button class="send-button ripple" onclick="sendMessage()">
                    <span class="material-icons" style="font-size: 20px;">send</span>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('messageTime').textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        // Category selection
        function selectCategory(category) {
            const categoryNames = {
                'pulsa': 'Pulsa',
                'data': 'Paket Data',
                'games': 'Voucher Game',
                'emoney': 'E-Money',
                'pln': 'Token PLN',
                'voucher': 'Voucher',
                'lainnya': 'Lainnya',
                'all': 'Semua Produk'
            };
            
            addUserMessage(`Saya ingin membeli ${categoryNames[category]}`);
            
            setTimeout(() => {
                addBotMessage(`Baik! Anda memilih kategori <strong>${categoryNames[category]}</strong>. 
                <br><br>Sedang memuat produk yang tersedia...
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>`);
                
                setTimeout(() => {
                    window.location.href = `mobile_products.php?category=${category}`;
                }, 2000);
            }, 500);
        }
        
        // Send message
        function sendMessage() {
            const input = document.getElementById('userInput');
            const message = input.value.trim();
            
            if (message) {
                addUserMessage(message);
                input.value = '';
                
                setTimeout(() => {
                    addBotMessage('Terima kasih atas pesan Anda. Tim customer service kami akan segera merespons.');
                }, 1000);
            }
        }
        
        // Brand selection
        function selectBrand(brand) {
            addUserMessage(`Saya ingin melihat produk ${brand}`);
            
            setTimeout(() => {
                addBotMessage(`Sedang memuat produk dari brand <strong>${brand}</strong>...
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>`);
                
                setTimeout(() => {
                    window.location.href = `mobile_products.php?brand=${brand}`;
                }, 1500);
            }, 500);
        }
        
        // Add user message
        function addUserMessage(message) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.style.cssText = `
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 16px;
                border-radius: 16px 16px 4px 16px;
                max-width: 85%;
                align-self: flex-end;
                margin-left: auto;
                box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
                margin-bottom: 16px;
            `;
            
            messageDiv.innerHTML = `
                <div style="font-size: 14px; line-height: 1.5; margin-bottom: 8px;">${message}</div>
                <div style="font-size: 11px; opacity: 0.8; text-align: right;">${new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Add bot message
        function addBotMessage(message) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message-bot';
            
            messageDiv.innerHTML = `
                <div class="message-text">${message}</div>
                <div class="message-time">${new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Header actions
        function toggleNotifications() {
            addBotMessage('🔔 Notifikasi aktif! Anda akan mendapat pemberitahuan untuk setiap transaksi.');
        }
        
        function showMenu() {
            addBotMessage(`📋 <strong>Menu Bantuan:</strong><br>
            • <a href="dashboard_products.php" style="color: #667eea;">Dashboard Admin</a><br>
            • <a href="admincenter.php" style="color: #667eea;">Admin Center</a><br>
            • Hubungi CS: <a href="https://wa.me/6281234567890" style="color: #667eea;">WhatsApp</a>`);
        }
        
        // Enter key support
        document.getElementById('userInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // PWA-like behavior
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            deferredPrompt = e;
        });
    </script>
</body>
</html>