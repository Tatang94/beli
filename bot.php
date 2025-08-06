<?php
/**
 * Telegram Bot untuk Penjualan Produk Digital (Pulsa & PPOB)
 * Versi PHP untuk Hosting cPanel
 */

// Konfigurasi Bot dan API
$BOT_TOKEN = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '8216106872:AAEQ_DxjYtZL0t6vD-y4Pfj90c94wHgXDcc';
$DIGIFLAZZ_USERNAME = $_ENV['DIGIFLAZZ_USERNAME'] ?? 'miwewogwOZ2g';
$DIGIFLAZZ_KEY = $_ENV['DIGIFLAZZ_KEY'] ?? '8c2f1f52-6e36-56de-a1cd-3662bd5eb375';
$ADMIN_IDS = [7044289974]; // Ganti dengan ID admin Anda

// Database configuration
$DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
$DB_NAME = $_ENV['DB_NAME'] ?? 'telegram_bot';
$DB_USER = $_ENV['DB_USER'] ?? 'root';
$DB_PASS = $_ENV['DB_PASS'] ?? '';

class TelegramBot {
    private $token;
    private $pdo;
    private $digiflazz_username;
    private $digiflazz_key;
    private $admin_ids;
    
    public function __construct($token, $pdo, $digiflazz_username, $digiflazz_key, $admin_ids) {
        $this->token = $token;
        $this->pdo = $pdo;
        $this->digiflazz_username = $digiflazz_username;
        $this->digiflazz_key = $digiflazz_key;
        $this->admin_ids = $admin_ids;
    }
    
    // Send message to Telegram
    private function sendMessage($chat_id, $text, $reply_markup = null) {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }
        
