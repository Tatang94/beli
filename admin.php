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
    header('Location: admin.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Digital Pulsa Bot</title>
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
        }
        
        .admin-content {
            padding: 40px 30px;
        }
        
        .login-form {
            text-align: center;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 15px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            outline: none;
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
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .menu-item {
            display: block;
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 15px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .menu-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .menu-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .menu-desc {
            font-size: 12px;
            color: #666;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>⚡ Admin Panel</h1>
            <p>Digital Pulsa Bot Management</p>
        </div>
        
        <div class="admin-content">
            <?php if (!$admin_logged_in): ?>
                <!-- Login Form -->
                <form method="post" class="login-form">
                    <h3 style="margin-bottom: 25px; color: #333;">🔐 Login Admin</h3>
                    <input type="password" name="admin_key" class="form-input" placeholder="Masukkan password admin..." required>
                    <button type="submit" class="btn">🚀 Masuk ke Panel</button>
                </form>
            <?php else: ?>
                <!-- Admin Dashboard -->
                <h3 style="text-align: center; margin-bottom: 30px; color: #333;">🎛️ Control Panel</h3>
                
                <a href="update_products.php" class="menu-item">
                    <div class="menu-title">🔄 Update Database Produk</div>
                    <div class="menu-desc">Sinkronisasi produk dari Digiflazz API</div>
                </a>
                
                <a href="products.php" class="menu-item">
                    <div class="menu-title">📦 Kelola Produk</div>
                    <div class="menu-desc">Lihat dan atur produk yang tersedia</div>
                </a>
                
                <div style="text-align: center;">
                    <a href="index.php" style="color: #667eea; text-decoration: none; margin-right: 20px;">🏠 Kembali ke Home</a>
                    <a href="?logout=1" class="logout-btn">🚪 Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>