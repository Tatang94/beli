<?php
require_once 'config.php';

// Simple admin authentication (in production, use proper session management)
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

// Get product count for display
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $products_count = $stmt->fetch()['count'] ?? 0;
} catch (Exception $e) {
    $products_count = 0;
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
            max-width: 500px;
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
            font-size: 24px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .admin-header p {
            font-size: 14px;
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
            gap: 15px;
        }
        
        .menu-item {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .menu-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }
        
        .menu-icon {
            font-size: 24px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .menu-text {
            flex: 1;
        }
        
        .menu-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .menu-desc {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #00c9ff 0%, #92fe9d 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 201, 255, 0.3);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 20px;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
        }
        
        .back-home {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
            padding: 12px 25px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .back-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(168, 237, 234, 0.3);
        }
        
        @media (max-width: 480px) {
            .admin-container {
                margin: 10px;
                max-width: none;
            }
            
            .admin-content {
                padding: 30px 20px;
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
                <div class="login-form">
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">🔐 Admin Access Key</label>
                            <input type="password" name="admin_key" class="form-input" placeholder="Masukkan kunci akses admin..." required>
                        </div>
                        <button type="submit" class="btn">
                            🚀 Masuk ke Admin Panel
                        </button>
                    </form>
                    
                    <a href="index.php" class="back-home">
                        🏠 Kembali ke Home
                    </a>
                </div>
            <?php else: ?>
                <!-- Admin Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($products_count) ?></div>
                        <div class="stat-label">Total Produk</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Status Online</div>
                    </div>
                </div>
                
                <div class="admin-menu">
                    <a href="update_products.php" class="menu-item">
                        <div class="menu-icon">🔄</div>
                        <div class="menu-text">
                            <div class="menu-title">Update Produk</div>
                            <div class="menu-desc">Sinkronisasi database produk dari API Digiflazz</div>
                        </div>
                    </a>
                    
                    <a href="admin.php?action=users" class="menu-item">
                        <div class="menu-icon">👥</div>
                        <div class="menu-text">
                            <div class="menu-title">Kelola Pengguna</div>
                            <div class="menu-desc">Lihat dan kelola data pengguna terdaftar</div>
                        </div>
                    </a>
                    
                    <a href="admin.php?action=deposits" class="menu-item">
                        <div class="menu-icon">💰</div>
                        <div class="menu-text">
                            <div class="menu-title">Konfirmasi Deposit</div>
                            <div class="menu-desc">Verifikasi dan approve deposit pengguna</div>
                        </div>
                    </a>
                    
                    <a href="admin.php?action=transactions" class="menu-item">
                        <div class="menu-icon">📊</div>
                        <div class="menu-text">
                            <div class="menu-title">Laporan Transaksi</div>
                            <div class="menu-desc">Monitor dan analisis riwayat transaksi</div>
                        </div>
                    </a>
                    
                    <a href="admin.php?action=settings" class="menu-item">
                        <div class="menu-icon">⚙️</div>
                        <div class="menu-text">
                            <div class="menu-title">Pengaturan Sistem</div>
                            <div class="menu-desc">Konfigurasi bot dan pengaturan margin</div>
                        </div>
                    </a>
                    
                    <a href="admin.php?action=broadcast" class="menu-item">
                        <div class="menu-icon">📢</div>
                        <div class="menu-text">
                            <div class="menu-title">Broadcast Message</div>
                            <div class="menu-desc">Kirim pesan ke semua pengguna bot</div>
                        </div>
                    </a>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="index.php" class="back-home">🏠 Kembali ke Home</a>
                    <a href="?logout=1" class="logout-btn">🚪 Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>