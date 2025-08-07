<?php
/**
 * Script untuk mengambil produk real dari API Digiflazz
 */

require_once 'config.php';

function categorizeProduct($product_name) {
    $name_lower = strtolower($product_name);
    
    // Pulsa & Kredit - Most comprehensive pattern matching
    if (strpos($name_lower, 'pulsa') !== false || 
        strpos($name_lower, 'kredit') !== false ||
        strpos($name_lower, 'regular') !== false ||
        strpos($name_lower, 'credit') !== false ||
        // Simple Telkomsel pulsa detection - just numbers with operator name
        (strpos($name_lower, 'telkomsel') !== false && 
         preg_match('/\b\d+\.?\d*\b/', $name_lower) &&
         strpos($name_lower, 'data') === false && 
         strpos($name_lower, 'internet') === false &&
         strpos($name_lower, 'freedom') === false &&
         strpos($name_lower, 'sakti') === false) ||
        // Other operators - simple detection
        (strpos($name_lower, 'indosat') !== false && 
         preg_match('/\b\d+\.?\d*\b/', $name_lower) &&
         strpos($name_lower, 'data') === false && 
         strpos($name_lower, 'internet') === false) ||
        (strpos($name_lower, 'xl') !== false && 
         preg_match('/\b\d+\.?\d*\b/', $name_lower) &&
         strpos($name_lower, 'data') === false && 
         strpos($name_lower, 'internet') === false) ||
        (strpos($name_lower, 'tri') !== false && 
         preg_match('/\b\d+\.?\d*\b/', $name_lower) &&
         strpos($name_lower, 'data') === false && 
         strpos($name_lower, 'internet') === false) ||
        (strpos($name_lower, 'smartfren') !== false && 
         preg_match('/\b\d+\.?\d*\b/', $name_lower) &&
         strpos($name_lower, 'data') === false && 
         strpos($name_lower, 'internet') === false) ||
        (strpos($name_lower, 'axis') !== false && 
         preg_match('/\b\d+\.?\d*\b/', $name_lower) &&
         strpos($name_lower, 'data') === false && 
         strpos($name_lower, 'internet') === false)) return 'pulsa';
    
    // Paket Data & Internet - more comprehensive
    if (strpos($name_lower, 'data') !== false || 
        strpos($name_lower, 'internet') !== false ||
        strpos($name_lower, 'kuota') !== false ||
        strpos($name_lower, 'unlimited') !== false ||
        strpos($name_lower, 'bulk') !== false ||
        strpos($name_lower, 'paket') !== false ||
        strpos($name_lower, 'gb') !== false ||
        strpos($name_lower, 'mb') !== false ||
        strpos($name_lower, '4g') !== false ||
        strpos($name_lower, '5g') !== false ||
        strpos($name_lower, 'hotrod') !== false ||
        strpos($name_lower, 'bronet') !== false ||
        strpos($name_lower, 'freedom') !== false) return 'data';
    
    // Games & Gaming - comprehensive
    if (strpos($name_lower, 'game') !== false || 
        strpos($name_lower, 'mobile legends') !== false ||
        strpos($name_lower, 'ml') !== false ||
        strpos($name_lower, 'pubg') !== false ||
        strpos($name_lower, 'free fire') !== false ||
        strpos($name_lower, 'ff') !== false ||
        strpos($name_lower, 'valorant') !== false ||
        strpos($name_lower, 'steam') !== false ||
        strpos($name_lower, 'garena') !== false ||
        strpos($name_lower, 'diamond') !== false ||
        strpos($name_lower, 'uc') !== false ||
        strpos($name_lower, 'cp') !== false ||
        strpos($name_lower, 'genshin') !== false ||
        strpos($name_lower, 'roblox') !== false ||
        strpos($name_lower, 'lords') !== false ||
        strpos($name_lower, 'clash') !== false ||
        strpos($name_lower, 'arena') !== false ||
        strpos($name_lower, 'honor') !== false ||
        strpos($name_lower, 'codm') !== false ||
        strpos($name_lower, 'higgs') !== false ||
        strpos($name_lower, 'domino') !== false ||
        strpos($name_lower, 'coin') !== false ||
        strpos($name_lower, 'gold') !== false ||
        strpos($name_lower, 'point') !== false) return 'games';
    
    // Voucher (selain game) - more comprehensive
    if ((strpos($name_lower, 'voucher') !== false && 
         strpos($name_lower, 'game') === false &&
         strpos($name_lower, 'aktivasi') === false &&
         strpos($name_lower, 'diamond') === false &&
         strpos($name_lower, 'uc') === false &&
         strpos($name_lower, 'cp') === false) ||
        strpos($name_lower, 'google play') !== false ||
        strpos($name_lower, 'apple') !== false ||
        strpos($name_lower, 'itunes') !== false ||
        strpos($name_lower, 'netflix') !== false ||
        strpos($name_lower, 'spotify') !== false) return 'voucher';
    
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
    
    // PLN & Listrik - avoid pascabayar
    if ((strpos($name_lower, 'pln') !== false || strpos($name_lower, 'listrik') !== false || strpos($name_lower, 'token') !== false) &&
        strpos($name_lower, 'pascabayar') === false && strpos($name_lower, 'tagihan') === false && strpos($name_lower, 'postpaid') === false) return 'pln';
    
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
    
    // === KATEGORI PASCABAYAR ===
    
    // PLN Pascabayar - lebih comprehensive
    if ((strpos($name_lower, 'pln') !== false && strpos($name_lower, 'pascabayar') !== false) ||
        (strpos($name_lower, 'pln') !== false && strpos($name_lower, 'postpaid') !== false) ||
        (strpos($name_lower, 'pln') !== false && strpos($name_lower, 'tagihan') !== false) ||
        (strpos($name_lower, 'listrik') !== false && strpos($name_lower, 'pascabayar') !== false) ||
        (strpos($name_lower, 'listrik') !== false && strpos($name_lower, 'tagihan') !== false)) return 'pln_pascabayar';
    
    // PDAM
    if (strpos($name_lower, 'pdam') !== false || 
        (strpos($name_lower, 'air') !== false && strpos($name_lower, 'tagihan') !== false)) return 'pdam';
    
    // HP Pascabayar - lebih comprehensive
    if ((strpos($name_lower, 'pascabayar') !== false || strpos($name_lower, 'postpaid') !== false || strpos($name_lower, 'tagihan') !== false) &&
        (strpos($name_lower, 'telkomsel') !== false || strpos($name_lower, 'indosat') !== false ||
         strpos($name_lower, 'xl') !== false || strpos($name_lower, 'tri') !== false ||
         strpos($name_lower, 'smartfren') !== false || strpos($name_lower, 'axis') !== false ||
         strpos($name_lower, 'halo') !== false || strpos($name_lower, 'matrix') !== false)) return 'hp_pascabayar';
    
    // Internet Pascabayar
    if ((strpos($name_lower, 'internet') !== false && strpos($name_lower, 'pascabayar') !== false) ||
        strpos($name_lower, 'indihome') !== false || strpos($name_lower, 'speedy') !== false ||
        strpos($name_lower, 'biznet') !== false || strpos($name_lower, 'first media') !== false ||
        strpos($name_lower, 'mnc play') !== false) return 'internet_pascabayar';
    
    // BPJS Kesehatan - lebih comprehensive
    if ((strpos($name_lower, 'bpjs') !== false && strpos($name_lower, 'kesehatan') !== false) ||
        (strpos($name_lower, 'bpjs') !== false && strpos($name_lower, 'kes') !== false) ||
        (strpos($name_lower, 'bpjs') !== false && strpos($name_lower, 'jkn') !== false)) return 'bpjs_kesehatan';
    
    // Multifinance
    if (strpos($name_lower, 'multifinance') !== false ||
        strpos($name_lower, 'finance') !== false ||
        strpos($name_lower, 'fif') !== false ||
        strpos($name_lower, 'adira') !== false ||
        strpos($name_lower, 'baf') !== false ||
        strpos($name_lower, 'oto') !== false ||
        strpos($name_lower, 'wom') !== false ||
        strpos($name_lower, 'mega') !== false ||
        strpos($name_lower, 'bussan') !== false ||
        strpos($name_lower, 'acc') !== false ||
        strpos($name_lower, 'dipo') !== false) return 'multifinance';
    
    // PBB
    if (strpos($name_lower, 'pbb') !== false ||
        (strpos($name_lower, 'pajak') !== false && strpos($name_lower, 'bumi') !== false)) return 'pbb';
    
    // Gas Negara
    if (strpos($name_lower, 'gas negara') !== false ||
        strpos($name_lower, 'pgn') !== false ||
        (strpos($name_lower, 'gas') !== false && strpos($name_lower, 'negara') !== false)) return 'gas_negara';
    
    // TV Pascabayar
    if ((strpos($name_lower, 'tv') !== false && strpos($name_lower, 'pascabayar') !== false) ||
        strpos($name_lower, 'indovision') !== false ||
        strpos($name_lower, 'big tv') !== false ||
        strpos($name_lower, 'nex parabola') !== false ||
        strpos($name_lower, 'transvision') !== false) return 'tv_pascabayar';
    
    // SAMSAT
    if (strpos($name_lower, 'samsat') !== false ||
        (strpos($name_lower, 'pajak') !== false && strpos($name_lower, 'kendaraan') !== false)) return 'samsat';
    
    // BPJS Ketenagakerjaan
    if ((strpos($name_lower, 'bpjs') !== false && strpos($name_lower, 'ketenagakerjaan') !== false) ||
        (strpos($name_lower, 'bpjs') !== false && strpos($name_lower, 'tk') !== false) ||
        (strpos($name_lower, 'bpjs') !== false && strpos($name_lower, 'tenaga') !== false)) return 'bpjs_ketenagakerjaan';
    
    // PLN Nontaglis
    if (strpos($name_lower, 'pln') !== false && strpos($name_lower, 'nontaglis') !== false) return 'pln_nontaglis';
    
    // Provider Khusus
    if (strpos($name_lower, 'telkomsel omni') !== false) return 'telkomsel_omni';
    if (strpos($name_lower, 'indosat only4u') !== false) return 'indosat_only4u';
    if (strpos($name_lower, 'tri cuanmax') !== false) return 'tri_cuanmax';
    if (strpos($name_lower, 'xl axis cuanku') !== false || strpos($name_lower, 'axis cuanku') !== false) return 'xl_axis_cuanku';
    if (strpos($name_lower, 'by.u') !== false) return 'by_u';
    
    // Additional comprehensive detection for remaining products
    
    // Detect remaining operator products as pulsa if they have numeric values
    if (preg_match('/\b(telkomsel|indosat|xl|tri|three|smartfren|axis|by\.u|byu)\b/i', $name_lower) && 
        preg_match('/\b\d+\.?\d*\b/', $name_lower) &&
        strpos($name_lower, 'data') === false && 
        strpos($name_lower, 'internet') === false &&
        strpos($name_lower, 'paket') === false) {
        return 'pulsa';
    }
    
    // Detect E-Toll and transport cards
    if (strpos($name_lower, 'e-toll') !== false ||
        strpos($name_lower, 'tapcash') !== false ||
        strpos($name_lower, 'brizzi') !== false ||
        strpos($name_lower, 'flazz') !== false ||
        strpos($name_lower, 'jakcard') !== false) {
        return 'emoney';
    }
    
    // Detect streaming services
    if (strpos($name_lower, 'netflix') !== false ||
        strpos($name_lower, 'spotify') !== false ||
        strpos($name_lower, 'youtube') !== false ||
        strpos($name_lower, 'disney') !== false ||
        strpos($name_lower, 'hbo') !== false ||
        strpos($name_lower, 'prime') !== false ||
        strpos($name_lower, 'vidio') !== false ||
        strpos($name_lower, 'viu') !== false) {
        return 'streaming';
    }
    
    // Detect TV services
    if (strpos($name_lower, 'tv') !== false ||
        strpos($name_lower, 'transvision') !== false ||
        strpos($name_lower, 'indovision') !== false ||
        strpos($name_lower, 'orange') !== false) {
        return 'tv';
    }
    
    // Detect aktivasi products
    if (strpos($name_lower, 'aktivasi') !== false ||
        strpos($name_lower, 'perdana') !== false) {
        return 'aktivasi_perdana';
    }
    
    // Detect eSIM products
    if (strpos($name_lower, 'esim') !== false ||
        strpos($name_lower, 'e-sim') !== false) {
        return 'esim';
    }
    
    // Detect phone/communication services
    if (strpos($name_lower, 'anynet') !== false ||
        strpos($name_lower, 'menit') !== false) {
        return 'paket_sms_telpon';
    }
    
    return 'lainnya';
}

