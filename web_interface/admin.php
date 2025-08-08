<?php
require_once 'config.php';

// Simple authentication
session_start();
$admin_password = "admin123"; // Change this!

if (!isset($_SESSION['admin_logged_in'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else if ($_POST['password']) {
        $error = "Password salah!";
    }
}

if (!isset($_SESSION['admin_logged_in'])) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-card h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
            font-size: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        .error {
            color: #e74c3c;
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>🔐 Admin Login</h1>
        <form method="POST">
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Masuk</button>
            <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
<?php
exit;
}

// Get stats
try {
    $db = new PDO("sqlite:bot_database.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Product stats
    $stmt = $db->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_products = array_sum(array_column($categories, 'count'));
    
    // Last update
    $stmt = $db->query("SELECT MAX(updated_at) as last_update FROM products LIMIT 1");
    $last_update = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle manual update
if (isset($_POST['update_products'])) {
    $output = shell_exec('cd ' . __DIR__ . ' && php auto_update_products.php 2>&1');
    $update_message = "Update produk berhasil dijalankan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f8f9fa;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #666;
            font-weight: 500;
        }
        
        .actions {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .actions h2 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s;
        }
        
        .btn-update:hover {
            transform: translateY(-1px);
        }
        
        .categories {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .categories h2 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .category-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        
        .category-name {
            font-weight: 500;
            color: #333;
        }
        
        .category-count {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.875rem;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .last-update {
            color: #666;
            font-size: 0.875rem;
            margin-top: 1rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .container { padding: 0 0.5rem; }
            .header { padding: 1rem; }
            .header h1 { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Admin Dashboard</h1>
        <a href="?logout=1" class="logout-btn">Keluar</a>
    </div>
    
    <div class="container">
        <?php if (isset($update_message)): ?>
        <div class="success"><?= $update_message ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= number_format($total_products) ?></h3>
                <p>Total Produk</p>
            </div>
            <div class="stat-card">
                <h3><?= count($categories) ?></h3>
                <p>Kategori Aktif</p>
            </div>
            <div class="stat-card">
                <h3><?= date('H:i') ?></h3>
                <p>Waktu Server</p>
            </div>
        </div>
        
        <div class="actions">
            <h2>⚙️ Aksi Cepat</h2>
            <form method="POST" style="display: inline;">
                <button type="submit" name="update_products" class="btn-update">
                    🔄 Update Produk Manual
                </button>
            </form>
        </div>
        
        <div class="categories">
            <h2>📋 Distribusi Kategori</h2>
            <div class="category-list">
                <?php foreach ($categories as $cat): ?>
                <div class="category-item">
                    <span class="category-name"><?= htmlspecialchars($cat['category']) ?></span>
                    <span class="category-count"><?= number_format($cat['count']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($last_update): ?>
            <div class="last-update">
                📅 Terakhir diupdate: <?= date('d/m/Y H:i:s', strtotime($last_update)) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>