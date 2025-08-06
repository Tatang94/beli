<?php
/**
 * Script untuk update produk dari Digiflazz API
 * Jalankan secara manual atau setup cron job
 */

require_once '../config.php';
require_once '../includes/database.php';
require_once '../includes/digiflazz.php';

// Check if accessed via admin
session_start();
$is_admin_access = isset($_SESSION['admin_logged_in']) || isset($_GET['admin_key']);

if (!$is_admin_access && !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    // Simple admin key check for remote access
    if (!isset($_GET['key']) || $_GET['key'] !== md5(BOT_TOKEN . 'update_products')) {
        http_response_code(403);
        die('Access denied');
    }
}

// Initialize components
$db = Database::getInstance();
$digiflazz = new DigiflazzAPI(DIGIFLAZZ_USERNAME, DIGIFLAZZ_KEY);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Produk Digiflazz</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        .loading { text-align: center; margin: 20px 0; }
        .progress { width: 100%; background: #f0f0f0; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 30px; background: #007bff; border-radius: 5px; line-height: 30px; color: white; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <h1>🔄 Update Produk dari Digiflazz</h1>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'update'): ?>
        <div class="loading">
            <p>⏳ Sedang mengupdate produk dari Digiflazz...</p>
            <p>Proses ini mungkin memakan waktu beberapa menit.</p>
            <div class="progress">
                <div class="progress-bar" style="width: 0%" id="progress">0%</div>
            </div>
        </div>
        
        <script>
        // Simulate progress
        let progress = 0;
        const progressBar = document.getElementById('progress');
        const interval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress > 90) progress = 90;
            progressBar.style.width = progress + '%';
            progressBar.textContent = Math.round(progress) + '%';
        }, 500);
        
        // Complete when page loads result
        window.addEventListener('load', () => {
            setTimeout(() => {
                clearInterval(interval);
                progressBar.style.width = '100%';
                progressBar.textContent = '100%';
            }, 1000);
        });
        </script>
        
        <?php
        $start_time = microtime(true);
        $result = $digiflazz->updateProductsToDatabase($db);
        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 2);
        
        if ($result[0]) {
            echo '<div class="success">✅ ' . $result[1] . '</div>';
            echo '<div class="info">⏱️ Waktu eksekusi: ' . $execution_time . ' detik</div>';
        } else {
            echo '<div class="error">❌ Gagal update produk: ' . $result[1] . '</div>';
        }
        ?>
    <?php else: ?>
        <div class="info">
            <h3>ℹ️ Informasi Update Produk</h3>
            <ul>
                <li>Proses ini akan mengambil semua produk terbaru dari Digiflazz</li>
                <li>Semua produk lama akan dihapus dan diganti dengan yang baru</li>
                <li>Pastikan koneksi internet stabil</li>
                <li>Proses dapat memakan waktu 1-5 menit tergantung jumlah produk</li>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Current Statistics -->
    <h2>📊 Statistik Produk Saat Ini</h2>
    <?php
    try {
        $pdo = $db->getConnection();
        
        // Get product statistics
        $stats = [];
        
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $stats['total'] = $stmt->fetch()['count'];
        
        // Products by category
        $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM products GROUP BY type ORDER BY count DESC");
        $categories = $stmt->fetchAll();
        
        // Products by brand (top 10)
        $stmt = $pdo->query("SELECT brand, COUNT(*) as count FROM products GROUP BY brand ORDER BY count DESC LIMIT 10");
        $brands = $stmt->fetchAll();
        
        // Price range
        $stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price FROM products");
        $price_stats = $stmt->fetch();
        
        // Last update
        $stmt = $pdo->query("SELECT MAX(updated_at) as last_update FROM products");
        $last_update = $stmt->fetch()['last_update'];
        
        echo '<div class="stats">';
        echo '<div class="stat-card"><div class="stat-number">' . number_format($stats['total']) . '</div><div>Total Produk</div></div>';
        echo '<div class="stat-card"><div class="stat-number">' . count($categories) . '</div><div>Kategori</div></div>';
        echo '<div class="stat-card"><div class="stat-number">Rp ' . number_format($price_stats['min_price']) . '</div><div>Harga Minimum</div></div>';
        echo '<div class="stat-card"><div class="stat-number">Rp ' . number_format($price_stats['max_price']) . '</div><div>Harga Maximum</div></div>';
        echo '</div>';
        
        if ($last_update) {
            echo '<div class="info">📅 Last Update: ' . $last_update . '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">❌ Error getting statistics: ' . $e->getMessage() . '</div>';
    }
    ?>
    
    <!-- Categories Table -->
    <?php if (!empty($categories)): ?>
    <h3>📱 Produk per Kategori</h3>
    <table>
        <tr><th>Kategori</th><th>Jumlah Produk</th><th>Persentase</th></tr>
        <?php foreach ($categories as $cat): ?>
        <tr>
            <td><?= htmlspecialchars(ucfirst($cat['type'])) ?></td>
            <td><?= number_format($cat['count']) ?></td>
            <td><?= round(($cat['count'] / $stats['total']) * 100, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    
    <!-- Top Brands Table -->
    <?php if (!empty($brands)): ?>
    <h3>🏪 Top 10 Brand</h3>
    <table>
        <tr><th>Brand</th><th>Jumlah Produk</th><th>Persentase</th></tr>
        <?php foreach ($brands as $brand): ?>
        <tr>
            <td><?= htmlspecialchars($brand['brand']) ?></td>
            <td><?= number_format($brand['count']) ?></td>
            <td><?= round(($brand['count'] / $stats['total']) * 100, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    
    <!-- Action Buttons -->
    <?php if (!isset($_GET['action'])): ?>
    <p>
        <button onclick="if(confirm('Yakin ingin update produk? Semua produk lama akan dihapus.')) { location.href='?action=update'; }">
            🔄 Update Produk Sekarang
        </button>
        <button onclick="location.reload()">📊 Refresh Statistik</button>
    </p>
    <?php endif; ?>
    
    <!-- Cron Job Setup -->
    <h2>📝 Setup Cron Job (Opsional)</h2>
    <div class="info">
        <p>Untuk update otomatis, tambahkan cron job di cPanel:</p>
        <code>0 6 * * * /usr/local/bin/php <?= __DIR__ ?>/update_products.php?key=<?= md5(BOT_TOKEN . 'update_products') ?> >/dev/null 2>&1</code>
        <p><small>Ini akan menjalankan update setiap hari jam 6 pagi</small></p>
        
        <h4>Alternative cron URLs:</h4>
        <code>0 6 * * * curl -s "<?= $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) ?>/update_products.php?action=update&key=<?= md5(BOT_TOKEN . 'update_products') ?>" >/dev/null 2>&1</code>
    </div>
    
    <!-- API Test -->
    <h2>🔧 Test Digiflazz API</h2>
    <?php if (isset($_GET['test_api'])): ?>
        <div class="loading">⏳ Testing Digiflazz API...</div>
        <?php
        $test_result = $digiflazz->getPriceList();
        if ($test_result[0]) {
            $products = $test_result[1];
            echo '<div class="success">✅ API Digiflazz berfungsi dengan baik!</div>';
            echo '<div class="info">📊 Total produk tersedia: ' . count($products) . '</div>';
            
            // Show sample products
            echo '<h4>Sample Produk (10 pertama):</h4>';
            echo '<table>';
            echo '<tr><th>Nama Produk</th><th>Harga</th><th>Brand</th><th>Kategori</th></tr>';
            for ($i = 0; $i < min(10, count($products)); $i++) {
                $product = $products[$i];
                echo '<tr>';
                echo '<td>' . htmlspecialchars($product['product_name']) . '</td>';
                echo '<td>Rp ' . number_format($product['price']) . '</td>';
                echo '<td>' . htmlspecialchars($product['brand']) . '</td>';
                echo '<td>' . htmlspecialchars($product['category']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<div class="error">❌ API Digiflazz gagal: ' . $test_result[1] . '</div>';
            echo '<div class="warning">⚠️ Periksa username dan API key Digiflazz di config.php</div>';
        }
        ?>
    <?php else: ?>
        <p>
            <button onclick="location.href='?test_api=1'">🔍 Test API Digiflazz</button>
        </p>
    <?php endif; ?>
</body>
</html>