<?php
/**
 * Halaman Sukses Transaksi
 */

require_once 'config.php';
session_start();

$trx_id = $_GET['trx_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Berhasil</title>
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
            text-align: center;
        }
        
        .header {
            background: #28a745;
            color: white;
            padding: 30px 20px;
        }
        
        .success-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .content {
            padding: 30px;
        }
        
        .success-message {
            font-size: 18px;
            font-weight: 600;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .transaction-id {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
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
            text-decoration: none;
            display: block;
            text-align: center;
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
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✅</div>
            <h2>Transaksi Berhasil!</h2>
        </div>
        
        <div class="content">
            <div class="success-message">
                Pembayaran Anda telah berhasil diproses
            </div>
            
            <div class="transaction-id">
                <strong>ID Transaksi:</strong><br>
                <?= htmlspecialchars($trx_id) ?>
            </div>
            
            <div style="color: #666; font-size: 14px; margin-bottom: 25px;">
                Transaksi akan diproses dalam 1-5 menit. Silakan cek target Anda.
            </div>
            
            <a href="index.php" class="btn btn-primary">
                🏠 Kembali ke Menu Utama
            </a>
            
            <a href="products.php?category=pulsa" class="btn btn-secondary">
                🛍️ Beli Lagi
            </a>
        </div>
    </div>
</body>
</html>