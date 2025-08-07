<?php
require_once 'config.php';

// Simple admin authentication
$admin_logged_in = false;
if (isset($_POST['admin_key']) && $_POST['admin_key'] === 'admin123') {
    $admin_logged_in = true;
    setcookie('admin_session', 'authenticated', time() + 3600);
} elseif (isset($_COOKIE['admin_session']) && $_COOKIE['admin_session'] === 'authenticated') {
    $admin_logged_in = true;
}

// Handle logout
if (isset($_GET['logout'])) {
    setcookie('admin_session', '', time() - 3600);
    header('Location: admincenter.php');
    exit;
}

// Get database stats
try {
    $pdo = new PDO("sqlite:../bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if products table exists, if not create it
    $tables_result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='products'")->fetchAll();
    if (empty($tables_result)) {
        // Create products table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_name TEXT NOT NULL,
            buyer_sku_code TEXT UNIQUE NOT NULL,
            buyer_product_status TEXT,
            seller_product_status TEXT,
            unlimited_stock TEXT,
            multi TEXT,
            start_cut_off TEXT,
            end_cut_off TEXT,
            desc TEXT,
            price INTEGER NOT NULL,
            category TEXT,
            brand TEXT,
            type TEXT,
            status TEXT DEFAULT 'active'
        )");
        $products_count = 0;
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $products_count = $stmt->fetch()['count'] ?? 0;
    }
} catch (Exception $e) {
    $products_count = 0;
    error_log("Database error in admincenter: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center - Digital Pulsa Bot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .admin-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .admin-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .admin-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .admin-header p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .admin-content {
            padding: 40px 30px;
        }
        
        .login-form {
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 15px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            outline: none;
        }
        
        .form-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .admin-menu {
            display: grid;
            gap: 20px;
        }
        
        .menu-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .menu-icon {
            font-size: 32px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .menu-text {
            flex: 1;
        }
        
        .menu-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        
        .menu-desc {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .back-home, .logout-btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px;
            transition: all 0.3s ease;
        }
        
        .back-home {
            background: linear-gradient(135deg, #00c9ff 0%, #92fe9d 100%);
            color: white;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
        }
        
        .back-home:hover, .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .menu-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>⚡ Admin Command Center</h1>
            <p>Digital Pulsa Bot Management Panel</p>
        </div>
        
        <div class="admin-content">
            <?php if (!$admin_logged_in): ?>
                <!-- Login Form -->
                <form method="post" class="login-form">
                    <div class="form-group">
                        <label class="form-label">🔐 Password Admin</label>
                        <input type="password" name="admin_key" class="form-input" placeholder="Masukkan password admin..." required>
                    </div>
                    <button type="submit" class="btn">🚀 Masuk ke Command Center</button>
                </form>
                
                <div style="text-align: center; margin-top: 25px; font-size: 12px; color: #666;">
                    🛡️ Area terbatas hanya untuk administrator sistem
                </div>
            <?php else: ?>
                <!-- Admin Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($products_count) ?></div>
                        <div class="stat-label">Produk Aktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Status Online</div>
                    </div>
                </div>
                
                <div class="admin-menu" id="adminMenu">
                    <div class="menu-item" onclick="loadContent('products')">
                        <div class="menu-icon">📋</div>
                        <div class="menu-text">
                            <div class="menu-title">Ambil List Produk</div>
                            <div class="menu-desc">Lihat dan kelola daftar lengkap produk yang tersedia</div>
                        </div>
                    </div>
                    
                    <div class="menu-item" onclick="loadContent('margin')">
                        <div class="menu-icon">💰</div>
                        <div class="menu-text">
                            <div class="menu-title">Atur Margin</div>
                            <div class="menu-desc">Kelola margin keuntungan untuk setiap kategori produk</div>
                        </div>
                    </div>
                    
                    <div class="menu-item" onclick="loadContent('statistics')">
                        <div class="menu-icon">📊</div>
                        <div class="menu-text">
                            <div class="menu-title">Lihat Statistics</div>
                            <div class="menu-desc">Monitor transaksi, pendapatan, dan analisis penjualan</div>
                        </div>
                    </div>
                    
                    <div class="menu-item" onclick="loadContent('buyers')">
                        <div class="menu-icon">👥</div>
                        <div class="menu-text">
                            <div class="menu-title">Jumlah Pembeli</div>
                            <div class="menu-desc">Data pembeli dan aktivitas transaksi pengguna</div>
                        </div>
                    </div>
                </div>
                
                <!-- Content area untuk konten dinamis -->
                <div id="dynamicContent" style="display: none; margin-top: 30px; padding: 20px; background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 id="contentTitle">Loading...</h3>
                        <button onclick="backToMenu()" style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">← Kembali</button>
                    </div>
                    <div id="contentBody">Loading...</div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="index.php" class="back-home">🏠 Kembali ke Home</a>
                    <a href="?logout=1" class="logout-btn">🚪 Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function loadContent(type) {
            document.getElementById('adminMenu').style.display = 'none';
            document.getElementById('dynamicContent').style.display = 'block';
            
            const contentTitle = document.getElementById('contentTitle');
            const contentBody = document.getElementById('contentBody');
            
            contentBody.innerHTML = '<div style="text-align: center; padding: 20px;">Loading...</div>';
            
            switch(type) {
                case 'products':
                    contentTitle.textContent = '📋 Kelola Produk';
                    loadProducts();
                    break;
                case 'margin':
                    contentTitle.textContent = '💰 Atur Margin';
                    loadMargin();
                    break;
                case 'statistics':
                    contentTitle.textContent = '📊 Statistik';
                    loadStatistics();
                    break;
                case 'buyers':
                    contentTitle.textContent = '👥 Data Pembeli';
                    loadBuyers();
                    break;
            }
        }
        
        function backToMenu() {
            document.getElementById('adminMenu').style.display = 'grid';
            document.getElementById('dynamicContent').style.display = 'none';
        }
        
        function loadProducts() {
            fetch('update_products.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('contentBody').innerHTML = `
                        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <h4>📦 Update Produk dari API Digiflazz</h4>
                            <p>Klik tombol di bawah untuk mengambil produk terbaru dari API</p>
                            <button onclick="updateProducts()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-top: 10px;">
                                🔄 Update Produk Sekarang
                            </button>
                        </div>
                        <div id="updateResult"></div>
                        <iframe src="products.php" style="width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 8px;"></iframe>
                    `;
                });
        }
        
        function loadMargin() {
            document.getElementById('contentBody').innerHTML = `
                <iframe src="margin.php" style="width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 8px;"></iframe>
            `;
        }
        
        function loadStatistics() {
            document.getElementById('contentBody').innerHTML = `
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <h4>📊 Statistik Sistem</h4>
                    <div style="margin: 20px 0;">
                        <div style="display: inline-block; margin: 10px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <div style="font-size: 24px; font-weight: bold; color: #28a745;"><?= number_format($products_count) ?></div>
                            <div style="font-size: 12px; color: #666;">Total Produk</div>
                        </div>
                        <div style="display: inline-block; margin: 10px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <div style="font-size: 24px; font-weight: bold; color: #007bff;">24/7</div>
                            <div style="font-size: 12px; color: #666;">Status Online</div>
                        </div>
                    </div>
                    <p style="color: #666; margin-top: 20px;">Sistem berjalan normal dan siap melayani transaksi</p>
                </div>
            `;
        }
        
        function loadBuyers() {
            document.getElementById('contentBody').innerHTML = `
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <h4>👥 Data Pembeli</h4>
                    <div style="margin: 20px 0;">
                        <div style="display: inline-block; margin: 10px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <div style="font-size: 24px; font-weight: bold; color: #ff6b6b;">0</div>
                            <div style="font-size: 12px; color: #666;">Pembeli Hari Ini</div>
                        </div>
                        <div style="display: inline-block; margin: 10px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <div style="font-size: 24px; font-weight: bold; color: #17a2b8;">0</div>
                            <div style="font-size: 12px; color: #666;">Total Transaksi</div>
                        </div>
                    </div>
                    <p style="color: #666; margin-top: 20px;">Belum ada transaksi tercatat</p>
                </div>
            `;
        }
        
        function updateProducts() {
            document.getElementById('updateResult').innerHTML = '<div style="padding: 10px; background: #fff3cd; border-radius: 5px;">Sedang mengupdate produk...</div>';
            
            fetch('update_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'update_products=1'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('updateResult').innerHTML = '<div style="padding: 10px; background: #d4edda; border-radius: 5px;">Produk berhasil diupdate!</div>';
                setTimeout(() => {
                    location.reload();
                }, 2000);
            })
            .catch(error => {
                document.getElementById('updateResult').innerHTML = '<div style="padding: 10px; background: #f8d7da; border-radius: 5px;">Error: ' + error + '</div>';
            });
        }
    </script>
</body>
</html>