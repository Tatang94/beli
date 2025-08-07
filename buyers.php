<?php
require_once 'config.php';

// Check admin authentication
if (!isset($_COOKIE['admin_session']) || $_COOKIE['admin_session'] !== 'authenticated') {
    header('Location: admincenter');
    exit;
}

// Get buyer statistics
$buyers = [];
$total_buyers = 0;
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Total unique buyers (users who have made transactions)
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM transactions
    ");
    $total_buyers = $stmt->fetch()['count'];
    
    // Buyers with transaction details
    $stmt = $pdo->query("
        SELECT 
            u.user_id,
            u.first_name,
            u.username,
            u.balance,
            u.join_date,
            COUNT(t.transaction_id) as total_transactions,
            SUM(CASE WHEN t.status = 'success' THEN t.amount ELSE 0 END) as total_spent,
            MAX(t.date) as last_transaction
        FROM users u
        LEFT JOIN transactions t ON u.user_id = t.user_id
        GROUP BY u.user_id
        HAVING total_transactions > 0
        ORDER BY total_spent DESC
        LIMIT 50
    ");
    $buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly buyer growth
    $stmt = $pdo->query("
        SELECT 
            strftime('%Y-%m', join_date) as month,
            COUNT(*) as new_buyers
        FROM users
        WHERE user_id IN (SELECT DISTINCT user_id FROM transactions)
        GROUP BY strftime('%Y-%m', join_date)
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthly_growth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pembeli - Admin Panel</title>
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
        
        .stats-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
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
        
        .table tr:hover {
            background: #f8f9fa;
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
        
        .buyer-rank {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .growth-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .growth-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .growth-month {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .growth-count {
            font-size: 18px;
            color: #667eea;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Data Pembeli</h1>
            <p>Informasi lengkap tentang customer dan aktivitas transaksi</p>
        </div>
        
        <div class="content">
            <div class="stats-summary">
                <div class="stat-number"><?= number_format($total_buyers) ?></div>
                <div>Total Pembeli Aktif</div>
            </div>
            
            <?php if ($monthly_growth): ?>
                <div class="section">
                    <h3>📈 Pertumbuhan Pembeli Bulanan</h3>
                    <div class="growth-grid">
                        <?php foreach ($monthly_growth as $growth): ?>
                            <div class="growth-card">
                                <div class="growth-month"><?= date('M Y', strtotime($growth['month'] . '-01')) ?></div>
                                <div class="growth-count">+<?= $growth['new_buyers'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($buyers): ?>
                <div class="section">
                    <h3>🏆 Top 50 Pembeli</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Total Transaksi</th>
                                <th>Total Belanja</th>
                                <th>Saldo</th>
                                <th>Terakhir Transaksi</th>
                                <th>Join Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($buyers as $index => $buyer): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <span class="buyer-rank">#<?= $index + 1 ?></span>
                                        <?php else: ?>
                                            #<?= $index + 1 ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($buyer['first_name'] ?? 'N/A') ?></td>
                                    <td>@<?= htmlspecialchars($buyer['username'] ?? 'N/A') ?></td>
                                    <td><?= number_format($buyer['total_transactions']) ?></td>
                                    <td>Rp<?= number_format($buyer['total_spent']) ?></td>
                                    <td>Rp<?= number_format($buyer['balance']) ?></td>
                                    <td>
                                        <?php if ($buyer['last_transaction']): ?>
                                            <?= date('d/m/Y', strtotime($buyer['last_transaction'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($buyer['join_date'])) ?></td>
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