<?php
/**
 * Mobile Confirmation Page - Android Style
 * Halaman konfirmasi transaksi dengan timer countdown
 */

require_once 'config.php';
session_start();

$trx_id = $_GET['trx_id'] ?? '';

// Database connection
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get transaction details
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE transaction_id = ?");
    $stmt->execute([$trx_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $transaction = null;
}

if (!$transaction) {
    header("Location: mobile_interface.php");
    exit;
}

// Handle payment confirmation
if ($_POST && isset($_POST['confirm_payment'])) {
    try {
        // Update transaction status to confirmed
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'confirmed', confirmed_at = CURRENT_TIMESTAMP WHERE transaction_id = ?");
        $stmt->execute([$trx_id]);
        
        header("Location: mobile_success.php?trx_id=$trx_id");
        exit;
        
    } catch (PDOException $e) {
        $error_message = "Gagal memproses pembayaran. Silakan coba lagi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Konfirmasi Pembayaran - <?php echo htmlspecialchars($transaction['transaction_id']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
            height: 100vh;
            overflow-x: hidden;
        }
        
        /* App Container */
        .app-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Status Bar */
        .status-bar {
            height: 24px;
            background: rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px;
            font-size: 12px;
            color: white;
            font-weight: 500;
        }
        
        /* App Header */
        .app-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .back-btn {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .header-title {
            flex: 1;
            color: white;
        }
        
        .header-title h1 {
            font-size: 18px;
            font-weight: 500;
        }
        
        .header-title p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Timer Badge */
        .timer-badge {
            background: rgba(255, 107, 107, 0.9);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            background: white;
            border-radius: 24px 24px 0 0;
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Transaction Info */
        .transaction-section {
            padding: 24px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .transaction-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .success-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 4px 20px rgba(0, 184, 148, 0.3);
        }
        
        .transaction-id {
            font-size: 14px;
            color: #666;
            margin-bottom: 4px;
        }
        
        .transaction-status {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .transaction-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 16px;
            border-left: 4px solid #00b894;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-size: 14px;
            color: #666;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            text-align: right;
        }
        
        .detail-amount {
            font-size: 18px;
            font-weight: 700;
            color: #00b894;
        }
        
        /* Payment Instructions */
        .payment-section {
            flex: 1;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
        }
        
        .payment-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .payment-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 24px;
            line-height: 1.4;
        }
        
        .payment-steps {
            background: #fff;
            border: 1px solid #f0f0f0;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .payment-step {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .payment-step:last-child {
            border-bottom: none;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 2px;
        }
        
        .step-desc {
            font-size: 12px;
            color: #666;
            line-height: 1.3;
        }
        
        /* Warning Section */
        .warning-section {
            background: #fff3cd;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            border-left: 4px solid #ffc107;
        }
        
        .warning-title {
            font-size: 14px;
            font-weight: 600;
            color: #856404;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .warning-text {
            font-size: 12px;
            color: #856404;
            line-height: 1.4;
        }
        
        /* Action Buttons */
        .action-section {
            padding: 20px;
            background: white;
            border-top: 1px solid #f0f0f0;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
        }
        
        .btn-secondary {
            flex: 1;
            background: #f8f9fa;
            color: #666;
            border: 1px solid #e9ecef;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-secondary:active {
            transform: scale(0.98);
            background: #e9ecef;
        }
        
        .btn-primary {
            flex: 2;
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:active {
            transform: scale(0.98);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:active::before {
            left: 100%;
        }
        
        /* Countdown Timer */
        .countdown-display {
            text-align: center;
            background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid #667eea20;
        }
        
        .countdown-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .countdown-time {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 4px;
            font-family: 'Roboto Mono', monospace;
        }
        
        .countdown-desc {
            font-size: 12px;
            color: #999;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .loading-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            text-align: center;
            max-width: 280px;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Status Bar -->
        <div class="status-bar">
            <span id="currentTime"></span>
            <span>🔋 100% 📶</span>
        </div>
        
        <!-- App Header -->
        <div class="app-header">
            <button class="back-btn" onclick="goBack()">
                <span class="material-icons" style="font-size: 20px;">arrow_back</span>
            </button>
            <div class="header-title">
                <h1>Konfirmasi Pembayaran</h1>
                <p>Proses pembayaran Anda</p>
            </div>
            <div class="timer-badge">
                <span class="material-icons" style="font-size: 14px;">timer</span>
                <span id="timerDisplay">14:59</span>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Transaction Info -->
            <div class="transaction-section">
                <div class="transaction-header">
                    <div class="success-icon">
                        <span class="material-icons" style="font-size: 32px; color: white;">check_circle</span>
                    </div>
                    <div class="transaction-id">ID Transaksi</div>
                    <div class="transaction-status"><?php echo htmlspecialchars($transaction['transaction_id']); ?></div>
                </div>
                
                <div class="transaction-details">
                    <div class="detail-row">
                        <span class="detail-label">Produk</span>
                        <span class="detail-value"><?php echo htmlspecialchars($transaction['product_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Nomor Tujuan</span>
                        <span class="detail-value"><?php echo htmlspecialchars($transaction['target_number']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value" style="color: #ffa726;">Menunggu Pembayaran</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Bayar</span>
                        <span class="detail-value detail-amount">Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Instructions -->
            <div class="payment-section">
                <div class="payment-title">Cara Pembayaran</div>
                <div class="payment-subtitle">
                    Ikuti langkah-langkah berikut untuk menyelesaikan pembayaran Anda
                </div>
                
                <div class="countdown-display">
                    <div class="countdown-label">Batas Waktu Pembayaran</div>
                    <div class="countdown-time" id="countdownTimer">14:59</div>
                    <div class="countdown-desc">Transaksi akan dibatalkan otomatis</div>
                </div>
                
                <div class="payment-steps">
                    <div class="payment-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <div class="step-title">Transfer ke Rekening</div>
                            <div class="step-desc">BCA: 1234567890 a.n Bot Digital</div>
                        </div>
                    </div>
                    <div class="payment-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <div class="step-title">Nominal Transfer</div>
                            <div class="step-desc">Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?> (transfer sesuai nominal)</div>
                        </div>
                    </div>
                    <div class="payment-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <div class="step-title">Konfirmasi Pembayaran</div>
                            <div class="step-desc">Klik tombol "Sudah Bayar" setelah transfer</div>
                        </div>
                    </div>
                </div>
                
                <div class="warning-section">
                    <div class="warning-title">
                        <span class="material-icons" style="font-size: 16px;">warning</span>
                        Penting!
                    </div>
                    <div class="warning-text">
                        • Transfer sesuai nominal exact<br>
                        • Jangan lupa konfirmasi pembayaran<br>
                        • Simpan bukti transfer untuk referensi
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-section">
                <div class="action-buttons">
                    <button class="btn-secondary" onclick="cancelTransaction()">
                        Batal
                    </button>
                    <form method="POST" style="flex: 2;">
                        <button type="submit" name="confirm_payment" class="btn-primary" onclick="showLoading()">
                            <span class="material-icons" style="font-size: 16px; margin-right: 8px;">payment</span>
                            Sudah Bayar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div style="font-weight: 600; color: #333; margin-bottom: 8px;">Memproses Pembayaran</div>
            <div style="font-size: 14px; color: #666;">Mohon tunggu sebentar...</div>
        </div>
    </div>
    
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        // Countdown timer (15 minutes)
        let timeLeft = 15 * 60; // 15 minutes in seconds
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('countdownTimer').textContent = display;
            document.getElementById('timerDisplay').textContent = display;
            
            if (timeLeft <= 0) {
                // Time expired
                alert('Waktu pembayaran habis. Transaksi dibatalkan.');
                window.location.href = 'mobile_interface.php';
                return;
            }
            
            // Change color when time is running low
            if (timeLeft <= 300) { // 5 minutes
                document.getElementById('countdownTimer').style.color = '#ff6b6b';
                document.querySelector('.timer-badge').style.background = 'rgba(255, 107, 107, 0.9)';
            }
            
            timeLeft--;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        
        // Navigation
        function goBack() {
            if (confirm('Yakin ingin kembali? Transaksi akan dibatalkan.')) {
                window.history.back();
            }
        }
        
        function cancelTransaction() {
            if (confirm('Yakin ingin membatalkan transaksi ini?')) {
                window.location.href = 'mobile_interface.php';
            }
        }
        
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        
        // Copy to clipboard functionality
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show toast or alert
                const toast = document.createElement('div');
                toast.textContent = 'Disalin ke clipboard';
                toast.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #333;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 8px;
                    font-size: 14px;
                    z-index: 1001;
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 2000);
            });
        }
        
        // Add copy functionality to detail values
        document.querySelectorAll('.detail-value').forEach(element => {
            element.addEventListener('click', function() {
                copyToClipboard(this.textContent);
            });
        });
        
        // Prevent accidental navigation
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
</body>
</html>