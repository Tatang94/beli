<?php
/**
 * Script untuk update produk dari Digiflazz API
 * Jalankan secara manual atau setup cron job
 */

require_once 'config.php';

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function updateProductsFromDigiflazz($pdo) {
    // Create signature for Digiflazz API
    $username = DIGIFLAZZ_USERNAME;
    $api_key = DIGIFLAZZ_KEY;
    $sign_string = $username . $api_key . 'pricelist';
    $sign = md5($sign_string);
    
    // Prepare request data
    $data = [
        'cmd' => 'prepaid',
        'username' => $username,
        'sign' => $sign
    ];
    
    // Make API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.digiflazz.com/v1/price-list');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [false, "HTTP Error: $http_code"];
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['data'])) {
        return [false, "Invalid API response"];
    }
    
    // Clear existing products
    $pdo->exec("TRUNCATE TABLE products");
    
    // Insert new products
    $stmt = $pdo->prepare("
        INSERT INTO products (name, price, digiflazz_code, description, brand, type, seller) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $count = 0;
    foreach ($result['data'] as $product) {
        if ($product['product_status'] && $product['seller_product_status']) {
            $stmt->execute([
                $product['product_name'],
                $product['price'],
                $product['buyer_sku_code'],
                $product['desc'] ?? '',
                $product['brand'],
                $product['category'],
                $product['seller_name']
            ]);
            $count++;
        }
    }
    
    return [true, "Successfully updated $count products"];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Produk Digiflazz</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .loading { text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔄 Update Produk dari Digiflazz</h1>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'update'): ?>
        <div class="loading">
            <p>⏳ Sedang mengupdate produk dari Digiflazz...</p>
            <p>Proses ini mungkin memakan waktu beberapa menit.</p>
        </div>
        
        <?php
        $result = updateProductsFromDigiflazz($pdo);
        
        if ($result[0]) {
            echo '<div class="success">✅ ' . $result[1] . '</div>';
        } else {
            echo '<div class="error">❌ ' . $result[1] . '</div>';
        }
        
        // Show product count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<div class="info">📊 Total produk dalam database: ' . $count['count'] . '</div>';
        ?>
    <?php else: ?>
        <div class="info">
            <h3>ℹ️ Informasi Update Produk</h3>
            <ul>
                <li>Proses ini akan mengambil semua produk terbaru dari Digiflazz</li>
                <li>Semua produk lama akan dihapus dan diganti dengan yang baru</li>
                <li>Pastikan koneksi internet stabil</li>
                <li>Proses dapat memakan waktu 1-5 menit</li>
            </ul>
        </div>
        
        <?php
        // Show current product count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<div class="info">📊 Produk saat ini dalam database: ' . $count['count'] . '</div>';
        ?>
        
        <p>
            <button onclick="if(confirm('Yakin ingin update produk? Semua produk lama akan dihapus.')) { location.href='?action=update'; }">
                🔄 Update Produk Sekarang
            </button>
        </p>
    <?php endif; ?>
    
    <h2>📝 Setup Cron Job (Opsional)</h2>
    <div class="info">
        <p>Untuk update otomatis, tambahkan cron job di cPanel:</p>
        <code>0 6 * * * /usr/local/bin/php <?= __DIR__ ?>/update_products.php >/dev/null 2>&1</code>
        <p><small>Ini akan menjalankan update setiap hari jam 6 pagi</small></p>
    </div>
</body>
</html>