function extractBrand($product_name) {
    $name_lower = strtolower($product_name);
    
    // Provider Seluler dan Brand lainnya
    $brands = [
        'telkomsel', 'indosat', 'xl', 'tri', 'three', 'smartfren', 'axis', 'by.u', 'byu',
        'pln', 'ovo', 'dana', 'gopay', 'shopeepay', 'linkaja', 'jenius', 'sakuku',
        'mobile legends', 'pubg', 'free fire', 'valorant', 'steam', 'garena',
        'netflix', 'disney', 'spotify', 'youtube', 'vidio', 'viu', 'iflix',
        'grab', 'gojek', 'uber', 'maxim', 'bpjs', 'pdam', 'indihome', 'biznet',
        'mnc', 'first media', 'myrepublic', 'oxygen', 'cbr', 'iconnet',
        'tapcash', 'brizzi', 'flazz', 'jakcard', 'mandiri', 'bni', 'bca', 'bri',
        'anynet', 'genshin', 'roblox', 'clash', 'arena', 'honor', 'lords'
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

// Fungsi untuk mengambil data dari API
function fetchApiData($data) {
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
    
    return ['response' => $response, 'http_code' => $http_code];
}

function updateProductsFromAPI() {
    $username = DIGIFLAZZ_USERNAME;
    $api_key = DIGIFLAZZ_KEY;
    $sign = md5($username . $api_key . 'pricelist');
    
    // Ambil produk prepaid dan pascabayar
    $data_prepaid = [
        'cmd' => 'prepaid',
        'username' => $username,
        'sign' => $sign
    ];
    
    $data_postpaid = [
        'cmd' => 'pasca',
        'username' => $username,
        'sign' => $sign
    ];

    
    // Ambil produk prepaid
    $prepaid_result = fetchApiData($data_prepaid);
    if ($prepaid_result['http_code'] !== 200) {
        return ['success' => false, 'message' => 'HTTP Error untuk prepaid: ' . $prepaid_result['http_code']];
    }
    
    $prepaid_data = json_decode($prepaid_result['response'], true);
    if (!$prepaid_data || !isset($prepaid_data['data'])) {
        return ['success' => false, 'message' => 'Invalid API response untuk prepaid'];
    }
    
    // Ambil produk pascabayar
    $postpaid_result = fetchApiData($data_postpaid);
    $postpaid_data = ['data' => []]; // Default jika gagal
    
    if ($postpaid_result['http_code'] === 200) {
        $temp_postpaid = json_decode($postpaid_result['response'], true);
        if ($temp_postpaid && isset($temp_postpaid['data'])) {
            $postpaid_data = $temp_postpaid;
        }
    }
    
    // Gabungkan data prepaid dan pascabayar
    $all_products = array_merge($prepaid_data['data'], $postpaid_data['data']);
    
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
        
        foreach ($all_products as $product) {
            // Hanya ambil produk yang aktif dan tersedia untuk dijual
            if ($product['buyer_product_status'] !== true || 
                $product['seller_product_status'] !== true) {
                continue;
            }
            
            $category = categorizeProduct($product['product_name']);
            $brand = extractBrand($product['product_name']);
            
            $stmt->execute([
                $product['product_name'] ?? 'Unknown Product',
                (int)($product['price'] ?? 0),
                $product['buyer_sku_code'] ?? '',
                $brand,
                $category,
                $product['seller_name'] ?? 'Digiflazz',
                $product['desc'] ?? ''
            ]);
            
            $insert_count++;
        }
        
        // Hitung produk pascabayar yang berhasil dimasukkan
        $pascabayar_categories = [
            'pln_pascabayar', 'pdam', 'hp_pascabayar', 'internet_pascabayar', 
            'bpjs_kesehatan', 'multifinance', 'pbb', 'gas_negara', 
            'tv_pascabayar', 'samsat', 'bpjs_ketenagakerjaan', 'pln_nontaglis',
            'telkomsel_omni', 'indosat_only4u', 'tri_cuanmax', 'xl_axis_cuanku', 'by_u'
        ];
        
        $placeholders = str_repeat('?,', count($pascabayar_categories) - 1) . '?';
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE type IN ({$placeholders})");
        $stmt_count->execute($pascabayar_categories);
        $pascabayar_count = $stmt_count->fetchColumn();
        
        return [
            'success' => true, 
            'message' => "Berhasil mengupdate {$insert_count} produk total (prepaid + pascabayar) dari API Digiflazz. Pascabayar: {$pascabayar_count} produk",
            'total_products' => $insert_count,
            'prepaid_count' => count($prepaid_data['data']),
            'postpaid_count' => count($postpaid_data['data']),
            'pascabayar_in_db' => $pascabayar_count
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