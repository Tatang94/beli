<?php
require_once 'config.php';

// Check admin authentication
if (!isset($_COOKIE['admin_session']) || $_COOKIE['admin_session'] !== 'authenticated') {
    header('Location: admincenter');
    exit;
}

// Get statistics
$stats = [];
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $stmt->fetch()['count'];
    
    // Total transactions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions");
    $stats['total_transactions'] = $stmt->fetch()['count'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'success'");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Pending transactions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'pending'");
    $stats['pending_transactions'] = $stmt->fetch()['count'];
    
    // Top selling products
    $stmt = $pdo->query("
        SELECT p.name, COUNT(t.product_id) as sales_count, SUM(t.amount) as total_amount
        FROM transactions t
        JOIN products p ON t.product_id = p.product_id
        WHERE t.status = 'success'
        GROUP BY t.product_id
        ORDER BY sales_count DESC
        LIMIT 5
    ");
    $stats['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent transactions
    $stmt = $pdo->query("
        SELECT t.*, p.name as product_name, u.first_name
        FROM transactions t
        LEFT JOIN products p ON t.product_id = p.product_id
        LEFT JOIN users u ON t.user_id = u.user_id
        ORDER BY t.date DESC
        LIMIT 10
    ");
    $stats['recent_transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .content {
            padding: 30px;
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
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .status-success { color: #28a745; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-failed { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Statistics & Analytics</h1>
            <p>Monitor kinerja dan analisis bisnis</p>
        </div>
        
        <div class="content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_products']) ?></div>
                    <div class="stat-label">Total Produk</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_transactions']) ?></div>
                    <div class="stat-label">Total Transaksi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp<?= number_format($stats['total_revenue']) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['pending_transactions']) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            
            <?php if ($stats['top_products']): ?>
                <div class="section">
                    <h3>🏆 Top 5 Produk Terlaris</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Jumlah Terjual</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['top_products'] as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= number_format($product['sales_count']) ?></td>
                                    <td>Rp<?= number_format($product['total_amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if ($stats['recent_transactions']): ?>
                <div class="section">
                    <h3>📋 10 Transaksi Terakhir</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Produk</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_transactions'] as $tx): ?>
                                <tr>
                                    <td>#<?= $tx['transaction_id'] ?></td>
                                    <td><?= htmlspecialchars($tx['first_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($tx['product_name'] ?? 'N/A') ?></td>
                                    <td>Rp<?= number_format($tx['amount']) ?></td>
                                    <td class="status-<?= $tx['status'] ?>"><?= ucfirst($tx['status']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($tx['date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="admincenter" class="btn">🔙 Kembali ke Admin Center</a>
            </div>
        </div>
    </div>
</body>
</html>