        return $this->makeRequest($url, $data);
    }
    
    // Edit message
    private function editMessage($chat_id, $message_id, $text, $reply_markup = null) {
        $url = "https://api.telegram.org/bot{$this->token}/editMessageText";
        $data = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }
        
        return $this->makeRequest($url, $data);
    }
    
    // Make HTTP request
    private function makeRequest($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    // Register or update user
    private function registerUser($user) {
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
    
    // Check if user is admin
    private function isAdmin($user_id) {
        return in_array($user_id, $this->admin_ids);
    }
    
    // Get user balance
    private function getUserBalance($user_id) {
        $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['balance'] : 0;
    }
    
    // Get margin percentage
    private function getMarginPercentage() {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = 'margin_percentage'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? floatval($result['setting_value']) : 10.0;
    }
    
    // Process start command
    private function handleStart($chat_id, $user) {
        $this->registerUser($user);
        
        $welcome_message = "👋 Halo {$user['first_name']}!\n\n";
        $welcome_message .= "Selamat datang di Bot Pulsa & PPOB Digital!\n";
        $welcome_message .= "Silakan pilih menu di bawah:";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🛍 Beli Produk', 'callback_data' => 'buy_product']],
                [['text' => '💰 Deposit Saldo', 'callback_data' => 'deposit']],
                [['text' => '💼 Cek Saldo', 'callback_data' => 'check_balance']]
            ]
        ];
        
        if ($this->isAdmin($user['id'])) {
            $keyboard['inline_keyboard'][] = [['text' => '👑 Admin Menu', 'callback_data' => 'admin_menu']];
        }
        
        $this->sendMessage($chat_id, $welcome_message, $keyboard);
    }
    
    // Process callback queries
    private function handleCallbackQuery($callback_query) {
        $data = $callback_query['data'];
        $chat_id = $callback_query['message']['chat']['id'];
        $message_id = $callback_query['message']['message_id'];
        $user = $callback_query['from'];
        
        switch ($data) {
            case 'main_menu':
                $this->showMainMenu($chat_id, $message_id, $user);
                break;
            case 'check_balance':
                $this->showBalance($chat_id, $message_id, $user['id']);
                break;
            case 'buy_product':
                $this->showCategories($chat_id, $message_id);
                break;
            case 'admin_menu':
                $this->showAdminMenu($chat_id, $message_id, $user['id']);
                break;
            case 'deposit':
                $this->showDepositMenu($chat_id, $message_id);
                break;
            default:
                if (strpos($data, 'show_category_') === 0) {
                    $category = substr($data, 14);
                    $this->showBrandsByCategory($chat_id, $message_id, $category);
                } elseif (strpos($data, 'show_brand_') === 0) {
                    $brand = substr($data, 11);
                    $this->showProductsByBrand($chat_id, $message_id, $brand);
                }
                break;
        }
    }
    
    // Show main menu
    private function showMainMenu($chat_id, $message_id, $user) {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🛍 Beli Produk', 'callback_data' => 'buy_product']],
                [['text' => '💰 Deposit Saldo', 'callback_data' => 'deposit']],
                [['text' => '💼 Cek Saldo', 'callback_data' => 'check_balance']]
            ]
        ];
        
        if ($this->isAdmin($user['id'])) {
            $keyboard['inline_keyboard'][] = [['text' => '👑 Admin Menu', 'callback_data' => 'admin_menu']];
        }
        
        $this->editMessage($chat_id, $message_id, "📱 Menu Utama\n\nSilakan pilih menu di bawah:", $keyboard);
    }
    
    // Show balance
    private function showBalance($chat_id, $message_id, $user_id) {
        $balance = $this->getUserBalance($user_id);
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '💰 Deposit', 'callback_data' => 'deposit']],
                [['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']]
            ]
        ];
        
        $text = "💼 Saldo Anda\n\n💰 Saldo: Rp " . number_format($balance) . "\n\nSilakan pilih menu di bawah:";
        $this->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Show categories
    private function showCategories($chat_id, $message_id) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT type FROM products ORDER BY type");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($categories)) {
            $this->editMessage($chat_id, $message_id, "❌ Maaf, saat ini tidak ada produk yang tersedia.");
            return;
        }
        
        $keyboard = ['inline_keyboard' => []];
        foreach ($categories as $cat) {
            $keyboard['inline_keyboard'][] = [['text' => ucfirst($cat['type']), 'callback_data' => 'show_category_' . $cat['type']]];
        }
        
        $this->editMessage($chat_id, $message_id, "📱 Pilih Kategori Produk\n\nSilakan pilih kategori produk yang ingin Anda beli:", $keyboard);
    }
    
    // Show brands by category
    private function showBrandsByCategory($chat_id, $message_id, $category) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT brand FROM products WHERE type = ? ORDER BY brand");
        $stmt->execute([$category]);
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($brands)) {
            $this->editMessage($chat_id, $message_id, "❌ Tidak ada brand tersedia dalam kategori $category.");
            return;
        }
        
        $keyboard = ['inline_keyboard' => []];
        foreach ($brands as $brand) {
            $keyboard['inline_keyboard'][] = [['text' => $brand['brand'], 'callback_data' => 'show_brand_' . $brand['brand']]];
        }
        
        $text = "🏪 Brand " . ucfirst($category) . "\n\nSilakan pilih brand yang ingin Anda beli:";
        $this->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Show admin menu
    private function showAdminMenu($chat_id, $message_id, $user_id) {
        if (!$this->isAdmin($user_id)) {
            $this->editMessage($chat_id, $message_id, "❌ Anda tidak memiliki akses admin.");
            return;
        }
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '➕ Update Produk', 'callback_data' => 'update_products']],
                [['text' => '⚙️ Setting Margin', 'callback_data' => 'margin_setting']],
                [['text' => '📊 Statistik Bot', 'callback_data' => 'bot_stats']],
                [['text' => '💵 Konfirmasi Deposit', 'callback_data' => 'confirm_deposit_list']],
                [['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']]
            ]
        ];
        
        $this->editMessage($chat_id, $message_id, "👑 Menu Admin\n\nSilakan pilih menu admin:", $keyboard);
    }
    
    // Show deposit menu
    private function showDepositMenu($chat_id, $message_id) {
        $text = "💰 Deposit Saldo\n\n";
        $text .= "Silakan transfer ke rekening berikut:\n\n";
        $text .= "🏦 Bank BCA\n";
        $text .= "📋 No. Rek: 1234567890\n";
        $text .= "👤 A.n: Admin Bot\n\n";
        $text .= "Setelah transfer, kirimkan bukti transfer dengan format:\n";
        $text .= "DEPOSIT [JUMLAH]\n";
        $text .= "Contoh: DEPOSIT 50000";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']]
            ]
        ];
        
        $this->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Process Digiflazz API
    private function processDigiflazzTransaction($product_code, $target_id, $ref_id) {
        $sign_string = $this->digiflazz_username . $this->digiflazz_key . $ref_id;
        $sign = md5($sign_string);
        
        $payload = [
            'username' => $this->digiflazz_username,
            'buyer_sku_code' => $product_code,
            'customer_no' => $target_id,
            'ref_id' => $ref_id,
            'sign' => $sign
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.digiflazz.com/v1/transaction');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return [true, json_decode($result, true)];
        } else {
            return [false, "HTTP Error: $http_code"];
        }
    }
    
    // Main webhook handler
    public function handleWebhook($update) {
        if (isset($update['message'])) {
            $message = $update['message'];
            $chat_id = $message['chat']['id'];
            $user = $message['from'];
            
            if (isset($message['text']) && $message['text'] === '/start') {
                $this->handleStart($chat_id, $user);
            }
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }
}

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize bot
$bot = new TelegramBot($BOT_TOKEN, $pdo, $DIGIFLAZZ_USERNAME, $DIGIFLAZZ_KEY, $ADMIN_IDS);

// Handle webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $update = json_decode($input, true);
    
    if ($update) {
        $bot->handleWebhook($update);
    }
}
?>