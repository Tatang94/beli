<?php
/**
 * PPOB Indonesia - Payment Point Online Bank
 * Sistem PPOB Lengkap dengan fitur standar Indonesia
 */

session_start();
require_once 'config.php';

// Database connection
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$action = $_GET['action'] ?? 'home';
$category = $_GET['cat'] ?? '';

// PPOB Categories with icons - Sesuai standar PPOB Indonesia
$ppob_categories = [
    // TAGIHAN BULANAN WAJIB
    'PLN' => ['icon' => '⚡', 'name' => 'Listrik PLN', 'desc' => 'Token & Tagihan PLN'],
    'PDAM' => ['icon' => '💧', 'name' => 'Air PDAM', 'desc' => 'Tagihan Air Daerah'],
    'BPJS' => ['icon' => '🏥', 'name' => 'BPJS', 'desc' => 'Kesehatan & Ketenagakerjaan'],
    'Multifinance' => ['icon' => '🏍️', 'name' => 'Cicilan', 'desc' => 'Motor, Mobil, Kartu Kredit'],
    'PBB' => ['icon' => '🏠', 'name' => 'PBB', 'desc' => 'Pajak Bumi Bangunan'],
    'SAMSAT' => ['icon' => '🚗', 'name' => 'SAMSAT', 'desc' => 'Pajak Kendaraan'],
    
    // TELEKOMUNIKASI
    'Pulsa' => ['icon' => '📱', 'name' => 'Pulsa', 'desc' => 'All Operator'],
    'Data' => ['icon' => '📶', 'name' => 'Paket Data', 'desc' => 'Kuota Internet'],
    'SMS Telpon' => ['icon' => '📞', 'name' => 'SMS & Telepon', 'desc' => 'Paket Komunikasi'],
    
    // E-MONEY & DIGITAL
    'E-Money' => ['icon' => '💳', 'name' => 'E-Money', 'desc' => 'GoPay, OVO, DANA, dll'],
    'Game' => ['icon' => '🎮', 'name' => 'Voucher Game', 'desc' => 'ML, PUBG, Free Fire'],
    'Voucher' => ['icon' => '🎫', 'name' => 'Voucher', 'desc' => 'Google Play, iTunes'],
    
    // HIBURAN & LIFESTYLE
    'Streaming' => ['icon' => '📺', 'name' => 'TV & Streaming', 'desc' => 'Netflix, Spotify, TV Kabel'],
    'Gas' => ['icon' => '🔥', 'name' => 'Gas PGN', 'desc' => 'Tagihan Gas Negara'],
    
    // INTERNASIONAL
    'China Topup' => ['icon' => '🇨🇳', 'name' => 'China Topup', 'desc' => 'WeChat, Alipay'],
    'Malaysia Topup' => ['icon' => '🇲🇾', 'name' => 'Malaysia Topup', 'desc' => 'Maxis, Celcom'],
    'Thailand Topup' => ['icon' => '🇹🇭', 'name' => 'Thailand Topup', 'desc' => 'AIS, DTAC'],
    'Singapore Topup' => ['icon' => '🇸🇬', 'name' => 'Singapore Topup', 'desc' => 'Singtel, M1'],
];

// Get category counts from database
$category_counts = [];
try {
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category_counts[$row['category']] = $row['count'];
    }
} catch (PDOException $e) {
    // Silent error handling
}

// Get products for selected category
$products = [];
if ($action === 'products' && $category) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY price ASC LIMIT 50");
        $stmt->execute([$category]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $products = [];
    }
}

