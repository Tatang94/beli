<?php
/**
 * PPOB Indonesia - Deposit System
 * Sistem deposit untuk PPOB
 */
session_start();
require_once 'config.php';

// Simulated user session for demo
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'demo_user';
    $_SESSION['balance'] = 50000; // Demo balance
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'form';
$user_balance = $_SESSION['balance'] ?? 0;

// Handle deposit
if ($action === 'process') {
    $amount = (int)($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? '';
    
    if ($amount >= 10000 && $amount <= 10000000) {
        // Generate unique transaction ID
        $trx_id = 'DEP' . date('YmdHis') . rand(100, 999);
        
        // In real implementation, this would integrate with payment gateway
        // For demo, we'll simulate a successful deposit
        $_SESSION['balance'] += $amount;
        
        $success = true;
        $message = "Deposit berhasil! Saldo Anda bertambah Rp " . number_format($amount);
    } else {
        $success = false;
        $message = "Jumlah deposit harus antara Rp 10.000 - Rp 10.000.000";
    }
}

// Deposit methods
$deposit_methods = [
    'bank_transfer' => [
        'name' => 'Transfer Bank',
        'icon' => '🏦',
        'desc' => 'BCA, Mandiri, BRI, BNI',
        'fee' => 'Gratis',
        'processing' => 'Otomatis'
    ],
    'va_bca' => [
        'name' => 'Virtual Account BCA',
        'icon' => '💳',
        'desc' => 'Virtual Account BCA',
        'fee' => 'Rp 4.000',
        'processing' => 'Real-time'
    ],
    'va_mandiri' => [
        'name' => 'Virtual Account Mandiri',
        'icon' => '💳',
        'desc' => 'Virtual Account Mandiri',
        'fee' => 'Rp 4.000',
        'processing' => 'Real-time'
    ],
    'qris' => [
        'name' => 'QRIS',
        'icon' => '📱',
        'desc' => 'Scan QR Code',
        'fee' => 'Rp 1.000',
        'processing' => 'Real-time'
    ],
    'ewallet' => [
        'name' => 'E-Wallet',
        'icon' => '💰',
        'desc' => 'GoPay, OVO, DANA',
        'fee' => '0.7%',
        'processing' => 'Real-time'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Saldo - PPOB Indonesia</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        
        .container {
            max-width: 600px;
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
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .balance-info {
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1rem;
        }
        
        .balance-info h3 {
            font-size: 1.8rem;
            margin-bottom: 0.25rem;
        }
        
        .content {
            padding: 2rem;
        }
        
        <?php if (isset($success)): ?>
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            <?= $success ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' ?>
        }
        <?php endif; ?>
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .amount-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .amount-btn {
            padding: 0.5rem;
            background: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .amount-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .deposit-methods {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .method-card {
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .method-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .method-card.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .method-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .method-icon {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 50%;
        }
        
        .method-card.selected .method-icon {
            background: rgba(255,255,255,0.2);
        }
        
        .method-info h4 {
            margin-bottom: 0.25rem;
        }
        
        .method-info p {
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        .method-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Deposit Saldo</h1>
            <div class="balance-info">
                <p>Saldo Saat Ini</p>
                <h3>Rp <?= number_format($user_balance) ?></h3>
            </div>
        </div>
        
        <div class="content">
            <?php if (isset($success)): ?>
            <div class="alert">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="process">
                
                <div class="form-group">
                    <label for="amount">Jumlah Deposit</label>
                    <input type="number" id="amount" name="amount" min="10000" max="10000000" 
                           placeholder="Minimal Rp 10.000" required>
                    
                    <div class="amount-buttons">
                        <div class="amount-btn" onclick="setAmount(50000)">Rp 50K</div>
                        <div class="amount-btn" onclick="setAmount(100000)">Rp 100K</div>
                        <div class="amount-btn" onclick="setAmount(250000)">Rp 250K</div>
                        <div class="amount-btn" onclick="setAmount(500000)">Rp 500K</div>
                        <div class="amount-btn" onclick="setAmount(1000000)">Rp 1M</div>
                        <div class="amount-btn" onclick="setAmount(5000000)">Rp 5M</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Pilih Metode Pembayaran</label>
                    <div class="deposit-methods">
                        <?php foreach ($deposit_methods as $key => $method): ?>
                        <div class="method-card" onclick="selectMethod('<?= $key ?>')">
                            <input type="radio" name="method" value="<?= $key ?>" style="display: none;" required>
                            <div class="method-header">
                                <div class="method-icon"><?= $method['icon'] ?></div>
                                <div class="method-info">
                                    <h4><?= $method['name'] ?></h4>
                                    <p><?= $method['desc'] ?></p>
                                </div>
                            </div>
                            <div class="method-details">
                                <span>Fee: <?= $method['fee'] ?></span>
                                <span>Proses: <?= $method['processing'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    💳 Proses Deposit
                </button>
            </form>
            
            <a href="ppob.php" class="back-link">← Kembali ke Beranda</a>
        </div>
    </div>
    
    <script>
        function setAmount(amount) {
            document.getElementById('amount').value = amount;
        }
        
        function selectMethod(methodKey) {
            // Remove selected class from all cards
            document.querySelectorAll('.method-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            event.currentTarget.querySelector('input[type="radio"]').checked = true;
        }
    </script>
</body>
</html>