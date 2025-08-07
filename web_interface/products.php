<?php
/**
 * Halaman Produk - Menampilkan daftar produk berdasarkan kategori
 */

require_once 'config.php';
session_start();

// Handle update products request
if (isset($_POST['update_products'])) {
    include 'update_products.php';
    $result = updateProductsFromAPI();
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header('Location: products.php');
    exit;
}

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Database connection SQLite
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Pastikan tabel products ada sesuai struktur yang sudah ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        product_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        price INTEGER,
        digiflazz_code TEXT,
        description TEXT,
        brand TEXT,
        type TEXT,
        seller TEXT
    )");
    
} catch (PDOException $e) {
    $pdo = null;
    $_SESSION['error_message'] = 'Database connection error: ' . $e->getMessage();
}

// Ambil produk dari database
$products = [];
$total_products = 0;

if ($pdo) {
    try {
        // Query untuk mengambil produk
        $where_conditions = [];
        $params = [];
        
        if ($category !== 'all' && !empty($category)) {
            $where_conditions[] = "type = ?";
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
        
        // Hitung total produk
        $count_sql = "SELECT COUNT(*) FROM products {$where_sql}";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total_products = $stmt->fetchColumn();
        
        // Ambil produk dengan limit
        $limit = 50;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM products {$where_sql} ORDER BY brand ASC, price ASC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Query error: ' . $e->getMessage();
    }
}

$category_names = [
    'pulsa' => 'Pulsa',
    'paket_data' => 'Paket Data',
    'pln' => 'PLN Token',
    'emoney' => 'E-Money',
    'game' => 'Voucher Game'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk <?= $category_names[$category] ?? ucfirst($category) ?> - Bot Pulsa</title>
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
        
        .container {
            width: 100%;
            max-width: 400px;
            height: 100vh;
            background: white;
            overflow-y: auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        
        .header {
            background: #075e54;
            color: white;
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .content {
            padding: 20px;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
        
        .product-grid {
            display: grid;
            gap: 12px;
        }
        
        .product-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .product-card:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .product-name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .product-price {
            color: #28a745;
            font-weight: bold;
            font-size: 16px;
        }
        
        .product-code {
            color: #6c757d;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .no-products {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }
        
        .update-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .update-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .update-btn:hover {
            background: #218838;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .search-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .stats {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 2px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .pagination a:hover {
            background: #f5f5f5;
        }
        
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        @media (max-width: 480px) {
            .container {
                max-width: 100%;
                height: 100vh;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🛍️ <?= $category_names[$category] ?? ucfirst($category) ?></h2>
            <p>Pilih produk yang ingin dibeli</p>
        </div>
        
        <div class="content">
            <a href="index.php" class="back-btn">⬅️ Kembali ke Menu</a>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="update-section">
                <h4>📦 Ambil Produk dari API Digiflazz</h4>
                <p>Klik tombol di bawah untuk mengambil daftar produk terbaru dari API Digiflazz.</p>
                <form method="post" style="display: inline;">
                    <button type="submit" name="update_products" class="update-btn" onclick="return confirm('Yakin ingin mengupdate produk? Ini akan mengganti semua produk yang ada.')">
                        🔄 Update Produk Sekarang
                    </button>
                </form>
            </div>
            
            <div class="stats">
                📊 Total produk dalam database: <strong><?= number_format($total_products) ?></strong>
            </div>
            
            <form method="get" class="filters">
                <select name="category" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?= $category == 'all' ? 'selected' : '' ?>>Semua Kategori</option>
                    <option value="pulsa" <?= $category == 'pulsa' ? 'selected' : '' ?>>Pulsa</option>
                    <option value="paket_data" <?= $category == 'paket_data' ? 'selected' : '' ?>>Paket Data</option>
                    <option value="pln" <?= $category == 'pln' ? 'selected' : '' ?>>PLN</option>
                    <option value="emoney" <?= $category == 'emoney' ? 'selected' : '' ?>>E-Money</option>
                    <option value="game" <?= $category == 'game' ? 'selected' : '' ?>>Game</option>
                </select>
                <input type="text" name="search" class="search-input" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="update-btn">🔍 Cari</button>
            </form>
            
            <div class="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                        <div class="product-card" onclick="buyProduct('<?= htmlspecialchars($product['digiflazz_code'] ?? '') ?>', '<?= htmlspecialchars($product['name'] ?? '') ?>', <?= $product['price'] ?? 0 ?>)">
                            <div class="product-name"><?= htmlspecialchars($product['name'] ?? 'Produk Tidak Tersedia') ?></div>
                            <div class="product-price">Rp <?= number_format($product['price'] ?? 0) ?></div>
                            <div class="product-code">
                                Kode: <?= htmlspecialchars($product['digiflazz_code'] ?? 'N/A') ?> | 
                                Brand: <?= htmlspecialchars($product['brand'] ?? 'Unknown') ?> |
                                Kategori: <?= htmlspecialchars($product['type'] ?? 'Unknown') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <h3>📭 Belum Ada Produk</h3>
                        <p>Klik tombol "Update Produk Sekarang" di atas untuk mengambil produk dari API Digiflazz.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function buyProduct(code, name, price) {
            if (confirm(`Beli ${name} seharga Rp ${price.toLocaleString('id-ID')}?`)) {
                window.location.href = `purchase.php?code=${code}&name=${encodeURIComponent(name)}&price=${price}`;
            }
        }
    </script>
</body>
</html>