// Handle purchase action
if ($action === 'buy') {
    $code = $_GET['code'] ?? '';
    $name = $_GET['name'] ?? '';
    $price = $_GET['price'] ?? '';
    
    // Redirect to mobile purchase interface
    if ($code && $name && $price) {
        header("Location: mobile_purchase.php?code=" . urlencode($code) . "&name=" . urlencode($name) . "&price=" . urlencode($price) . "&cat=" . urlencode($category));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPOB Indonesia - Payment Point Online Bank</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header PPOB */
        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .logo span {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .balance {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem 0;
        }
        
        .hero {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            color: white;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            opacity: 0.8;
        }
        
        /* Categories Grid */
        .categories-section {
            background: white;
            border-radius: 20px 20px 0 0;
            padding: 3rem 2rem;
            margin-top: 2rem;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .section-title h3 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .section-title p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .category-card {
            background: white;
            border: 2px solid #f0f0f0;
            border-radius: 16px;
            padding: 1.5rem;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .category-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .category-card:hover::before {
            transform: scaleX(1);
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }
        
        .category-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .category-info h4 {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
            color: #333;
        }
        
        .category-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .category-count {
            background: #f8f9fa;
            color: #667eea;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Products List */
        .products-section {
            background: white;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-info {
            margin-bottom: 1rem;
        }
        
        .product-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .product-brand {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .product-price {
            font-size: 1.2rem;
            color: #667eea;
            font-weight: 700;
        }
        
        .buy-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .buy-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .hero h2 {
                font-size: 2rem;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .categories-section {
                padding: 2rem 1rem;
            }
        }
        
        /* Footer */
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
        }
        
        .footer p {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>💳 PPOB Indonesia</h1>
                    <span>Payment Point Online Bank</span>
                </div>
                <div class="balance">
                    <a href="deposit.php" style="color: white; text-decoration: none; margin-right: 1rem;">
                        💰 Saldo: Rp <?= number_format($_SESSION['balance'] ?? 0) ?>
                    </a>
                    <a href="history.php" style="color: white; text-decoration: none;">
                        📊 Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="main-content">
            
            <?php if ($action === 'home'): ?>
            
            <!-- Hero Section -->
            <div class="hero">
                <h2>PPOB Terlengkap Indonesia</h2>
                <p>Bayar tagihan, isi pulsa, beli token listrik, dan 1000+ layanan lainnya dalam satu platform</p>
            </div>
            
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-card">
                    <h3>1,178+</h3>
                    <p>Produk Digital</p>
                </div>
                <div class="stat-card">
                    <h3>18</h3>
                    <p>Kategori Lengkap</p>
                </div>
                <div class="stat-card">
                    <h3>99.9%</h3>
                    <p>Uptime Server</p>
                </div>
                <div class="stat-card">
                    <h3>24/7</h3>
                    <p>Customer Support</p>
                </div>
            </div>
            
            <!-- Categories Section -->
            <div class="categories-section">
                <div class="section-title">
                    <h3>Layanan PPOB Lengkap</h3>
                    <p>Pilih kategori layanan yang Anda butuhkan</p>
                </div>
                
                <div class="categories-grid">
                    <?php foreach ($ppob_categories as $cat_key => $cat_info): 
                        $count = $category_counts[$cat_key] ?? 0;
                        if ($count > 0): // Only show categories with products
                    ?>
                    <a href="?action=products&cat=<?= urlencode($cat_key) ?>" class="category-card">
                        <div class="category-header">
                            <div class="category-icon"><?= $cat_info['icon'] ?></div>
                            <div class="category-info">
                                <h4><?= htmlspecialchars($cat_info['name']) ?></h4>
                                <p><?= htmlspecialchars($cat_info['desc']) ?></p>
                            </div>
                        </div>
                        <div class="category-count">
                            <?= number_format($count) ?> layanan
                        </div>
                    </a>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            
            <?php elseif ($action === 'products' && $category): ?>
            
            <!-- Products Section -->
            <div class="products-section">
                <a href="?" class="back-btn">← Kembali ke Beranda</a>
                
                <div class="section-title">
                    <?php $cat_info = $ppob_categories[$category] ?? ['icon' => '📦', 'name' => $category]; ?>
                    <h3><?= $cat_info['icon'] ?> <?= htmlspecialchars($cat_info['name']) ?></h3>
                    <p><?= count($products) ?> produk tersedia</p>
                </div>
                
                <?php if (empty($products)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">
                    Tidak ada produk dalam kategori ini saat ini.
                </p>
                <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-brand">📍 <?= htmlspecialchars($product['brand']) ?></div>
                            <div class="product-price">Rp <?= number_format($product['price']) ?></div>
                        </div>
                        <a href="?action=buy&code=<?= urlencode($product['digiflazz_code']) ?>&name=<?= urlencode($product['name']) ?>&price=<?= $product['price'] ?>&cat=<?= urlencode($category) ?>" 
                           class="buy-btn" style="text-decoration: none; text-align: center; display: block;">
                            💳 Beli Sekarang
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Layanan PPOB</h4>
                    <ul>
                        <li><a href="?action=products&cat=Pulsa">Pulsa All Operator</a></li>
                        <li><a href="?action=products&cat=PLN">Token Listrik PLN</a></li>
                        <li><a href="?action=products&cat=PDAM">Tagihan Air PDAM</a></li>
                        <li><a href="?action=products&cat=Game">Voucher Game</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Bantuan</h4>
                    <ul>
                        <li><a href="help.php">Customer Service</a></li>
                        <li><a href="help.php#faq">FAQ</a></li>
                        <li><a href="help.php#panduan">Panduan Transaksi</a></li>
                        <li><a href="history.php">Riwayat Transaksi</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Keamanan</h4>
                    <ul>
                        <li>🔒 SSL 256-bit Encryption</li>
                        <li>🏦 Terdaftar Bank Indonesia</li>
                        <li>✅ ISO 27001 Certified</li>
                        <li>🛡️ PCI DSS Compliant</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Kontak</h4>
                    <ul>
                        <li>📱 WhatsApp: 0812-3456-789</li>
                        <li>📧 Email: cs@ppob-indonesia.com</li>
                        <li>📞 Call: 021-1234-5678</li>
                        <li>🕒 24/7 Customer Support</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 PPOB Indonesia. Platform pembayaran online terpercaya dan aman.</p>
            </div>
        </div>
    </div>
    
    <!-- Floating Help Button -->
    <div class="help-float">
        <a href="help.php" class="help-btn" title="Bantuan & Customer Service">
            🆘
        </a>
    </div>
</body>
</html>