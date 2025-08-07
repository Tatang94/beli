<?php
/**
 * Halaman Konfirmasi Transaksi - Simulasi proses pembayaran
 */

require_once 'config.php';
session_start();

$trx_id = $_GET['trx_id'] ?? '';
$product_code = $_GET['code'] ?? '';
$product_name = $_GET['name'] ?? '';
$product_price = $_GET['price'] ?? 0;
$target = $_GET['target'] ?? '';

// Simulasi proses pembayaran
$payment_success = true; // Untuk demo, selalu berhasil

if ($_POST && isset($_POST['confirm_payment'])) {
    // Di sini bisa integrasi dengan Digiflazz API
    // Untuk demo, kita langsung redirect ke success
    header("Location: success.php?trx_id=$trx_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Transaksi</title>
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
        
        .transaction-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
        }
        
        .info-value {
            color: #666;
            text-align: right;
        }
        
        .total-price {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .total-price .amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
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
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
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
        
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🔍 Konfirmasi Transaksi</h2>
        </div>
        
        <div class="content">
            <div class="transaction-info">
                <div class="info-row">
                    <div class="info-label">ID Transaksi:</div>
                    <div class="info-value"><?= htmlspecialchars($trx_id) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Produk:</div>
                    <div class="info-value"><?= htmlspecialchars($product_name) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Target:</div>
                    <div class="info-value"><?= htmlspecialchars($target) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Waktu:</div>
                    <div class="info-value"><?= date('d/m/Y H:i:s') ?></div>
                </div>
            </div>
            
            <div class="total-price">
                <div>Total Pembayaran</div>
                <div class="amount">Rp <?= number_format($product_price) ?></div>
            </div>
            
            <div class="warning">
                ⚠️ Pastikan data sudah benar sebelum melanjutkan pembayaran. Transaksi tidak dapat dibatalkan setelah diproses.
            </div>
            
            <form method="POST">
                <button type="submit" name="confirm_payment" class="btn btn-success">
                    💳 Bayar Sekarang
                </button>
            </form>
            
            <a href="purchase.php?code=<?= $product_code ?>&name=<?= urlencode($product_name) ?>&price=<?= $product_price ?>" class="btn btn-secondary">
                ⬅️ Ubah Data
            </a>
        </div>
    </div>
</body>
</html>