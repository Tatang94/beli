<?php
/**
 * Halaman Produk - Menampilkan daftar produk berdasarkan kategori
 */

require_once 'config.php';
session_start();

$category = $_GET['category'] ?? 'pulsa';

// Database connection dengan fallback
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
}

// Ambil produk dari database atau buat dummy data
$products = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category LIKE ? AND status = 'active' ORDER BY price ASC LIMIT 20");
        $stmt->execute(["%{$category}%"]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback data
    }
}

// Jika tidak ada data di database, gunakan data dummy
if (empty($products)) {
    switch($category) {
        case 'pulsa':
            $products = [
                ['product_name' => 'Telkomsel 5.000', 'price' => 5500, 'product_code' => 'S5'],
                ['product_name' => 'Telkomsel 10.000', 'price' => 10200, 'product_code' => 'S10'],
                ['product_name' => 'Telkomsel 20.000', 'price' => 20200, 'product_code' => 'S20'],
                ['product_name' => 'Indosat 5.000', 'price' => 5300, 'product_code' => 'I5'],
                ['product_name' => 'Indosat 10.000', 'price' => 10100, 'product_code' => 'I10'],
                ['product_name' => 'XL 5.000', 'price' => 5400, 'product_code' => 'X5'],
                ['product_name' => 'XL 10.000', 'price' => 10400, 'product_code' => 'X10'],
                ['product_name' => 'Tri 5.000', 'price' => 5200, 'product_code' => 'T5'],
            ];
            break;
        case 'paket_data':
            $products = [
                ['product_name' => 'Telkomsel Data 1GB', 'price' => 15000, 'product_code' => 'SD1GB'],
                ['product_name' => 'Telkomsel Data 2GB', 'price' => 25000, 'product_code' => 'SD2GB'],
                ['product_name' => 'Indosat Data 1GB', 'price' => 14000, 'product_code' => 'ID1GB'],
                ['product_name' => 'XL Data 1GB', 'price' => 16000, 'product_code' => 'XD1GB'],
            ];
            break;
        case 'pln':
            $products = [
                ['product_name' => 'PLN 20.000', 'price' => 20500, 'product_code' => 'PLN20'],
                ['product_name' => 'PLN 50.000', 'price' => 50500, 'product_code' => 'PLN50'],
                ['product_name' => 'PLN 100.000', 'price' => 100500, 'product_code' => 'PLN100'],
            ];
            break;
        default:
            $products = [
                ['product_name' => 'Produk akan segera tersedia', 'price' => 0, 'product_code' => 'SOON'],
            ];
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
            
            <div class="product-grid">
                <?php if (!empty($products) && $products[0]['price'] > 0): ?>
                    <?php foreach($products as $product): ?>
                        <div class="product-card" onclick="buyProduct('<?= htmlspecialchars($product['product_code']) ?>', '<?= htmlspecialchars($product['product_name']) ?>', <?= $product['price'] ?>)">
                            <div class="product-name"><?= htmlspecialchars($product['product_name']) ?></div>
                            <div class="product-price">Rp <?= number_format($product['price']) ?></div>
                            <div class="product-code">Kode: <?= htmlspecialchars($product['product_code']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <h3>Produk Segera Hadir</h3>
                        <p>Kategori ini sedang dalam pengembangan.</p>
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