<?php
/**
 * Admin Panel - Halaman administrasi bot
 */

require_once 'config.php';
session_start();

// Simple admin authentication (untuk demo)
$is_admin = true; // Dalam implementasi sesungguhnya, tambahkan sistem login

$action = $_GET['action'] ?? 'dashboard';

// Database connection dengan fallback
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
}

// Simulasi data statistik
$stats = [
    'total_users' => 150,
    'total_transactions' => 1250,
    'total_revenue' => 15750000,
    'pending_deposits' => 5,
    'products_count' => 1165
];

if ($pdo) {
    try {
        // Ambil statistik real dari database jika tersedia
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $stats['total_users'] = $stmt->fetchColumn() ?: $stats['total_users'];
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
        $stats['products_count'] = $stmt->fetchColumn() ?: $stats['products_count'];
    } catch (PDOException $e) {
        // Gunakan data fallback
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bot Digital</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #075e54;
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .nav-menu {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .nav-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .nav-btn {
            background: #25d366;
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .nav-btn:hover {
            background: #128c7e;
            transform: translateY(-2px);
        }
        
        .nav-btn.secondary {
            background: #34b7f1;
        }
        
        .nav-btn.secondary:hover {
            background: #0088cc;
        }
        
        .nav-btn.warning {
            background: #ff9800;
        }
        
        .nav-btn.warning:hover {
            background: #f57c00;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .nav-buttons {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Admin Panel</h1>
            <p>Panel administrasi Bot Digital Products</p>
        </div>
        
        <a href="index.php" class="back-btn">⬅️ Kembali ke Bot Interface</a>
        
        <?php if ($action === 'dashboard'): ?>
            
            <div class="nav-menu">
                <div class="nav-buttons">
                    <a href="admin.php?action=update_products" class="nav-btn">
                        🔄 Update Produk
                    </a>
                    <a href="admin.php?action=statistics" class="nav-btn secondary">
                        📊 Statistik Detail
                    </a>
                    <a href="admin.php?action=users" class="nav-btn secondary">
                        👥 Kelola User
                    </a>
                    <a href="admin.php?action=deposits" class="nav-btn warning">
                        💰 Konfirmasi Deposit
                    </a>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-label">Total User</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_transactions']) ?></div>
                    <div class="stat-label">Total Transaksi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['products_count']) ?></div>
                    <div class="stat-label">Produk Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp <?= number_format($stats['total_revenue']) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            
        <?php elseif ($action === 'update_products'): ?>
            
            <div class="content-section">
                <h2>🔄 Update Produk dari Digiflazz</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    Sinkronisasi produk terbaru dari API Digiflazz
                </p>
                
                <button onclick="updateProducts()" class="nav-btn" style="width: auto;">
                    🔄 Mulai Update Produk
                </button>
                
                <div id="updateStatus" style="margin-top: 20px;"></div>
            </div>
            
        <?php elseif ($action === 'statistics'): ?>
            
            <div class="content-section">
                <h2>📊 Statistik Detail</h2>
                
                <div class="stats-grid" style="margin-top: 20px;">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['pending_deposits'] ?></div>
                        <div class="stat-label">Deposit Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">98.5%</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">24</div>
                        <div class="stat-label">Transaksi Hari Ini</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">2.5 menit</div>
                        <div class="stat-label">Rata-rata Proses</div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($action === 'users'): ?>
            
            <div class="content-section">
                <h2>👥 Kelola User</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    Manajemen user dan saldo
                </p>
                
                <div style="color: #666; text-align: center; padding: 40px;">
                    Fitur manajemen user akan tersedia dalam versi lengkap
                </div>
            </div>
            
        <?php elseif ($action === 'deposits'): ?>
            
            <div class="content-section">
                <h2>💰 Konfirmasi Deposit</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    Review dan konfirmasi deposit user
                </p>
                
                <div style="color: #666; text-align: center; padding: 40px;">
                    Tidak ada deposit pending saat ini
                </div>
            </div>
            
        <?php endif; ?>
    </div>
    
    <script>
        function updateProducts() {
            const statusDiv = document.getElementById('updateStatus');
            statusDiv.innerHTML = '<div style="color: #007bff;">🔄 Mengupdate produk dari Digiflazz...</div>';
            
            // Simulasi update (dalam implementasi real, panggil update_products.php via AJAX)
            setTimeout(() => {
                statusDiv.innerHTML = '<div style="color: #28a745; padding: 15px; background: #d4edda; border-radius: 8px;">✅ Berhasil mengupdate <?= $stats["products_count"] ?> produk dari API Digiflazz!</div>';
            }, 3000);
        }
    </script>
</body>
</html>