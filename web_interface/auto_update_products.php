<?php
/**
 * Auto Update Products dari Digiflazz API
 * Script ini akan dijalankan setiap 30 menit untuk update produk otomatis
 */

require_once 'config.php';

// Set time limit untuk operasi yang membutuhkan waktu lama
set_time_limit(300); // 5 menit

// Setup logging
function logMessage($message) {
    $log_file = 'auto_update_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

// Database connection menggunakan SQLite untuk development
function getDatabaseConnection() {
    try {
        $pdo = new PDO("sqlite:bot_database.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables if not exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            price INTEGER NOT NULL,
            digiflazz_code TEXT NOT NULL UNIQUE,
            description TEXT,
            brand TEXT,
            type TEXT,
            seller TEXT,
            category TEXT,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_name TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            updated_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        return $pdo;
    } catch (PDOException $e) {
        logMessage("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Function untuk generate signature Digiflazz
function generateSignature($username, $apikey, $devcmd) {
    return md5($username . $apikey . $devcmd);
}

// Function untuk kategorisasi produk (versi lengkap berdasarkan dokumentasi Digiflazz 2025)
function categorizeProduct($product_name, $api_category = '', $api_type = '') {
    $name_lower = strtolower($product_name);
    
    // Gunakan kategori API jika tersedia dan valid
    $api_category_lower = strtolower($api_category);
    $api_type_lower = strtolower($api_type);
    
    // Pulsa - deteksi berdasarkan brand + nominal atau kata pulsa
    $pulsa_brands = ['telkomsel', 'indosat', 'xl', 'axis', 'tri', 'smartfren', 'by.u'];
    $is_pulsa_brand = false;
    
    foreach ($pulsa_brands as $brand) {
        if (strpos($name_lower, $brand) !== false) {
            $is_pulsa_brand = true;
            break;
        }
    }
    
    // Pulsa jika: ada kata "pulsa" ATAU (brand pulsa + tidak ada kata data/internet/kuota/paket/gb/mb)
    if ((strpos($name_lower, 'pulsa') !== false && 
         strpos($name_lower, 'data') === false && 
         strpos($name_lower, 'internet') === false &&
         strpos($name_lower, 'kuota') === false &&
         strpos($name_lower, 'paket') === false) ||
        ($is_pulsa_brand && 
         strpos($name_lower, 'data') === false && 
         strpos($name_lower, 'internet') === false &&
         strpos($name_lower, 'kuota') === false &&
         strpos($name_lower, 'paket') === false &&
         strpos($name_lower, 'gb') === false &&
         strpos($name_lower, 'mb') === false &&
         preg_match('/\b\d+\.?\d*\b/', $name_lower)) || // ada angka (nominal)
         strpos($api_category_lower, 'pulsa') !== false) return 'Pulsa';
    
    // Data & Internet
    if (strpos($name_lower, 'data') !== false || 
        strpos($name_lower, 'internet') !== false ||
        strpos($name_lower, 'kuota') !== false ||
        (strpos($name_lower, 'paket') !== false && strpos($name_lower, 'data') !== false) ||
        strpos($name_lower, 'gb') !== false ||
        strpos($name_lower, 'mb') !== false ||
        strpos($name_lower, 'unlimited') !== false) return 'Data';
    
    // Games & Voucher Game
    if (strpos($name_lower, 'game') !== false || 
        strpos($name_lower, 'mobile legends') !== false ||
        strpos($name_lower, 'ml') !== false ||
        strpos($name_lower, 'pubg') !== false ||
        strpos($name_lower, 'free fire') !== false ||
        strpos($name_lower, 'ff') !== false ||
        strpos($name_lower, 'diamond') !== false ||
        strpos($name_lower, 'uc') !== false ||
        strpos($name_lower, 'genshin') !== false ||
        strpos($name_lower, 'honkai') !== false ||
        strpos($name_lower, 'roblox') !== false ||
        strpos($name_lower, 'steam') !== false ||
        strpos($name_lower, 'aov') !== false ||
        strpos($name_lower, 'call of duty') !== false ||
        strpos($name_lower, 'cod') !== false ||
        strpos($name_lower, 'garena') !== false) return 'Game';
    
    // E-Money & Digital Wallet (lebih lengkap)
    if (strpos($name_lower, 'ovo') !== false || 
        strpos($name_lower, 'dana') !== false ||
        strpos($name_lower, 'gopay') !== false ||
        strpos($name_lower, 'shopee') !== false ||
        strpos($name_lower, 'linkaja') !== false ||
        strpos($name_lower, 'tcash') !== false ||
        strpos($name_lower, 'jenius') !== false ||
        strpos($name_lower, 'sakuku') !== false ||
        strpos($name_lower, 'isaku') !== false ||
        strpos($name_lower, 'brizzi') !== false ||
        strpos($name_lower, 'flazz') !== false ||
        strpos($name_lower, 'e-toll') !== false ||
        strpos($name_lower, 'mandiri e-money') !== false) return 'E-Money';
    
    // PLN & Token Listrik
    if (strpos($name_lower, 'pln') !== false || 
        strpos($name_lower, 'listrik') !== false || 
        strpos($name_lower, 'token') !== false ||
        strpos($api_category_lower, 'pln') !== false) return 'PLN';
    
    // Streaming & TV
    if (strpos($name_lower, 'netflix') !== false ||
        strpos($name_lower, 'disney') !== false ||
        strpos($name_lower, 'vidio') !== false ||
        strpos($name_lower, 'spotify') !== false ||
        strpos($name_lower, 'youtube premium') !== false ||
        strpos($name_lower, 'amazon prime') !== false ||
        strpos($name_lower, 'viu') !== false ||
        strpos($name_lower, 'wetv') !== false ||
        strpos($name_lower, 'iqiyi') !== false ||
        strpos($name_lower, 'apple music') !== false ||
        strpos($name_lower, 'hbo') !== false ||
        strpos($name_lower, 'mola tv') !== false ||
        strpos($name_lower, 'vision+') !== false) return 'Streaming';
    
    // PDAM & Air
    if (strpos($name_lower, 'pdam') !== false ||
        strpos($name_lower, 'air') !== false ||
        strpos($name_lower, 'aetra') !== false ||
        strpos($name_lower, 'palyja') !== false ||
        strpos($api_category_lower, 'pdam') !== false) return 'PDAM';
    
    // Gas PGN
    if (strpos($name_lower, 'gas') !== false ||
        strpos($name_lower, 'pgn') !== false) return 'Gas';
    
    // Voucher Google Play, Apple, dll
    if (strpos($name_lower, 'voucher') !== false ||
        strpos($name_lower, 'google play') !== false ||
        strpos($name_lower, 'apple') !== false ||
        strpos($name_lower, 'itunes') !== false) return 'Voucher';
    
    // SMS & Telepon
    if (strpos($name_lower, 'sms') !== false || 
        strpos($name_lower, 'telepon') !== false ||
        strpos($name_lower, 'telpon') !== false ||
        strpos($name_lower, 'paket sms') !== false ||
        strpos($name_lower, 'paket telepon') !== false ||
        strpos($name_lower, 'paket telpon') !== false ||
        strpos($name_lower, 'unlimited telpon') !== false) return 'SMS Telpon';
    
    // Media Sosial
    if (strpos($name_lower, 'facebook') !== false ||
        strpos($name_lower, 'instagram') !== false ||
        strpos($name_lower, 'twitter') !== false ||
        strpos($name_lower, 'tiktok') !== false ||
        strpos($name_lower, 'whatsapp') !== false) return 'Media Sosial';
    
    // Aktivasi & Masa Aktif
    if (strpos($name_lower, 'aktivasi') !== false ||
        strpos($name_lower, 'masa aktif') !== false ||
        strpos($name_lower, 'extend') !== false) return 'Aktivasi';
    
    // eSIM
    if (strpos($name_lower, 'esim') !== false ||
        strpos($name_lower, 'e-sim') !== false) return 'eSIM';
    
    // Bundling Packages
    if (strpos($name_lower, 'bundling') !== false ||
        strpos($name_lower, 'combo') !== false) return 'Bundling';
    
    // Pascabayar categories
    if (strpos($api_category_lower, 'pascabayar') !== false ||
        strpos($api_type_lower, 'postpaid') !== false ||
        strpos($name_lower, 'pascabayar') !== false ||
        strpos($name_lower, 'tagihan') !== false) {
        
        if (strpos($name_lower, 'pln') !== false) return 'PLN Pascabayar';
        if (strpos($name_lower, 'pdam') !== false) return 'PDAM';
        if (strpos($name_lower, 'bpjs') !== false) return 'BPJS';
        if (strpos($name_lower, 'multifinance') !== false) return 'Multifinance';
        if (strpos($name_lower, 'pbb') !== false) return 'PBB';
        if (strpos($name_lower, 'samsat') !== false) return 'SAMSAT';
        
        return 'Lainnya';
    }
    
    // International Top Up
    if (strpos($name_lower, 'china') !== false) return 'China Topup';
    if (strpos($name_lower, 'malaysia') !== false) return 'Malaysia Topup';
    if (strpos($name_lower, 'philippines') !== false) return 'Philippines Topup';
    if (strpos($name_lower, 'singapore') !== false) return 'Singapore Topup';
    if (strpos($name_lower, 'thailand') !== false) return 'Thailand Topup';
    if (strpos($name_lower, 'vietnam') !== false) return 'Vietnam Topup';
    
    return 'Lainnya';
}

// Function untuk ambil data produk dari Digiflazz API
function fetchProductsFromDigiflazz() {
    $username = DIGIFLAZZ_USERNAME;
    $apikey = DIGIFLAZZ_KEY;
    $devcmd = 'pricelist';
    
    $signature = generateSignature($username, $apikey, $devcmd);
    
    $data = array(
        'cmd' => $devcmd,
        'username' => $username,
        'sign' => $signature
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.digiflazz.com/v1/price-list');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0'
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        logMessage("CURL Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        logMessage("HTTP Error: " . $http_code);
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage("JSON Decode Error: " . json_last_error_msg());
        return false;
    }
    
    return $result;
}

// Function untuk update produk ke database
function updateProductsDatabase($products_data, $pdo) {
    if (!isset($products_data['data']) || !is_array($products_data['data'])) {
        logMessage("Invalid products data structure");
        return false;
    }
    
    $products = $products_data['data'];
    $updated_count = 0;
    $inserted_count = 0;
    $error_count = 0;
    
    // Get current margin setting
    $margin_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = 'margin_percentage'");
    $margin_stmt->execute();
    $margin_result = $margin_stmt->fetch(PDO::FETCH_ASSOC);
    $margin_percentage = $margin_result ? (float)$margin_result['setting_value'] : 10;
    
    foreach ($products as $product) {
        try {
            // Skip produk yang tidak valid
            if (!isset($product['product_name']) || !isset($product['price']) || !isset($product['buyer_sku_code'])) {
                $error_count++;
                continue;
            }
            
            // Filter untuk skip POSTPAID saja (terima semua yang lain)
            if (isset($product['type']) && strtoupper($product['type']) === 'POSTPAID') {
                continue; // Skip postpaid products only
            }
            
            // Hitung harga jual dengan margin
            $base_price = (int)$product['price'];
            $selling_price = $base_price + ($base_price * $margin_percentage / 100);
            $selling_price = (int)round($selling_price);
            
            // Kategorisasi produk
            $category = categorizeProduct(
                $product['product_name'], 
                $product['category'] ?? '', 
                $product['type'] ?? ''
            );
            
            // Cek apakah produk sudah ada
            $check_stmt = $pdo->prepare("SELECT id FROM products WHERE digiflazz_code = ?");
            $check_stmt->execute([$product['buyer_sku_code']]);
            
            if ($check_stmt->fetch()) {
                // Update produk yang sudah ada
                $update_stmt = $pdo->prepare("
                    UPDATE products SET 
                        name = ?, 
                        price = ?, 
                        description = ?, 
                        brand = ?, 
                        type = ?, 
                        seller = ?,
                        category = ?,
                        last_updated = CURRENT_TIMESTAMP
                    WHERE digiflazz_code = ?
                ");
                
                $update_stmt->execute([
                    $product['product_name'],
                    $selling_price,
                    $product['desc'] ?? '',
                    $product['brand'] ?? '',
                    $product['type'] ?? '',
                    $product['seller_name'] ?? '',
                    $category,
                    $product['buyer_sku_code']
                ]);
                
                $updated_count++;
            } else {
                // Insert produk baru
                $insert_stmt = $pdo->prepare("
                    INSERT INTO products (name, price, digiflazz_code, description, brand, type, seller, category, last_updated) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                
                $insert_stmt->execute([
                    $product['product_name'],
                    $selling_price,
                    $product['buyer_sku_code'],
                    $product['desc'] ?? '',
                    $product['brand'] ?? '',
                    $product['type'] ?? '',
                    $product['seller_name'] ?? '',
                    $category
                ]);
                
                $inserted_count++;
            }
            
        } catch (PDOException $e) {
            logMessage("Database error for product " . $product['buyer_sku_code'] . ": " . $e->getMessage());
            $error_count++;
        }
    }
    
    logMessage("Products update completed: {$inserted_count} inserted, {$updated_count} updated, {$error_count} errors");
    
    // Update last sync time
    try {
        $sync_stmt = $pdo->prepare("
            INSERT OR REPLACE INTO settings (setting_name, setting_value, updated_date) 
            VALUES ('last_product_sync', ?, CURRENT_TIMESTAMP)
        ");
        $sync_stmt->execute([date('Y-m-d H:i:s')]);
    } catch (PDOException $e) {
        logMessage("Error updating sync time: " . $e->getMessage());
    }
    
    return true;
}

// Main execution
function main() {
    logMessage("Starting automatic product update...");
    
    // Check if already running (simple file lock)
    $lock_file = 'auto_update.lock';
    if (file_exists($lock_file)) {
        $lock_time = filemtime($lock_file);
        if (time() - $lock_time < 1800) { // 30 minutes
            logMessage("Update already running or recently completed. Skipping...");
            return;
        } else {
            unlink($lock_file); // Remove old lock
        }
    }
    
    // Create lock file
    file_put_contents($lock_file, time());
    
    try {
        // Connect to database
        $pdo = getDatabaseConnection();
        if (!$pdo) {
            logMessage("Failed to connect to database");
            return;
        }
        
        // Fetch products from Digiflazz
        logMessage("Fetching products from Digiflazz API...");
        $products_data = fetchProductsFromDigiflazz();
        
        if (!$products_data) {
            logMessage("Failed to fetch products from Digiflazz API");
            return;
        }
        
        if (!isset($products_data['data'])) {
            logMessage("Invalid response from Digiflazz API");
            return;
        }
        
        logMessage("Received " . count($products_data['data']) . " products from API");
        
        // Update database
        logMessage("Updating products database...");
        $result = updateProductsDatabase($products_data, $pdo);
        
        if ($result) {
            logMessage("Product update completed successfully");
        } else {
            logMessage("Product update failed");
        }
        
    } catch (Exception $e) {
        logMessage("Error during update: " . $e->getMessage());
    } finally {
        // Remove lock file
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
    }
}

// Run the update if called directly
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    main();
}
?>