<?php
/**
 * Halaman Pembelian - Form input target dan konfirmasi
 */

require_once 'config.php';
session_start();

$product_code = $_GET['code'] ?? '';
$product_name = $_GET['name'] ?? '';
$product_price = $_GET['price'] ?? 0;

if ($_POST) {
    $target = $_POST['target'] ?? '';
    
    if ($target) {
        // Simpan ke database atau proses transaksi
        $transaction_id = 'TRX' . time() . rand(100, 999);
        
        // Redirect ke halaman konfirmasi
        header("Location: confirmation.php?trx_id=$transaction_id&code=$product_code&name=" . urlencode($product_name) . "&price=$product_price&target=$target");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembelian <?= htmlspecialchars($product_name) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: #075e54;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .content {
            padding: 30px;
        }
        
        .product-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        input[type="text"], input[type="tel"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        input[type="text"]:focus, input[type="tel"]:focus {
            outline: none;
            border-color: #25d366;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: #25d366;
            color: white;
        }
        
        .btn-primary:hover {
            background: #128c7e;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .helper-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>💰 Konfirmasi Pembelian</h2>
        </div>
        
        <div class="content">
            <div class="product-info">
                <div class="product-name"><?= htmlspecialchars($product_name) ?></div>
                <div class="product-price">Rp <?= number_format($product_price) ?></div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nomor Target:</label>
                    <input type="tel" name="target" required placeholder="Contoh: 081234567890" 
                           pattern="[0-9]+" title="Hanya angka yang diperbolehkan">
                    <div class="helper-text">
                        Masukkan nomor HP/ID yang akan diisi
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    ✅ Lanjutkan Pembelian
                </button>
            </form>
            
            <a href="products.php?category=<?= $_GET['category'] ?? 'pulsa' ?>" class="btn btn-secondary">
                ⬅️ Kembali ke Daftar Produk
            </a>
        </div>
    </div>
</body>
</html>