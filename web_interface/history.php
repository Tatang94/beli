<?php
/**
 * PPOB Indonesia - Transaction History
 * Riwayat transaksi pengguna
 */
session_start();
require_once 'config.php';

// Simulated user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'demo_user';
    $_SESSION['balance'] = 50000;
}

// Sample transaction data (in real app, this would come from database)
$transactions = [
    [
        'id' => 'TRX240809001',
        'type' => 'pulsa',
        'category' => 'Pulsa',
        'product' => 'Telkomsel 25.000',
        'target' => '081234567890',
        'amount' => 26500,
        'status' => 'success',
        'date' => '2025-08-09 08:30:15',
        'ref_id' => 'REF240809001'
    ],
    [
        'id' => 'TRX240809002',
        'type' => 'token_pln',
        'category' => 'PLN',
        'product' => 'Token PLN 50.000',
        'target' => '12345678901',
        'amount' => 51500,
        'status' => 'success',
        'date' => '2025-08-09 07:45:22',
        'ref_id' => 'REF240809002'
    ],
    [
        'id' => 'TRX240809003',
        'type' => 'emoney',
        'category' => 'E-Money',
        'product' => 'GoPay 100.000',
        'target' => '081987654321',
        'amount' => 102000,
        'status' => 'pending',
        'date' => '2025-08-09 07:15:10',
        'ref_id' => 'REF240809003'
    ],
    [
        'id' => 'TRX240808001',
        'type' => 'game',
        'category' => 'Game',
        'product' => 'Mobile Legends 275 Diamond',
        'target' => '123456789',
        'amount' => 75000,
        'status' => 'success',
        'date' => '2025-08-08 20:30:45',
        'ref_id' => 'REF240808001'
    ],
    [
        'id' => 'TRX240808002',
        'type' => 'deposit',
        'category' => 'Deposit',
        'product' => 'Deposit Via Transfer Bank',
        'target' => '-',
        'amount' => 500000,
        'status' => 'success',
        'date' => '2025-08-08 15:20:30',
        'ref_id' => 'DEP240808001'
    ]
];

function getStatusBadge($status) {
    switch ($status) {
        case 'success':
            return '<span class="status-badge success">✅ Berhasil</span>';
        case 'pending':
            return '<span class="status-badge pending">⏳ Pending</span>';
        case 'failed':
            return '<span class="status-badge failed">❌ Gagal</span>';
        default:
            return '<span class="status-badge unknown">❓ Unknown</span>';
    }
}

function getCategoryIcon($category) {
    $icons = [
        'Pulsa' => '📱',
        'Data' => '📶',
        'PLN' => '⚡',
        'Game' => '🎮',
        'E-Money' => '💳',
        'Deposit' => '💰',
        'PDAM' => '💧',
        'BPJS' => '🏥'
    ];
    return $icons[$category] ?? '📦';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - PPOB Indonesia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 1rem;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 12px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .content {
            padding: 2rem;
        }
        
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .transactions-list {
            /* space-y: 1rem; */
        }
        
        .transaction-card {
            border: 1px solid #e1e1e1;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s;
        }
        
        .transaction-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .transaction-main {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .transaction-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .transaction-info h4 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            color: #333;
        }
        
        .transaction-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .transaction-amount {
            text-align: right;
        }
        
        .amount {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .amount.deposit {
            color: #28a745;
        }
        
        .amount.purchase {
            color: #dc3545;
        }
        
        .transaction-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
        }
        
        .detail-label {
            color: #666;
        }
        
        .detail-value {
            color: #333;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .transaction-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .transaction-main {
                width: 100%;
            }
            
            .transaction-amount {
                text-align: left;
            }
            
            .filters {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Riwayat Transaksi</h1>
            <p>Kelola dan pantau semua aktivitas transaksi Anda</p>
            
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value"><?= count($transactions) ?></div>
                    <div class="stat-label">Total Transaksi</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= count(array_filter($transactions, fn($t) => $t['status'] === 'success')) ?></div>
                    <div class="stat-label">Berhasil</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= count(array_filter($transactions, fn($t) => $t['status'] === 'pending')) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">Rp <?= number_format(array_sum(array_column(array_filter($transactions, fn($t) => $t['type'] !== 'deposit'), 'amount'))) ?></div>
                    <div class="stat-label">Total Pengeluaran</div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <a href="ppob.php" class="back-link">← Kembali ke Beranda</a>
            
            <div class="filters">
                <a href="#" class="filter-btn active">Semua</a>
                <a href="#" class="filter-btn">Hari Ini</a>
                <a href="#" class="filter-btn">Minggu Ini</a>
                <a href="#" class="filter-btn">Bulan Ini</a>
                <a href="#" class="filter-btn">Berhasil</a>
                <a href="#" class="filter-btn">Pending</a>
            </div>
            
            <div class="transactions-list">
                <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <h3>Belum Ada Transaksi</h3>
                    <p>Mulai bertransaksi untuk melihat riwayat di sini</p>
                </div>
                <?php else: ?>
                    <?php foreach ($transactions as $trx): ?>
                    <div class="transaction-card">
                        <div class="transaction-header">
                            <div class="transaction-main">
                                <div class="transaction-icon">
                                    <?= getCategoryIcon($trx['category']) ?>
                                </div>
                                <div class="transaction-info">
                                    <h4><?= htmlspecialchars($trx['product']) ?></h4>
                                    <p><?= $trx['target'] !== '-' ? 'Tujuan: ' . htmlspecialchars($trx['target']) : 'Sistem Internal' ?></p>
                                </div>
                            </div>
                            <div class="transaction-amount">
                                <div class="amount <?= $trx['type'] === 'deposit' ? 'deposit' : 'purchase' ?>">
                                    <?= $trx['type'] === 'deposit' ? '+' : '-' ?>Rp <?= number_format($trx['amount']) ?>
                                </div>
                                <?= getStatusBadge($trx['status']) ?>
                            </div>
                        </div>
                        
                        <div class="transaction-details">
                            <div class="detail-item">
                                <span class="detail-label">ID Transaksi:</span>
                                <span class="detail-value"><?= htmlspecialchars($trx['id']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Ref ID:</span>
                                <span class="detail-value"><?= htmlspecialchars($trx['ref_id']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Kategori:</span>
                                <span class="detail-value"><?= htmlspecialchars($trx['category']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tanggal:</span>
                                <span class="detail-value"><?= date('d/m/Y H:i', strtotime($trx['date'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>