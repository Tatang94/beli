<?php
/**
 * Database Connection dan Helper Functions
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // User Functions
    public function registerUser($user) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (user_id, username, first_name, last_name, join_date) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            username = VALUES(username), 
            first_name = VALUES(first_name), 
            last_name = VALUES(last_name)
        ");
        
        return $stmt->execute([
            $user['id'],
            $user['username'] ?? '',
            $user['first_name'] ?? '',
            $user['last_name'] ?? ''
        ]);
    }
    
    public function getUserBalance($user_id) {
        $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['balance'] : 0;
    }
    
    public function updateUserBalance($user_id, $amount) {
        $stmt = $this->pdo->prepare("UPDATE users SET balance = balance + ? WHERE user_id = ?");
        return $stmt->execute([$amount, $user_id]);
    }
    
    // Product Functions
    public function getCategories() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT type FROM products ORDER BY type");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getBrandsByCategory($category) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT brand FROM products WHERE type = ? ORDER BY brand");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    
    public function getProductsByBrand($brand) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE brand = ? ORDER BY price ASC");
        $stmt->execute([$brand]);
        return $stmt->fetchAll();
    }
    
    public function getProductByCode($code) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE digiflazz_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    // Transaction Functions
    public function createTransaction($user_id, $product_id, $amount, $target_id, $ref_id) {
        $stmt = $this->pdo->prepare("
            INSERT INTO transactions (user_id, product_id, amount, target_id, digiflazz_refid, status, date) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        return $stmt->execute([$user_id, $product_id, $amount, $target_id, $ref_id]);
    }
    
    public function updateTransactionStatus($ref_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE transactions SET status = ? WHERE digiflazz_refid = ?");
        return $stmt->execute([$status, $ref_id]);
    }
    
    // Deposit Functions
    public function createDeposit($user_id, $amount, $proof = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO deposits (user_id, amount, proof, status, date) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        return $stmt->execute([$user_id, $amount, $proof]);
    }
    
    public function getPendingDeposits() {
        $stmt = $this->pdo->prepare("
            SELECT d.*, u.first_name, u.username 
            FROM deposits d 
            JOIN users u ON d.user_id = u.user_id 
            WHERE d.status = 'pending' 
            ORDER BY d.date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function confirmDeposit($deposit_id) {
        $stmt = $this->pdo->prepare("
            UPDATE deposits SET status = 'confirmed' WHERE deposit_id = ?
        ");
        return $stmt->execute([$deposit_id]);
    }
    
    // Settings Functions
    public function getSetting($name) {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    }
    
    public function setSetting($name, $value) {
        $stmt = $this->pdo->prepare("
            INSERT INTO settings (setting_name, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$name, $value]);
    }
    
    // Statistics Functions
    public function getBotStats() {
        $stats = [];
        
        // Total users
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // Total transactions
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM transactions");
        $stats['total_transactions'] = $stmt->fetch()['count'];
        
        // Total deposits
        $stmt = $this->pdo->query("SELECT SUM(amount) as total FROM deposits WHERE status = 'confirmed'");
        $stats['total_deposits'] = $stmt->fetch()['total'] ?? 0;
        
        // Today's transactions
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM transactions WHERE DATE(date) = CURDATE()");
        $stats['today_transactions'] = $stmt->fetch()['count'];
        
        return $stats;
    }
}
?>