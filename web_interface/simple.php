<?php
/**
 * Bot Pulsa Digital - Simple Interface
 * Interface sederhana namun lengkap dengan produk API
 */

session_start();
require_once 'config.php';

// Database connection SQLite
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
}

// Get category and search parameters
$category = $_GET['cat'] ?? '';
$search = $_GET['search'] ?? '';
$action = $_GET['action'] ?? 'home';

// Get products from database
$products = [];
$categories = [];

// Kategori aktual dengan produk yang tersedia (setelah redistributrasi)
$default_categories = [
    ['category' => 'Data', 'count' => 0],
    ['category' => 'Pulsa', 'count' => 0],
    ['category' => 'Game', 'count' => 0],
    ['category' => 'E-Money', 'count' => 0],
    ['category' => 'PLN', 'count' => 0],
    ['category' => 'Streaming', 'count' => 0],
    ['category' => 'Voucher', 'count' => 0]
];

if ($pdo) {
    // Merge database counts with comprehensive categories
    try {
        $cat_sql = "SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC";
        $stmt = $pdo->query($cat_sql);
        $db_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update default categories with actual counts from database
        $categories = $default_categories;
        foreach ($db_categories as $db_cat) {
            foreach ($categories as &$cat) {
                if (strtolower($cat['category']) === strtolower($db_cat['category'])) {
                    $cat['count'] = $db_cat['count'];
                    break;
                }
            }
        }
    } catch (PDOException $e) {
        $categories = $default_categories;
    }
    
    // Get products based on filters
    if ($action == 'products') {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($category)) {
                $where_conditions[] = "category = ?";
                $params[] = $category;
            }
            
            if (!empty($search)) {
                $where_conditions[] = "(name LIKE ? OR brand LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            
            $where_sql = '';
            if (!empty($where_conditions)) {
                $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
            }
            
            $sql = "SELECT * FROM products {$where_sql} ORDER BY brand ASC, price ASC LIMIT 100";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // Silent fail
        }
    }
} else {
    $categories = $default_categories;
}

// Category icons (hanya kategori aktif)
$cat_icons = [
    'Data' => '🌐',
    'Pulsa' => '📱',
    'Game' => '🎮', 
    'E-Money' => '💳',
    'PLN' => '⚡',
    'Streaming' => '📺',
    'Voucher' => '🎫'
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Pulsa Digital</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
        }
        
        .header {
            background: #25d366;
            color: white;
            padding: 15px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .content {
            padding: 20px;
        }
        
        .search-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .category-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: #333;
        }
        
        .category-card:hover {
            border-color: #25d366;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.2);
        }
        
        .category-icon {
            font-size: 30px;
            margin-bottom: 10px;
        }
        
        .category-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .category-count {
            font-size: 12px;
            color: #666;
        }
        
        .product-list {
            display: grid;
            gap: 10px;
        }
        
        .product-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .product-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .product-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .product-price {
            color: #25d366;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .product-info {
            font-size: 12px;
            color: #666;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
        }
        
        .stats {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .buy-btn {
            background: #25d366;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            margin-top: 10px;
        }
        
        @media (max-width: 480px) {
            .container {
                max-width: 100%;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 Bot Pulsa Digital</h1>
            <p>Pulsa, Data, Game, E-Money & Lainnya</p>
        </div>
        
        <div class="content">
            <?php if ($action == 'home'): ?>
                <!-- HOME PAGE -->
                <div class="search-box">
                    <form method="get">
                        <input type="hidden" name="action" value="products">
                        <input type="text" name="search" class="search-input" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
                    </form>
                </div>
                
                <div class="stats">
                    📊 <strong>26</strong> kategori lengkap tersedia | 🔄 Siap menerima produk dari API
                </div>
                
                <div class="category-grid">
                    <?php foreach ($categories as $cat): ?>
                        <a href="?action=products&cat=<?= urlencode($cat['category']) ?>" class="category-card">
                            <div class="category-icon"><?= $cat_icons[$cat['category']] ?? '📦' ?></div>
                            <div class="category-name"><?= ucfirst($cat['category']) ?></div>
                            <div class="category-count"><?= number_format($cat['count']) ?> produk</div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($categories)): ?>
                    <div class="no-data">
                        <h3>📭 Tidak Ada Data</h3>
                        <p>Database produk kosong. Silakan jalankan update produk terlebih dahulu.</p>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($action == 'products'): ?>
                <!-- PRODUCTS PAGE -->
                <a href="?" class="back-btn">← Kembali</a>
                
                <?php if (!empty($category)): ?>
                    <h2><?= $cat_icons[$category] ?? '📦' ?> <?= ucfirst($category) ?></h2>
                <?php else: ?>
                    <h2>🔍 Hasil Pencarian: "<?= htmlspecialchars($search) ?>"</h2>
                <?php endif; ?>
                
                <div class="search-box">
                    <form method="get">
                        <input type="hidden" name="action" value="products">
                        <input type="hidden" name="cat" value="<?= htmlspecialchars($category) ?>">
                        <input type="text" name="search" class="search-input" placeholder="Cari dalam kategori..." value="<?= htmlspecialchars($search) ?>">
                    </form>
                </div>
                
                <div class="stats">
                    🎯 <strong><?= count($products) ?></strong> produk ditemukan
                </div>
                
                <div class="product-list">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-item" onclick="buyProduct('<?= htmlspecialchars($product['digiflazz_code']) ?>', '<?= htmlspecialchars($product['name']) ?>', <?= $product['price'] ?>)">
                                <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="product-price">Rp <?= number_format($product['price']) ?></div>
                                <div class="product-info">
                                    Brand: <?= htmlspecialchars($product['brand']) ?> | Kode: <?= htmlspecialchars($product['digiflazz_code']) ?>
                                </div>
                                <button class="buy-btn" type="button">💰 Beli Sekarang</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <h3>📭 Tidak Ada Produk</h3>
                            <p>Tidak ada produk yang sesuai dengan kriteria pencarian.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($action == 'buy'): ?>
                <!-- BUY PAGE -->
                <a href="?action=products&cat=<?= urlencode($_GET['cat'] ?? '') ?>" class="back-btn">← Kembali</a>
                
                <h2>💰 Konfirmasi Pembelian</h2>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3><?= htmlspecialchars($_GET['name'] ?? '') ?></h3>
                    <p><strong>Harga: Rp <?= number_format($_GET['price'] ?? 0) ?></strong></p>
                    <p>Kode: <?= htmlspecialchars($_GET['code'] ?? '') ?></p>
                </div>
                
                <form style="display: grid; gap: 15px;">
                    <input type="text" placeholder="Nomor Tujuan (08xxx)" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                    <button type="submit" class="buy-btn">Lanjutkan Pembayaran</button>
                </form>
                
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function buyProduct(code, name, price) {
            if (confirm(`Beli ${name}\nHarga: Rp ${price.toLocaleString('id-ID')}\n\nLanjutkan?`)) {
                window.location.href = `?action=buy&code=${code}&name=${encodeURIComponent(name)}&price=${price}&cat=<?= urlencode($category) ?>`;
            }
        }
    </script>
</body>
</html>