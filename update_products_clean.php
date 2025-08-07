<?php
/**
 * Script untuk mengambil produk real dari API Digiflazz
 */

require_once 'config.php';

function categorizeProduct($product_name) {
    $name_lower = strtolower($product_name);
    if (strpos($name_lower, 'pulsa') !== false) return 'pulsa';
    if (strpos($name_lower, 'data') !== false) return 'paket_data';
    if (strpos($name_lower, 'pln') !== false) return 'pln';
    if (strpos($name_lower, 'ovo') !== false || strpos($name_lower, 'dana') !== false) return 'emoney';
    if (strpos($name_lower, 'game') !== false) return 'game';
    return 'lainnya';
}

function extractBrand($product_name) {
    $name_lower = strtolower($product_name);
    $brands = ['telkomsel', 'indosat', 'xl', 'tri', 'smartfren', 'axis', 'pln'];
    foreach ($brands as $brand) {
        if (strpos($name_lower, $brand) !== false) return ucfirst($brand);
    }
    return 'Lainnya';
}

function updateProductsFromAPI() {
    $username = DIGIFLAZZ_USERNAME;
    $api_key = DIGIFLAZZ_KEY;
    $sign = md5($username . $api_key . 'pricelist');
    
    $data = [
        'cmd' => 'prepaid',
        'username' => $username,
        'sign' => $sign
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.digiflazz.com/v1/price-list');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return ['success' => false, 'message' => 'HTTP Error: ' . $http_code];
    }
    
    $result = json_decode($response, true);
    if (!$result || !isset($result['data'])) {
        return ['success' => false, 'message' => 'Invalid API response'];
    }
    
    try {
        $pdo = new PDO("sqlite:bot_database.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_name TEXT NOT NULL,
            buyer_sku_code TEXT UNIQUE NOT NULL,
            buyer_product_status TEXT,
            seller_product_status TEXT,
            unlimited_stock TEXT,
            multi TEXT,
            start_cut_off TEXT,
            end_cut_off TEXT,
            desc TEXT,
            price INTEGER NOT NULL,
            category TEXT,
            brand TEXT,
            type TEXT,
            status TEXT DEFAULT 'active',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $pdo->exec("DELETE FROM products");
        
        $insert_count = 0;
        $stmt = $pdo->prepare("INSERT INTO products 
            (product_name, buyer_sku_code, buyer_product_status, seller_product_status, 
             unlimited_stock, multi, start_cut_off, end_cut_off, desc, price, category, brand, type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($result['data'] as $product) {
            if ($product['buyer_product_status'] === false || 
                $product['seller_product_status'] === false) {
                continue;
            }
            
            $category = categorizeProduct($product['product_name']);
            $brand = extractBrand($product['product_name']);
            
            $stmt->execute([
                $product['product_name'],
                $product['buyer_sku_code'],
                $product['buyer_product_status'] ? 'active' : 'inactive',
                $product['seller_product_status'] ? 'active' : 'inactive',
                $product['unlimited_stock'] ? 'yes' : 'no',
                $product['multi'] ? 'yes' : 'no',
                $product['start_cut_off'] ?? '',
                $product['end_cut_off'] ?? '',
                $product['desc'] ?? '',
                (int)$product['price'],
                $category,
                $brand,
                'digital'
            ]);
            
            $insert_count++;
        }
        
        return [
            'success' => true, 
            'message' => "Berhasil mengupdate {$insert_count} produk dari API Digiflazz",
            'total_products' => $insert_count
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    
    $admin_logged_in = false;
    if (isset($_COOKIE['admin_session']) && $_COOKIE['admin_session'] === 'authenticated') {
        $admin_logged_in = true;
    }
    
    if (!$admin_logged_in) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    $result = updateProductsFromAPI();
    echo json_encode($result);
}
?>