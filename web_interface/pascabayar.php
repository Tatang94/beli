<?php
/**
 * Halaman Pascabayar - Khusus untuk produk pascabayar/tagihan
 */

require_once 'config.php';
session_start();

// Handle update products request
if (isset($_POST['update_products'])) {
    include 'update_products.php';
    $result = updateProductsFromAPI();
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header('Location: pascabayar.php');
    exit;
}

$category = $_GET['category'] ?? 'all_pascabayar';
$search = $_GET['search'] ?? '';

// Database connection SQLite
try {
    $pdo = new PDO("sqlite:../bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $pdo = null;
    $_SESSION['error_message'] = 'Database connection error: ' . $e->getMessage();
}

// Ambil produk pascabayar dari database
$products = [];
$total_products = 0;

if ($pdo) {
    try {
        // Kategori pascabayar saja
        $pascabayar_categories = [
            'pln_pascabayar', 'pdam', 'hp_pascabayar', 'internet_pascabayar', 
            'bpjs_kesehatan', 'multifinance', 'pbb', 'gas_negara', 
            'tv_pascabayar', 'samsat', 'bpjs_ketenagakerjaan', 'pln_nontaglis',
            'telkomsel_omni', 'indosat_only4u', 'tri_cuanmax', 'xl_axis_cuanku', 'by_u'
        ];
        
        $where_conditions = [];
        $params = [];
        
        if ($category === 'all_pascabayar') {
            $placeholders = str_repeat('?,', count($pascabayar_categories) - 1) . '?';
            $where_conditions[] = "type IN ({$placeholders})";
            $params = array_merge($params, $pascabayar_categories);
        } elseif (in_array($category, $pascabayar_categories)) {
            $where_conditions[] = "type = ?";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(name LIKE ? OR brand LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Hitung total produk pascabayar
        $count_sql = "SELECT COUNT(*) FROM products {$where_sql}";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total_products = $stmt->fetchColumn();
        
        // Ambil produk dengan limit
        $limit = 50;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM products {$where_sql} ORDER BY brand ASC, price ASC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Query error: ' . $e->getMessage();
    }
}

$category_names = [
    'all_pascabayar' => 'Semua Pascabayar',
    'pln_pascabayar' => 'PLN Pascabayar',
    'pdam' => 'PDAM',
    'hp_pascabayar' => 'HP Pascabayar',
    'internet_pascabayar' => 'Internet Pascabayar',
    'bpjs_kesehatan' => 'BPJS Kesehatan',
    'multifinance' => 'Multifinance',
    'pbb' => 'PBB',
    'gas_negara' => 'Gas Negara',
    'tv_pascabayar' => 'TV Pascabayar',
    'samsat' => 'SAMSAT',
    'bpjs_ketenagakerjaan' => 'BPJS Ketenagakerjaan',
    'pln_nontaglis' => 'PLN Nontaglis',
    'telkomsel_omni' => 'Telkomsel Omni',
    'indosat_only4u' => 'Indosat Only4u',
    'tri_cuanmax' => 'Tri CuanMax',
    'xl_axis_cuanku' => 'XL Axis Cuanku',
    'by_u' => 'by.U'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pascabayar <?= $category_names[$category] ?? ucfirst($category) ?> - Bot Pulsa</title>
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
            height: 100vh;
            background: white;
            overflow-y: auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        
        .header {
            background: linear-gradient(135deg, #d63031 0%, #74b9ff 100%);
            color: white;
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .content {
            padding: 20px;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
        
        .product-grid {
            display: grid;
            gap: 12px;
        }
        
        .product-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-left: 4px solid #d63031;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .product-card:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .product-name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .product-price {
            color: #d63031;
            font-weight: bold;
            font-size: 16px;
        }
        
        .product-code {
            color: #6c757d;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .product-badge {
            background: #d63031;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
            margin-top: 5px;
        }
        
        .no-products {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }
        
        .update-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 1px solid #d63031;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .update-btn {
            background: #d63031;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .update-btn:hover {
            background: #b71c1c;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .search-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .stats {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
            border-left: 4px solid #d63031;
        }
        
        .pascabayar-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1e40af;
        }
        
        @media (max-width: 480px) {
            .container {
                max-width: 100%;
                height: 100vh;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>💳 <?= $category_names[$category] ?? ucfirst($category) ?></h2>
            <p>Bayar tagihan pascabayar dengan mudah</p>
        </div>
        
        <div class="content">
            <a href="index.php" class="back-btn">⬅️ Kembali ke Menu</a>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="pascabayar-info">
                <h4>ℹ️ Tentang Pascabayar</h4>
                <p>Pascabayar adalah layanan pembayaran tagihan bulanan seperti listrik PLN, air PDAM, telepon pascabayar, internet, BPJS, dan lainnya. Anda bayar setelah menggunakan layanan.</p>
            </div>
            
            <div class="update-section">
                <h4>🔄 Ambil Produk Pascabayar Terbaru</h4>
                <p>Klik tombol di bawah untuk mengambil daftar produk pascabayar terbaru dari API Digiflazz.</p>
                <form method="post" style="display: inline;">
                    <button type="submit" name="update_products" class="update-btn" onclick="return confirm('Yakin ingin mengupdate produk pascabayar? Ini akan mengambil data terbaru dari API.')">
                        🔄 Update Produk Pascabayar
                    </button>
                </form>
            </div>
            
            <div class="stats">
                📊 Total produk pascabayar: <strong><?= number_format($total_products) ?></strong>
            </div>
            
            <form method="get" class="filters">
                <select name="category" class="filter-select" onchange="this.form.submit()">
                    <option value="all_pascabayar" <?= $category == 'all_pascabayar' ? 'selected' : '' ?>>Semua Pascabayar</option>
                    <option value="pln_pascabayar" <?= $category == 'pln_pascabayar' ? 'selected' : '' ?>>PLN Pascabayar</option>
                    <option value="pdam" <?= $category == 'pdam' ? 'selected' : '' ?>>PDAM</option>
                    <option value="hp_pascabayar" <?= $category == 'hp_pascabayar' ? 'selected' : '' ?>>HP Pascabayar</option>
                    <option value="internet_pascabayar" <?= $category == 'internet_pascabayar' ? 'selected' : '' ?>>Internet Pascabayar</option>
                    <option value="bpjs_kesehatan" <?= $category == 'bpjs_kesehatan' ? 'selected' : '' ?>>BPJS Kesehatan</option>
                    <option value="multifinance" <?= $category == 'multifinance' ? 'selected' : '' ?>>Multifinance</option>
                    <option value="pbb" <?= $category == 'pbb' ? 'selected' : '' ?>>PBB</option>
                    <option value="gas_negara" <?= $category == 'gas_negara' ? 'selected' : '' ?>>Gas Negara</option>
                    <option value="tv_pascabayar" <?= $category == 'tv_pascabayar' ? 'selected' : '' ?>>TV Pascabayar</option>
                    <option value="samsat" <?= $category == 'samsat' ? 'selected' : '' ?>>SAMSAT</option>
                    <option value="bpjs_ketenagakerjaan" <?= $category == 'bpjs_ketenagakerjaan' ? 'selected' : '' ?>>BPJS Ketenagakerjaan</option>
                    <option value="pln_nontaglis" <?= $category == 'pln_nontaglis' ? 'selected' : '' ?>>PLN Nontaglis</option>
                    <option value="telkomsel_omni" <?= $category == 'telkomsel_omni' ? 'selected' : '' ?>>Telkomsel Omni</option>
                    <option value="indosat_only4u" <?= $category == 'indosat_only4u' ? 'selected' : '' ?>>Indosat Only4u</option>
                    <option value="tri_cuanmax" <?= $category == 'tri_cuanmax' ? 'selected' : '' ?>>Tri CuanMax</option>
                    <option value="xl_axis_cuanku" <?= $category == 'xl_axis_cuanku' ? 'selected' : '' ?>>XL Axis Cuanku</option>
                    <option value="by_u" <?= $category == 'by_u' ? 'selected' : '' ?>>by.U</option>
                </select>
                <input type="text" name="search" class="search-input" placeholder="Cari produk pascabayar..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="update-btn">🔍 Cari</button>
            </form>
            
            <div class="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                        <div class="product-card" onclick="buyPascabayar('<?= htmlspecialchars($product['digiflazz_code'] ?? '') ?>', '<?= htmlspecialchars($product['name'] ?? '') ?>', <?= $product['price'] ?? 0 ?>)">
                            <div class="product-name"><?= htmlspecialchars($product['name'] ?? 'Produk Tidak Tersedia') ?></div>
                            <div class="product-price">Rp <?= number_format($product['price'] ?? 0) ?></div>
                            <div class="product-badge">PASCABAYAR</div>
                            <div class="product-code">
                                Kode: <?= htmlspecialchars($product['digiflazz_code'] ?? 'N/A') ?> | 
                                Brand: <?= htmlspecialchars($product['brand'] ?? 'Unknown') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <h3>😔 Tidak Ada Produk</h3>
                        <p>Belum ada produk pascabayar dalam kategori ini.</p>
                        <p>Coba update produk dari API atau pilih kategori lain.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function buyPascabayar(code, name, price) {
            // Redirect ke halaman pembelian pascabayar
            const params = new URLSearchParams({
                type: 'pascabayar',
                code: code,
                name: name,
                price: price
            });
            window.location.href = 'purchase.php?' + params.toString();
        }
    </script>
</body>
</html>