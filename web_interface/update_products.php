<?php
/**
 * Script untuk mengambil produk real dari API Digiflazz
 */

require_once 'config.php';

function categorizeProduct($product_name) {
    $name_lower = strtolower($product_name);
    
    // Pulsa & Kredit
    if (strpos($name_lower, 'pulsa') !== false || 
        strpos($name_lower, 'kredit') !== false ||
        strpos($name_lower, 'regular') !== false) return 'pulsa';
    
    // Paket Data & Internet
    if (strpos($name_lower, 'data') !== false || 
        strpos($name_lower, 'internet') !== false ||
        strpos($name_lower, 'kuota') !== false ||
        strpos($name_lower, 'unlimited') !== false) return 'paket_data';
    
    // PLN & Listrik
    if (strpos($name_lower, 'pln') !== false || 
        strpos($name_lower, 'listrik') !== false ||
        strpos($name_lower, 'token') !== false) return 'pln';
    
    // E-Wallet & E-Money
    if (strpos($name_lower, 'ovo') !== false || 
        strpos($name_lower, 'dana') !== false ||
        strpos($name_lower, 'gopay') !== false ||
        strpos($name_lower, 'shopee') !== false ||
        strpos($name_lower, 'link') !== false ||
        strpos($name_lower, 'jenius') !== false ||
        strpos($name_lower, 'sakuku') !== false ||
        strpos($name_lower, 'tcash') !== false ||
        strpos($name_lower, 'doku') !== false ||
        strpos($name_lower, 'grab') !== false ||
        strpos($name_lower, 'gojek') !== false) return 'emoney';
    
    // Gaming & Voucher Game
    if (strpos($name_lower, 'game') !== false || 
        strpos($name_lower, 'mobile legends') !== false ||
        strpos($name_lower, 'ml') !== false ||
        strpos($name_lower, 'pubg') !== false ||
        strpos($name_lower, 'free fire') !== false ||
        strpos($name_lower, 'valorant') !== false ||
        strpos($name_lower, 'steam') !== false ||
        strpos($name_lower, 'garena') !== false ||
        strpos($name_lower, 'diamond') !== false ||
        strpos($name_lower, 'uc') !== false ||
        strpos($name_lower, 'voucher') !== false) return 'game';
    
    // TV & Streaming
    if (strpos($name_lower, 'tv') !== false || 
        strpos($name_lower, 'netflix') !== false ||
        strpos($name_lower, 'disney') !== false ||
        strpos($name_lower, 'spotify') !== false ||
        strpos($name_lower, 'youtube') !== false ||
        strpos($name_lower, 'vidio') !== false ||
        strpos($name_lower, 'viu') !== false ||
        strpos($name_lower, 'iflix') !== false) return 'streaming';
    
    // BPJS & Asuransi
    if (strpos($name_lower, 'bpjs') !== false || 
        strpos($name_lower, 'asuransi') !== false ||
        strpos($name_lower, 'kesehatan') !== false) return 'bpjs';
    
    // PDAM & Air
    if (strpos($name_lower, 'pdam') !== false || 
        strpos($name_lower, 'air') !== false) return 'pdam';
    
    // Cicilan & Multifinance
    if (strpos($name_lower, 'cicilan') !== false || 
        strpos($name_lower, 'finance') !== false ||
        strpos($name_lower, 'kredit') !== false) return 'multifinance';
    
    // Internet & WiFi Provider
    if (strpos($name_lower, 'wifi') !== false || 
        strpos($name_lower, 'internet') !== false ||
        strpos($name_lower, 'indihome') !== false ||
        strpos($name_lower, 'biznet') !== false ||
        strpos($name_lower, 'mnc') !== false ||
        strpos($name_lower, 'first') !== false) return 'internet_provider';
    
    return 'lainnya';
}

function extractBrand($product_name) {
    $name_lower = strtolower($product_name);
    
    // Provider Seluler
    $brands = [
        'telkomsel', 'indosat', 'xl', 'tri', 'three', 'smartfren', 'axis', 'by.u',
        'pln', 'ovo', 'dana', 'gopay', 'shopeepay', 'linkaja', 'jenius', 'sakuku',
        'mobile legends', 'pubg', 'free fire', 'valorant', 'steam', 'garena',
        'netflix', 'disney', 'spotify', 'youtube', 'vidio', 'viu', 'iflix',
        'grab', 'gojek', 'uber', 'maxim', 'bpjs', 'pdam', 'indihome', 'biznet',
        'mnc', 'first media', 'myrepublic', 'oxygen', 'cbr', 'iconnet'
    ];
    
    foreach ($brands as $brand) {
        if (strpos($name_lower, $brand) !== false) {
            return ucwords($brand);
        }
    }
    
    // Ekstrak brand dari awal nama produk jika tidak ditemukan
    $words = explode(' ', $product_name);
    if (count($words) > 0) {
        return ucfirst(strtolower($words[0]));
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
        $pdo = new PDO("sqlite:../bot_database.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Buat tabel products dengan struktur yang benar
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            product_id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            price INTEGER NOT NULL,
            digiflazz_code TEXT NOT NULL UNIQUE,
            description TEXT,
            brand TEXT,
            type TEXT,
            seller TEXT
        )");
        
        $pdo->exec("DELETE FROM products");
        
        $insert_count = 0;
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO products 
            (name, price, digiflazz_code, brand, type, seller, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($result['data'] as $product) {
            if ($product['buyer_product_status'] === false || 
                $product['seller_product_status'] === false) {
                continue;
            }
            
            $category = categorizeProduct($product['product_name']);
            $brand = extractBrand($product['product_name']);
            
            $stmt->execute([
                $product['product_name'],
                (int)$product['price'],
                $product['buyer_sku_code'],
                $brand,
                $category,
                $product['seller_name'] ?? 'Digiflazz',
                $product['desc'] ?? ''
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