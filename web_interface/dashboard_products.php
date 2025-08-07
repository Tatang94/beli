<?php
/**
 * Dashboard untuk monitoring auto update products
 */

require_once 'config.php';
require_once 'cron_scheduler.php';

// Get status
$status = getSchedulerStatus();

// Get product count
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get recent products
    $stmt = $pdo->query("SELECT name, price, category, last_updated FROM products ORDER BY last_updated DESC LIMIT 10");
    $recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get products by category
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $total_products = 0;
    $recent_products = [];
    $categories = [];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Auto Update Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card.blue { background: linear-gradient(135deg, #74b9ff, #0984e3); }
        .stat-card.green { background: linear-gradient(135deg, #00b894, #00a085); }
        .stat-card.purple { background: linear-gradient(135deg, #a29bfe, #6c5ce7); }
        .stat-card h3 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .stat-card p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        .section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-active { background: #00b894; }
        .status-warning { background: #fdcb6e; }
        .status-error { background: #e17055; }
        .btn {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            margin: 5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(116, 185, 255, 0.4);
        }
        .btn.danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .category-bar {
            background: #e9ecef;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
            position: relative;
        }
        .category-fill {
            height: 100%;
            background: linear-gradient(90deg, #74b9ff, #0984e3);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .refresh-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .auto-refresh {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Product Auto Update Dashboard</h1>
            <p>Monitoring sistem update otomatis produk dari Digiflazz API</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo number_format($total_products); ?></h3>
                <p>Total Produk Tersedia</p>
            </div>
            <div class="stat-card blue">
                <h3><?php echo $status['minutes_until_next']; ?></h3>
                <p>Menit ke Update Berikutnya</p>
            </div>
            <div class="stat-card green">
                <h3><?php echo count($categories); ?></h3>
                <p>Kategori Produk</p>
            </div>
            <div class="stat-card purple">
                <h3>30</h3>
                <p>Menit Interval Update</p>
            </div>
        </div>
        
        <div class="section">
            <h2>
                <span class="status-indicator <?php echo $status['is_overdue'] ? 'status-error' : 'status-active'; ?>"></span>
                Status Auto Update
            </h2>
            <div class="refresh-info">
                <strong>Last Update:</strong> <?php echo $status['last_update']; ?><br>
                <strong>Next Update:</strong> <?php echo $status['next_update']; ?><br>
                <strong>Status:</strong> 
                <?php if ($status['is_overdue']): ?>
                    <span style="color: #e17055; font-weight: bold;">Overdue - Perlu dicek</span>
                <?php else: ?>
                    <span style="color: #00b894; font-weight: bold;">Berjalan Normal</span>
                <?php endif; ?>
            </div>
            
            <button class="btn" onclick="triggerManualUpdate()">🔄 Trigger Manual Update</button>
            <button class="btn" onclick="location.reload()">📊 Refresh Dashboard</button>
            <button class="btn danger" onclick="showLogs()">📋 Lihat Log</button>
        </div>
        
        <?php if (!empty($categories)): ?>
        <div class="section">
            <h2>📊 Distribusi Produk per Kategori</h2>
            <?php 
            $max_count = max(array_column($categories, 'count'));
            foreach ($categories as $category): 
                $percentage = ($category['count'] / $max_count) * 100;
            ?>
            <div style="margin: 10px 0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span><strong><?php echo ucfirst($category['category']); ?></strong></span>
                    <span><?php echo number_format($category['count']); ?> produk</span>
                </div>
                <div class="category-bar">
                    <div class="category-fill" style="width: <?php echo $percentage; ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($recent_products)): ?>
        <div class="section">
            <h2>🆕 Produk Terbaru</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Kategori</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>Rp <?php echo number_format($product['price']); ?></td>
                        <td>
                            <span style="background: #e3f2fd; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;">
                                <?php echo ucfirst($product['category']); ?>
                            </span>
                        </td>
                        <td><?php echo $product['last_updated'] ?? 'Unknown'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="auto-refresh">
            <p>🔄 Dashboard ini refresh otomatis setiap 60 detik</p>
            <p>Sistem auto update berjalan setiap 30 menit</p>
        </div>
        
        <div id="result" style="margin-top: 20px;"></div>
    </div>
    
    <script>
        function triggerManualUpdate() {
            document.getElementById('result').innerHTML = '<div class="refresh-info">⏳ Memulai update manual...</div>';
            
            fetch('cron_scheduler.php?action=manual')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('result').innerHTML = 
                        '<div class="refresh-info">✅ ' + data.message + ' pada ' + data.timestamp + '</div>';
                    setTimeout(() => location.reload(), 3000);
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = 
                        '<div class="refresh-info" style="background: #ffebee; border-left-color: #f44336;">❌ Error: ' + error + '</div>';
                });
        }
        
        function showLogs() {
            window.open('logs.php', '_blank');
        }
        
        // Auto refresh dashboard setiap 60 detik
        setInterval(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>