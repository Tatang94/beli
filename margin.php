<?php
require_once 'config.php';

// Check admin authentication
if (!isset($_COOKIE['admin_session']) || $_COOKIE['admin_session'] !== 'authenticated') {
    header('Location: admincenter');
    exit;
}

// Handle margin update
$message = '';
if ($_POST) {
    try {
        $pdo = new PDO("sqlite:bot_database.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $category = $_POST['category'];
        $margin = floatval($_POST['margin']);
        
        // Create margins table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS margins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT UNIQUE,
            margin_percent REAL DEFAULT 10.0
        )");
        
        // Insert or update margin
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO margins (category, margin_percent) VALUES (?, ?)");
        $stmt->execute([$category, $margin]);
        
        $message = "Margin untuk kategori '$category' berhasil diatur ke $margin%";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get current margins
$margins = [];
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT * FROM margins ORDER BY category");
    $margins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}

// Get available categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT type FROM products WHERE type IS NOT NULL ORDER BY type");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = ['pulsa', 'paket_data', 'pln', 'emoney', 'game', 'streaming'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Margin - Admin Panel</title>
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
            max-width: 800px;
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
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-select, .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
        }
        
        .form-select:focus, .form-input:focus {
            border-color: #667eea;
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
            margin-right: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .margin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .margin-table th, .margin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .margin-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .back-link {
            color: #667eea;
            text-decoration: none;
            margin-right: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Atur Margin Keuntungan</h1>
            <p>Kelola margin untuk setiap kategori produk</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message"><?= $message ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label class="form-label">Kategori Produk</label>
                    <select name="category" class="form-select" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Margin (%)</label>
                    <input type="number" name="margin" class="form-input" min="0" max="100" step="0.1" placeholder="Contoh: 10.5" required>
                </div>
                
                <button type="submit" class="btn">💾 Simpan Margin</button>
                <a href="admincenter" class="btn btn-secondary">🔙 Kembali</a>
            </form>
            
            <?php if ($margins): ?>
                <h3 style="margin-top: 30px; margin-bottom: 15px;">📊 Margin Saat Ini</h3>
                <table class="margin-table">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Margin (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($margins as $margin): ?>
                            <tr>
                                <td><?= htmlspecialchars($margin['category']) ?></td>
                                <td><?= number_format($margin['margin_percent'], 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>