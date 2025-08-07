<?php
// Script untuk inisialisasi database SQLite
try {
    $pdo = new PDO("sqlite:bot_database.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY,
        username TEXT DEFAULT NULL,
        first_name TEXT DEFAULT NULL,
        last_name TEXT DEFAULT NULL,
        balance INTEGER DEFAULT 0,
        is_admin INTEGER DEFAULT 0,
        join_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        product_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        price INTEGER NOT NULL,
        digiflazz_code TEXT NOT NULL,
        description TEXT,
        brand TEXT,
        type TEXT,
        seller TEXT
    )");
    
    // Create indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_type ON products(type)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_brand ON products(brand)");
    
    // Create transactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        amount INTEGER NOT NULL,
        digiflazz_refid TEXT,
        status TEXT DEFAULT 'pending',
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        target_id TEXT
    )");
    
    // Create deposits table
    $pdo->exec("CREATE TABLE IF NOT EXISTS deposits (
        deposit_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        amount INTEGER NOT NULL,
        method TEXT DEFAULT 'bank_transfer',
        status TEXT DEFAULT 'pending',
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        proof_image TEXT
    )");
    
    echo "Database berhasil diinisialisasi!\n";
    echo "Tabel users, products, transactions, dan deposits telah dibuat.\n";
    
    // Check if products table has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch()['count'];
    echo "Jumlah produk dalam database: " . $count . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>