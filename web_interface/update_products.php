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
        strpos($name_lower, 'unlimited') !== false) return 'data';
    
    // Games & Gaming
    if (strpos($name_lower, 'game') !== false || 
        strpos($name_lower, 'mobile legends') !== false ||
        strpos($name_lower, 'ml') !== false ||
        strpos($name_lower, 'pubg') !== false ||
        strpos($name_lower, 'free fire') !== false ||
        strpos($name_lower, 'valorant') !== false ||
        strpos($name_lower, 'steam') !== false ||
        strpos($name_lower, 'garena') !== false ||
        strpos($name_lower, 'diamond') !== false ||
        strpos($name_lower, 'uc') !== false) return 'games';
    
    // Voucher (selain game)
    if (strpos($name_lower, 'voucher') !== false && 
        strpos($name_lower, 'game') === false &&
        strpos($name_lower, 'aktivasi') === false) return 'voucher';
    
    // E-Money & E-Wallet
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
    
    // PLN & Listrik
    if (strpos($name_lower, 'pln') !== false || 
        strpos($name_lower, 'listrik') !== false ||
        strpos($name_lower, 'token') !== false) return 'pln';
    
    // International Top-ups
    if (strpos($name_lower, 'china') !== false) return 'china_topup';
    if (strpos($name_lower, 'malaysia') !== false) return 'malaysia_topup';
    if (strpos($name_lower, 'philippines') !== false) return 'philippines_topup';
    if (strpos($name_lower, 'singapore') !== false) return 'singapore_topup';
    if (strpos($name_lower, 'thailand') !== false) return 'thailand_topup';
    if (strpos($name_lower, 'vietnam') !== false) return 'vietnam_topup';
    
    // SMS & Telpon
    if (strpos($name_lower, 'sms') !== false || 
        strpos($name_lower, 'telpon') !== false ||
        strpos($name_lower, 'telepon') !== false ||
        strpos($name_lower, 'nelpon') !== false) return 'paket_sms_telpon';
    
    // Streaming Services
    if (strpos($name_lower, 'netflix') !== false ||
        strpos($name_lower, 'disney') !== false ||
        strpos($name_lower, 'spotify') !== false ||
        strpos($name_lower, 'youtube') !== false ||
        strpos($name_lower, 'vidio') !== false ||
        strpos($name_lower, 'viu') !== false ||
        strpos($name_lower, 'iflix') !== false) return 'streaming';
    
    // TV & Broadcasting
    if (strpos($name_lower, 'tv') !== false || 
        strpos($name_lower, 'indovision') !== false ||
        strpos($name_lower, 'transtv') !== false ||
        strpos($name_lower, 'first media') !== false) return 'tv';
    
    // Aktivasi Voucher
    if (strpos($name_lower, 'aktivasi voucher') !== false ||
        (strpos($name_lower, 'aktivasi') !== false && strpos($name_lower, 'voucher') !== false)) return 'aktivasi_voucher';
    
    // Masa Aktif
    if (strpos($name_lower, 'masa aktif') !== false ||
        strpos($name_lower, 'perpanjang') !== false) return 'masa_aktif';
    
    // Bundling
    if (strpos($name_lower, 'bundling') !== false ||
        strpos($name_lower, 'combo') !== false ||
        strpos($name_lower, 'paket') !== false) return 'bundling';
    
    // Aktivasi Perdana
    if (strpos($name_lower, 'aktivasi perdana') !== false ||
        (strpos($name_lower, 'aktivasi') !== false && strpos($name_lower, 'perdana') !== false)) return 'aktivasi_perdana';
    
    // Gas
    if (strpos($name_lower, 'gas') !== false ||
        strpos($name_lower, 'lpg') !== false ||
        strpos($name_lower, 'pertamina') !== false) return 'gas';
    
    // eSIM
    if (strpos($name_lower, 'esim') !== false ||
        strpos($name_lower, 'e-sim') !== false) return 'esim';
    
    // Media Sosial
    if (strpos($name_lower, 'instagram') !== false ||
        strpos($name_lower, 'facebook') !== false ||
        strpos($name_lower, 'twitter') !== false ||
        strpos($name_lower, 'tiktok') !== false ||
        strpos($name_lower, 'whatsapp') !== false) return 'media_sosial';
    
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