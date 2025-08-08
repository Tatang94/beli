<?php
/**
 * Halaman Produk Terkelompok - Menampilkan semua produk dikelompokkan berdasarkan kategori dan brand
 */

// Session start (harus sebelum output apapun)
session_start();
require_once 'config.php';

// Database connection
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ambil semua produk dikelompokkan berdasarkan kategori dan brand dengan sorting
    $query = "
        SELECT category, brand, COUNT(*) as total_products, 
               MIN(price) as min_price, MAX(price) as max_price,
               GROUP_CONCAT(name, ' | ') as sample_products
        FROM products 
        WHERE type = 'PREPAID' OR type IS NULL OR type = ''
        GROUP BY category, brand 
        ORDER BY 
            CASE category 
                WHEN 'Data' THEN 1
                WHEN 'Pulsa' THEN 2  
                WHEN 'Game' THEN 3
                WHEN 'E-Money' THEN 4
                WHEN 'PLN' THEN 5
                ELSE 6
            END,
            brand ASC
    ";
    $stmt = $pdo->query($query);
    $grouped_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil statistik keseluruhan
    $stats_query = "
        SELECT 
            COUNT(*) as total_all_products,
            COUNT(DISTINCT category) as total_categories,
            COUNT(DISTINCT brand) as total_brands
        FROM products
    ";
    $stats_stmt = $pdo->query($stats_query);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $grouped_products = [];
    $stats = ['total_all_products' => 0, 'total_categories' => 0, 'total_brands' => 0];
}

// Kelompokkan berdasarkan kategori
$categories = [];
foreach ($grouped_products as $product) {
    $categories[$product['category']][] = $product;
}

$category_icons = [
    'Data' => '🌐',
    'Pulsa' => '📱', 
    'E-Money' => '💳',
    'Game' => '🎮',
    'PLN' => '⚡'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Produk Terkelompok - Bot Pulsa Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .category-section {
            background: rgba(255,255,255,0.95);
            margin-bottom: 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .category-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .brand-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .brand-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .brand-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .brand-name {
            font-size: 16px;
            font-weight: 500;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .brand-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .info-item {
            text-align: center;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 6px;
        }
        
        .info-label {
            font-size: 11px;
            color: #666;
            display: block;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        
        .price-range {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            text-align: center;
            padding: 6px;
            background: #e8f2ff;
            border-radius: 6px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .nav-btn {
            background: rgba(255,255,255,0.9);
            color: #667eea;
            padding: 10px 20px;
            border: 2px solid #667eea;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .brand-grid {
                grid-template-columns: 1fr;
                padding: 15px;
            }
            
            .stats {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Daftar Produk Terkelompok</h1>
            <p>Semua produk dikelompokkan berdasarkan kategori dan brand</p>
            
            <div class="stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($stats['total_all_products']); ?></span>
                    <span class="stat-label">Total Produk</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_categories']; ?></span>
                    <span class="stat-label">Kategori</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_brands']; ?></span>
                    <span class="stat-label">Brand</span>
                </div>
            </div>
        </div>
        
        <div class="nav-buttons">
            <a href="mobile_interface.php" class="nav-btn">🏠 Beranda</a>
            <a href="products_detailed.php" class="nav-btn">📊 Detail Hierarki</a>
            <a href="mobile_products.php" class="nav-btn">📱 Mobile View</a>
        </div>
        
        <?php foreach ($categories as $category_name => $brands): ?>
        <div class="category-section">
            <div class="category-header">
                <span><?php echo $category_icons[$category_name] ?? '📦'; ?></span>
                <span><?php echo strtoupper($category_name); ?></span>
                <span style="margin-left: auto; font-size: 14px; opacity: 0.9;">
                    <?php echo count($brands); ?> Brand | 
                    <?php echo array_sum(array_column($brands, 'total_products')); ?> Produk
                </span>
            </div>
            
            <div class="brand-grid">
                <?php foreach ($brands as $brand): ?>
                <div class="brand-card" onclick="window.location.href='mobile_products.php?category=<?php echo urlencode($category_name); ?>&brand=<?php echo urlencode($brand['brand']); ?>'">
                    <div class="brand-name"><?php echo htmlspecialchars($brand['brand']); ?></div>
                    
                    <div class="brand-info">
                        <div class="info-item">
                            <span class="info-label">Produk</span>
                            <span class="info-value"><?php echo $brand['total_products']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Kategori</span>
                            <span class="info-value"><?php echo strtoupper($brand['category']); ?></span>
                        </div>
                    </div>
                    
                    <div class="price-range">
                        💰 Rp <?php echo number_format($brand['min_price']); ?> - Rp <?php echo number_format($brand['max_price']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($categories)): ?>
        <div class="category-section">
            <div style="padding: 40px; text-align: center; color: #666;">
                <h3>Belum ada data produk</h3>
                <p>Silakan jalankan auto update untuk mengisi database produk</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>