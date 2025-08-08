<?php
/**
 * Halaman Produk Detail dengan Struktur Hierarki
 * Implementasi: Category → Brand → Produk (sorted by price/name)
 */

// Session start (harus sebelum output apapun)
session_start();
require_once 'config.php';

$sort_by = $_GET['sort'] ?? 'price'; // price atau name
$category_filter = $_GET['category'] ?? 'all';

// Database connection
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build category filter
    $category_where = "";
    $params = [];
    if ($category_filter !== 'all') {
        $category_where = "AND category = ?";
        $params[] = $category_filter;
    }
    
    // Ambil semua produk dengan filter PREPAID dan sorting
    $sort_column = ($sort_by === 'name') ? 'name' : 'price';
    $sort_order = ($sort_by === 'name') ? 'ASC' : 'ASC';
    
    $query = "
        SELECT * FROM products 
        WHERE (type != 'POSTPAID' OR type IS NULL OR type = '') 
        $category_where
        ORDER BY 
            CASE category 
                WHEN 'Data' THEN 1
                WHEN 'Pulsa' THEN 2  
                WHEN 'Game' THEN 3
                WHEN 'E-Money' THEN 4
                WHEN 'PLN' THEN 5
                ELSE 6
            END,
            brand ASC,
            $sort_column $sort_order
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Struktur hierarki: Category → Brand → Products
    $hierarchy = [];
    foreach ($all_products as $product) {
        $category = $product['category'];
        $brand = $product['brand'];
        
        if (!isset($hierarchy[$category])) {
            $hierarchy[$category] = [];
        }
        if (!isset($hierarchy[$category][$brand])) {
            $hierarchy[$category][$brand] = [];
        }
        
        $hierarchy[$category][$brand][] = $product;
    }
    
    // Statistik
    $stats_query = "
        SELECT 
            COUNT(*) as total_products,
            COUNT(DISTINCT category) as total_categories,
            COUNT(DISTINCT brand) as total_brands
        FROM products 
        WHERE (type = 'PREPAID' OR type IS NULL OR type = '')
        $category_where
    ";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($params);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $hierarchy = [];
    $stats = ['total_products' => 0, 'total_categories' => 0, 'total_brands' => 0];
}

$category_icons = [
    'Data' => '🌐',
    'Pulsa' => '📱', 
    'E-Money' => '💳',
    'Game' => '🎮',
    'PLN' => '⚡'
];

$category_names = [
    'Data' => 'Paket Data',
    'Pulsa' => 'Pulsa',
    'E-Money' => 'E-Money', 
    'Game' => 'Voucher Game',
    'PLN' => 'Token PLN'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Detail Terstruktur - Bot Pulsa Digital</title>
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
        
        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .control-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .control-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .control-btn.active {
            background: #333;
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
        
        /* Category Section */
        .category-section {
            background: rgba(255,255,255,0.95);
            margin-bottom: 25px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .category-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 20px;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Brand Section */
        .brand-section {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .brand-section:last-child {
            border-bottom: none;
        }
        
        .brand-header {
            background: #f8f9fa;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 500;
            color: #555;
            border-left: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .brand-stats {
            font-size: 12px;
            color: #888;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .product-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 16px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .product-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .product-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .product-price {
            font-size: 16px;
            font-weight: 700;
            color: #667eea;
        }
        
        .product-type {
            font-size: 11px;
            background: #e8f2ff;
            color: #667eea;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .product-desc {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            line-height: 1.3;
        }
        
        /* Navigation */
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
                padding: 15px;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                justify-content: center;
            }
        }
        
        /* Empty State */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #666;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Struktur Produk Hierarki</h1>
            <p>Produk PREPAID dikelompokkan: Category → Brand → Produk (Terurut)</p>
            
            <div class="controls">
                <div class="filter-group">
                    <strong>Kategori:</strong>
                    <button class="control-btn <?php echo $category_filter === 'all' ? 'active' : ''; ?>" 
                            onclick="filterCategory('all')">Semua</button>
                    <?php foreach ($category_names as $cat_key => $cat_name): ?>
                        <button class="control-btn <?php echo $category_filter === $cat_key ? 'active' : ''; ?>" 
                                onclick="filterCategory('<?php echo $cat_key; ?>')"><?php echo $cat_name; ?></button>
                    <?php endforeach; ?>
                </div>
                
                <div class="filter-group">
                    <strong>Urut:</strong>
                    <button class="control-btn <?php echo $sort_by === 'price' ? 'active' : ''; ?>" 
                            onclick="sortBy('price')">Harga</button>
                    <button class="control-btn <?php echo $sort_by === 'name' ? 'active' : ''; ?>" 
                            onclick="sortBy('name')">Nama</button>
                </div>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($stats['total_products']); ?></span>
                    <span class="stat-label">Total Produk PREPAID</span>
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
            <a href="products_grouped.php" class="nav-btn">📋 Ringkasan</a>
            <a href="mobile_products.php" class="nav-btn">📱 Mobile View</a>
        </div>
        
        <?php if (!empty($hierarchy)): ?>
            <?php foreach ($hierarchy as $category_name => $brands): ?>
            <div class="category-section">
                <div class="category-header">
                    <span><?php echo $category_icons[$category_name] ?? '📦'; ?></span>
                    <span><?php echo strtoupper($category_names[$category_name] ?? $category_name); ?></span>
                    <span style="margin-left: auto; font-size: 14px; opacity: 0.9;">
                        <?php echo count($brands); ?> Brand | 
                        <?php echo array_sum(array_map('count', $brands)); ?> Produk
                    </span>
                </div>
                
                <?php foreach ($brands as $brand_name => $products): ?>
                <div class="brand-section">
                    <div class="brand-header">
                        <span><strong><?php echo htmlspecialchars($brand_name); ?></strong></span>
                        <span class="brand-stats">
                            <?php echo count($products); ?> produk | 
                            Rp <?php echo number_format(min(array_column($products, 'price'))); ?> - 
                            Rp <?php echo number_format(max(array_column($products, 'price'))); ?>
                        </span>
                    </div>
                    
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                        <div class="product-card" onclick="selectProduct('<?php echo htmlspecialchars($product['digiflazz_code']); ?>', '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)">
                            <div class="product-name">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </div>
                            <div class="product-info">
                                <div class="product-price">
                                    Rp <?php echo number_format($product['price']); ?>
                                </div>
                                <?php if (!empty($product['type'])): ?>
                                <div class="product-type">
                                    <?php echo strtoupper($product['type']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($product['description'])): ?>
                            <div class="product-desc">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <div class="category-section">
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>Belum ada data produk PREPAID</h3>
                <p>Silakan jalankan auto update untuk mengisi database produk</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function filterCategory(category) {
            const url = new URL(window.location);
            url.searchParams.set('category', category);
            window.location = url;
        }
        
        function sortBy(sort) {
            const url = new URL(window.location);
            url.searchParams.set('sort', sort);
            window.location = url;
        }
        
        function selectProduct(code, name, price) {
            if (confirm(`Pilih produk: ${name}\nHarga: Rp ${price.toLocaleString('id-ID')}\n\nLanjutkan ke pembelian?`)) {
                window.location.href = `mobile_purchase.php?product=${code}`;
            }
        }
    </script>
</body>
</html>