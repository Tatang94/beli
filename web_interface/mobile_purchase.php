<?php
/**
 * Mobile Purchase Page - Android Style
 * Halaman pembelian dengan UI/UX mobile-first
 */

require_once 'config.php';
session_start();

$product_code = $_GET['product'] ?? '';

// Database connection
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE digiflazz_code = ?");
    $stmt->execute([$product_code]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $product = null;
}

if (!$product) {
    header("Location: mobile_interface.php");
    exit;
}

// Handle form submission
if ($_POST) {
    $target_number = $_POST['target_number'] ?? '';
    $target_number = preg_replace('/[^0-9]/', '', $target_number); // Remove non-numeric
    
    if (strlen($target_number) >= 10) {
        // Generate transaction ID
        $transaction_id = 'TRX' . date('YmdHis') . rand(100, 999);
        
        // Store transaction (simplified for demo)
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                transaction_id TEXT UNIQUE,
                product_code TEXT,
                product_name TEXT,
                target_number TEXT,
                amount INTEGER,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            
            $stmt = $pdo->prepare("INSERT INTO transactions (transaction_id, product_code, product_name, target_number, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $transaction_id,
                $product['digiflazz_code'],
                $product['name'],
                $target_number,
                $product['price']
            ]);
            
            header("Location: mobile_confirmation.php?trx_id=$transaction_id");
            exit;
            
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    } else {
        $error_message = "Nomor tidak valid. Masukkan nomor yang benar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Beli <?php echo htmlspecialchars($product['name']); ?></title>
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
        
        .back-btn:active {
            transform: scale(0.95);
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
        
        /* Product Info */
        .product-section {
            padding: 24px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .product-card {
            background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
            padding: 20px;
            border-radius: 16px;
            border-left: 4px solid #667eea;
        }
        
        .product-category {
            background: #667eea20;
            color: #667eea;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 8px;
        }
        
        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .product-desc {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }
        
        /* Form Section */
        .form-section {
            flex: 1;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
        }
        
        .form-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 24px;
            line-height: 1.4;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        
        .input-field {
            width: 100%;
            background: #f8f9fa;
            border: 2px solid #f0f0f0;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .input-field:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-hint {
            font-size: 12px;
            color: #999;
            margin-top: 6px;
        }
        
        .error-message {
            background: #ffe6e6;
            color: #d63031;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            border-left: 4px solid #d63031;
        }
        
        /* Number Pad */
        .number-pad {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 16px;
            margin-bottom: 20px;
            display: none;
        }
        
        .number-pad.show {
            display: block;
        }
        
        .number-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .number-btn {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 16px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #333;
        }
        
        .number-btn:active {
            transform: scale(0.95);
            background: #667eea;
            color: white;
        }
        
        .number-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .clear-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .done-btn {
            background: #00b894;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Purchase Button */
        .purchase-section {
            padding: 20px;
            background: white;
            border-top: 1px solid #f0f0f0;
        }
        
        .purchase-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .purchase-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .purchase-btn:not(:disabled):active {
            transform: scale(0.98);
        }
        
        .purchase-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .purchase-btn:not(:disabled):active::before {
            left: 100%;
        }
        
        /* Security Info */
        .security-info {
            background: #e8f4fd;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #0984e3;
        }
        
        .security-title {
            font-size: 14px;
            font-weight: 600;
            color: #0984e3;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .security-text {
            font-size: 12px;
            color: #0984e3;
            line-height: 1.4;
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
                <h1>Pembelian</h1>
                <p>Masukkan detail pembelian</p>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Product Info -->
            <div class="product-section">
                <div class="product-card">
                    <div class="product-category">
                        <?php echo htmlspecialchars($product['category'] ?? 'Digital'); ?>
                    </div>
                    <div class="product-name">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </div>
                    <div class="product-price">
                        Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                    </div>
                    <?php if (!empty($product['description'])): ?>
                        <div class="product-desc">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Form Section -->
            <div class="form-section">
                <div class="form-title">Masukkan Nomor Tujuan</div>
                <div class="form-subtitle">
                    Pastikan nomor yang Anda masukkan sudah benar
                </div>
                
                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="purchaseForm">
                    <div class="input-group">
                        <label class="input-label">Nomor HP</label>
                        <input type="tel" 
                               name="target_number" 
                               class="input-field" 
                               placeholder="08xxxxxxxxxx"
                               pattern="[0-9]*"
                               inputmode="numeric"
                               maxlength="15"
                               required
                               id="targetNumber"
                               value="<?php echo htmlspecialchars($_POST['target_number'] ?? ''); ?>">
                        <div class="input-hint">
                            Contoh: 081234567890 (tanpa +62)
                        </div>
                    </div>
                    
                    <!-- Number Pad (Optional) -->
                    <div class="number-pad" id="numberPad">
                        <div class="number-grid">
                            <button type="button" class="number-btn" onclick="addNumber('1')">1</button>
                            <button type="button" class="number-btn" onclick="addNumber('2')">2</button>
                            <button type="button" class="number-btn" onclick="addNumber('3')">3</button>
                            <button type="button" class="number-btn" onclick="addNumber('4')">4</button>
                            <button type="button" class="number-btn" onclick="addNumber('5')">5</button>
                            <button type="button" class="number-btn" onclick="addNumber('6')">6</button>
                            <button type="button" class="number-btn" onclick="addNumber('7')">7</button>
                            <button type="button" class="number-btn" onclick="addNumber('8')">8</button>
                            <button type="button" class="number-btn" onclick="addNumber('9')">9</button>
                            <button type="button" class="number-btn" onclick="addNumber('0')">0</button>
                            <button type="button" class="number-btn" onclick="deleteNumber()">⌫</button>
                            <button type="button" class="number-btn" onclick="toggleNumberPad()">✓</button>
                        </div>
                    </div>
                    
                    <div class="security-info">
                        <div class="security-title">
                            <span class="material-icons" style="font-size: 16px;">security</span>
                            Transaksi Aman
                        </div>
                        <div class="security-text">
                            Proses otomatis dan instant. Data Anda dilindungi dengan enkripsi SSL.
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Purchase Button -->
            <div class="purchase-section">
                <button type="submit" form="purchaseForm" class="purchase-btn" id="purchaseBtn" disabled>
                    <span class="material-icons" style="font-size: 20px; margin-right: 8px;">shopping_cart</span>
                    Beli Sekarang - Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                </button>
            </div>
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
        
        // Navigation
        function goBack() {
            window.history.back();
        }
        
        // Number input validation
        const targetNumber = document.getElementById('targetNumber');
        const purchaseBtn = document.getElementById('purchaseBtn');
        
        targetNumber.addEventListener('input', function(e) {
            // Remove non-numeric characters
            let value = e.target.value.replace(/[^0-9]/g, '');
            
            // Format with proper spacing
            if (value.length > 4) {
                value = value.substring(0, 4) + ' ' + value.substring(4);
            }
            if (value.length > 9) {
                value = value.substring(0, 9) + ' ' + value.substring(9);
            }
            
            e.target.value = value;
            
            // Validate and enable/disable button
            const cleanNumber = value.replace(/\s/g, '');
            purchaseBtn.disabled = cleanNumber.length < 10;
        });
        
        // Number pad functions
        function addNumber(num) {
            const input = document.getElementById('targetNumber');
            let current = input.value.replace(/\s/g, '');
            if (current.length < 15) {
                input.value = current + num;
                input.dispatchEvent(new Event('input'));
            }
        }
        
        function deleteNumber() {
            const input = document.getElementById('targetNumber');
            let current = input.value.replace(/\s/g, '');
            input.value = current.slice(0, -1);
            input.dispatchEvent(new Event('input'));
        }
        
        function toggleNumberPad() {
            const pad = document.getElementById('numberPad');
            pad.classList.toggle('show');
        }
        
        // Show number pad on input focus
        targetNumber.addEventListener('focus', function() {
            if (window.innerWidth <= 768) {
                document.getElementById('numberPad').classList.add('show');
            }
        });
        
        // Auto-format Indonesian phone numbers
        targetNumber.addEventListener('blur', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            
            // Convert +62 to 0
            if (value.startsWith('62')) {
                value = '0' + value.substring(2);
            }
            
            // Ensure starts with 0
            if (!value.startsWith('0') && value.length > 0) {
                value = '0' + value;
            }
            
            e.target.value = value;
            e.target.dispatchEvent(new Event('input'));
        });
        
        // Form submission with loading state
        document.getElementById('purchaseForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('purchaseBtn');
            btn.innerHTML = '<span class="material-icons" style="font-size: 20px; margin-right: 8px;">hourglass_empty</span>Memproses...';
            btn.disabled = true;
        });
        
        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>