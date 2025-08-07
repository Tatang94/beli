<?php
/**
 * Mobile Success Page - Android Style
 * Halaman sukses transaksi dengan animasi
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
    
    if ($transaction) {
        // Update status to processing/success
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'success' WHERE transaction_id = ?");
        $stmt->execute([$trx_id]);
    }
    
} catch (PDOException $e) {
    $transaction = null;
}

if (!$transaction) {
    header("Location: mobile_interface.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Transaksi Berhasil - Bot Pulsa Digital</title>
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
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
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
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .close-btn {
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
            text-align: center;
        }
        
        .header-title h1 {
            font-size: 18px;
            font-weight: 500;
        }
        
        .share-btn {
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
        
        /* Success Animation */
        .success-section {
            padding: 40px 20px;
            text-align: center;
            background: linear-gradient(135deg, rgba(0, 184, 148, 0.05) 0%, rgba(0, 206, 201, 0.05) 100%);
        }
        
        .success-animation {
            width: 120px;
            height: 120px;
            margin: 0 auto 24px;
            position: relative;
        }
        
        .success-circle {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: successPulse 2s ease-in-out infinite;
            box-shadow: 0 8px 30px rgba(0, 184, 148, 0.3);
            position: relative;
        }
        
        .success-circle::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border: 2px solid rgba(0, 184, 148, 0.3);
            border-radius: 50%;
            animation: successRipple 2s ease-in-out infinite;
        }
        
        .success-icon {
            font-size: 48px;
            color: white;
            animation: successCheck 1s ease-in-out;
        }
        
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes successRipple {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.2); opacity: 0; }
        }
        
        @keyframes successCheck {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .success-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .success-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 24px;
            line-height: 1.4;
        }
        
        .transaction-id-display {
            background: rgba(0, 184, 148, 0.1);
            color: #00b894;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            border: 1px solid rgba(0, 184, 148, 0.2);
        }
        
        /* Transaction Details */
        .details-section {
            padding: 24px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .details-card {
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 16px;
            overflow: hidden;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
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
        
        /* Action Section */
        .action-section {
            flex: 1;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .action-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .action-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 32px;
            text-align: center;
            line-height: 1.4;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 20px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 184, 148, 0.3);
        }
        
        .action-btn:active {
            transform: scale(0.98);
        }
        
        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .action-btn:active::before {
            left: 100%;
        }
        
        /* Bottom Section */
        .bottom-section {
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #f0f0f0;
        }
        
        .rating-section {
            margin-bottom: 16px;
        }
        
        .rating-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 4px;
        }
        
        .star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .star.active {
            color: #ffc107;
        }
        
        .footer-text {
            font-size: 12px;
            color: #999;
            line-height: 1.4;
        }
        
        /* Confetti Animation */
        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 1000;
        }
        
        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #667eea;
            animation: confettiFall 3s ease-in-out forwards;
        }
        
        @keyframes confettiFall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
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
            <button class="close-btn" onclick="goHome()">
                <span class="material-icons" style="font-size: 20px;">close</span>
            </button>
            <div class="header-title">
                <h1>Transaksi Berhasil</h1>
            </div>
            <button class="share-btn" onclick="shareTransaction()">
                <span class="material-icons" style="font-size: 20px;">share</span>
            </button>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Success Animation -->
            <div class="success-section">
                <div class="success-animation">
                    <div class="success-circle">
                        <span class="material-icons success-icon">check_circle</span>
                    </div>
                </div>
                
                <div class="success-title">Pembayaran Berhasil!</div>
                <div class="success-subtitle">
                    Transaksi Anda telah berhasil diproses.<br>
                    Produk akan segera dikirim ke nomor tujuan.
                </div>
                <div class="transaction-id-display">
                    <?php echo htmlspecialchars($transaction['transaction_id']); ?>
                </div>
            </div>
            
            <!-- Transaction Details -->
            <div class="details-section">
                <div class="details-card">
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons" style="font-size: 18px; color: #667eea;">shopping_bag</span>
                            Produk
                        </span>
                        <span class="detail-value"><?php echo htmlspecialchars($transaction['product_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons" style="font-size: 18px; color: #667eea;">phone</span>
                            Nomor Tujuan
                        </span>
                        <span class="detail-value"><?php echo htmlspecialchars($transaction['target_number']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons" style="font-size: 18px; color: #667eea;">schedule</span>
                            Waktu
                        </span>
                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons" style="font-size: 18px; color: #00b894;">payments</span>
                            Total
                        </span>
                        <span class="detail-value detail-amount">Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Action Section -->
            <div class="action-section">
                <div class="action-title">Apa yang ingin Anda lakukan selanjutnya?</div>
                <div class="action-subtitle">
                    Jelajahi fitur lainnya atau lakukan transaksi baru
                </div>
                
                <div class="action-buttons">
                    <button class="action-btn btn-primary" onclick="buyAgain()">
                        <span class="material-icons" style="font-size: 20px;">refresh</span>
                        Beli Lagi
                    </button>
                    <button class="action-btn btn-secondary" onclick="viewHistory()">
                        <span class="material-icons" style="font-size: 20px;">history</span>
                        Lihat Riwayat
                    </button>
                    <button class="action-btn btn-success" onclick="contactSupport()">
                        <span class="material-icons" style="font-size: 20px;">support_agent</span>
                        Hubungi CS
                    </button>
                </div>
            </div>
            
            <!-- Rating Section -->
            <div class="bottom-section">
                <div class="rating-section">
                    <div class="rating-title">Bagaimana pengalaman Anda?</div>
                    <div class="rating-stars">
                        <span class="star" onclick="rate(1)">★</span>
                        <span class="star" onclick="rate(2)">★</span>
                        <span class="star" onclick="rate(3)">★</span>
                        <span class="star" onclick="rate(4)">★</span>
                        <span class="star" onclick="rate(5)">★</span>
                    </div>
                </div>
                
                <div class="footer-text">
                    Terima kasih telah menggunakan Bot Pulsa Digital.<br>
                    Kami siap melayani 24/7 untuk kebutuhan digital Anda.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confetti -->
    <div class="confetti" id="confetti"></div>
    
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
        
        // Create confetti animation
        function createConfetti() {
            const confetti = document.getElementById('confetti');
            const colors = ['#667eea', '#764ba2', '#00b894', '#00cec9', '#ffc107', '#ff6b6b'];
            
            for (let i = 0; i < 50; i++) {
                const piece = document.createElement('div');
                piece.className = 'confetti-piece';
                piece.style.left = Math.random() * 100 + '%';
                piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                piece.style.animationDelay = Math.random() * 2 + 's';
                piece.style.animationDuration = (Math.random() * 2 + 2) + 's';
                confetti.appendChild(piece);
            }
            
            // Remove confetti after animation
            setTimeout(() => {
                confetti.innerHTML = '';
            }, 5000);
        }
        
        // Start confetti on page load
        setTimeout(createConfetti, 500);
        
        // Navigation functions
        function goHome() {
            window.location.href = 'mobile_interface.php';
        }
        
        function buyAgain() {
            window.location.href = 'mobile_interface.php';
        }
        
        function viewHistory() {
            alert('🕐 Fitur riwayat transaksi akan segera hadir!');
        }
        
        function contactSupport() {
            window.open('https://wa.me/6281234567890?text=Halo,%20saya%20butuh%20bantuan%20untuk%20transaksi%20<?php echo $transaction['transaction_id']; ?>', '_blank');
        }
        
        function shareTransaction() {
            if (navigator.share) {
                navigator.share({
                    title: 'Transaksi Berhasil - Bot Pulsa Digital',
                    text: `Transaksi ${<?php echo $transaction['transaction_id']; ?>} berhasil! Total: Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?>`,
                    url: window.location.href
                });
            } else {
                // Fallback copy to clipboard
                const text = `Transaksi ${<?php echo $transaction['transaction_id']; ?>} berhasil! Total: Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?>`;
                navigator.clipboard.writeText(text).then(() => {
                    alert('Detail transaksi disalin ke clipboard!');
                });
            }
        }
        
        // Rating system
        function rate(stars) {
            const starElements = document.querySelectorAll('.star');
            starElements.forEach((star, index) => {
                if (index < stars) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
            
            // Show thank you message
            setTimeout(() => {
                alert(`Terima kasih atas rating ${stars} bintang! 🌟`);
            }, 500);
        }
        
        // Auto redirect after 30 seconds
        setTimeout(() => {
            const redirect = confirm('Ingin kembali ke halaman utama?');
            if (redirect) {
                goHome();
            }
        }, 30000);
    </script>
</body>
</html>