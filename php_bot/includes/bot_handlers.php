<?php
/**
 * Bot Message Handlers
 */

class BotHandlers {
    private $telegram;
    private $db;
    private $digiflazz;
    private $admin_ids;
    
    public function __construct($telegram, $db, $digiflazz, $admin_ids) {
        $this->telegram = $telegram;
        $this->db = $db;
        $this->digiflazz = $digiflazz;
        $this->admin_ids = $admin_ids;
    }
    
    // Check if user is admin
    private function isAdmin($user_id) {
        return in_array($user_id, $this->admin_ids);
    }
    
    // Handle start command
    public function handleStart($chat_id, $user) {
        $this->db->registerUser($user);
        
        $welcome_message = "👋 Halo {$user['first_name']}!\n\n";
        $welcome_message .= "Selamat datang di Bot Pulsa & PPOB Digital!\n";
        $welcome_message .= "Silakan pilih menu di bawah:";
        
        $keyboard = $this->getMainMenuKeyboard($user['id']);
        
        $this->telegram->sendMessage($chat_id, $welcome_message, $keyboard);
    }
    
    // Get main menu keyboard
    private function getMainMenuKeyboard($user_id) {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🛍 Beli Produk', 'callback_data' => 'buy_product']],
                [['text' => '💰 Deposit Saldo', 'callback_data' => 'deposit']],
                [['text' => '💼 Cek Saldo', 'callback_data' => 'check_balance']]
            ]
        ];
        
        if ($this->isAdmin($user_id)) {
            $keyboard['inline_keyboard'][] = [['text' => '👑 Admin Menu', 'callback_data' => 'admin_menu']];
        }
        
        return $keyboard;
    }
    
    // Handle callback queries
    public function handleCallbackQuery($callback_query) {
        $data = $callback_query['data'];
        $chat_id = $callback_query['message']['chat']['id'];
        $message_id = $callback_query['message']['message_id'];
        $user = $callback_query['from'];
        
        // Answer callback query
        $this->telegram->answerCallbackQuery($callback_query['id']);
        
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
            case 'update_products':
                $this->updateProducts($chat_id, $message_id, $user['id']);
                break;
            case 'bot_stats':
                $this->showBotStats($chat_id, $message_id, $user['id']);
                break;
            default:
                if (strpos($data, 'show_category_') === 0) {
                    $category = substr($data, 14);
                    $this->showBrandsByCategory($chat_id, $message_id, $category);
                } elseif (strpos($data, 'show_brand_') === 0) {
                    $brand = substr($data, 11);
                    $this->showProductsByBrand($chat_id, $message_id, $brand);
                } elseif (strpos($data, 'buy_') === 0) {
                    $product_code = substr($data, 4);
                    $this->startPurchaseFlow($chat_id, $message_id, $user['id'], $product_code);
                }
                break;
        }
    }
    
    // Show main menu
    private function showMainMenu($chat_id, $message_id, $user) {
        $keyboard = $this->getMainMenuKeyboard($user['id']);
        $this->telegram->editMessage($chat_id, $message_id, "📱 Menu Utama\n\nSilakan pilih menu di bawah:", $keyboard);
    }
    
    // Show balance
    private function showBalance($chat_id, $message_id, $user_id) {
        $balance = $this->db->getUserBalance($user_id);
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '💰 Deposit', 'callback_data' => 'deposit']],
                [['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']]
            ]
        ];
        
        $text = "💼 Saldo Anda\n\n💰 Saldo: Rp " . number_format($balance) . "\n\nSilakan pilih menu di bawah:";
        $this->telegram->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Show categories
    private function showCategories($chat_id, $message_id) {
        $categories = $this->db->getCategories();
        
        if (empty($categories)) {
            $this->telegram->editMessage($chat_id, $message_id, "❌ Maaf, saat ini tidak ada produk yang tersedia.");
            return;
        }
        
        $keyboard = ['inline_keyboard' => []];
        foreach ($categories as $cat) {
            $keyboard['inline_keyboard'][] = [['text' => ucfirst($cat['type']), 'callback_data' => 'show_category_' . $cat['type']]];
        }
        $keyboard['inline_keyboard'][] = [['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']];
        
        $this->telegram->editMessage($chat_id, $message_id, "📱 Pilih Kategori Produk\n\nSilakan pilih kategori produk yang ingin Anda beli:", $keyboard);
    }
    
    // Show brands by category
    private function showBrandsByCategory($chat_id, $message_id, $category) {
        $brands = $this->db->getBrandsByCategory($category);
        
        if (empty($brands)) {
            $this->telegram->editMessage($chat_id, $message_id, "❌ Tidak ada brand tersedia dalam kategori $category.");
            return;
        }
        
        $keyboard = ['inline_keyboard' => []];
        foreach ($brands as $brand) {
            $keyboard['inline_keyboard'][] = [['text' => $brand['brand'], 'callback_data' => 'show_brand_' . $brand['brand']]];
        }
        $keyboard['inline_keyboard'][] = [['text' => '🔙 Kembali', 'callback_data' => 'buy_product']];
        
        $text = "🏪 Brand " . ucfirst($category) . "\n\nSilakan pilih brand yang ingin Anda beli:";
        $this->telegram->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Show products by brand
    private function showProductsByBrand($chat_id, $message_id, $brand) {
        $products = $this->db->getProductsByBrand($brand);
        
        if (empty($products)) {
            $this->telegram->editMessage($chat_id, $message_id, "❌ Tidak ada produk tersedia untuk brand $brand.");
            return;
        }
        
        $keyboard = ['inline_keyboard' => []];
        foreach ($products as $product) {
            $text = $product['name'] . " - Rp " . number_format($product['price']);
            $keyboard['inline_keyboard'][] = [['text' => $text, 'callback_data' => 'buy_' . $product['digiflazz_code']]];
        }
        $keyboard['inline_keyboard'][] = [['text' => '🔙 Kembali', 'callback_data' => 'buy_product']];
        
        $text = "📱 Produk " . $brand . "\n\nSilakan pilih produk yang ingin Anda beli:";
        $this->telegram->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Show admin menu
    private function showAdminMenu($chat_id, $message_id, $user_id) {
        if (!$this->isAdmin($user_id)) {
            $this->telegram->editMessage($chat_id, $message_id, "❌ Anda tidak memiliki akses admin.");
            return;
        }
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '➕ Update Produk', 'callback_data' => 'update_products']],
                [['text' => '📊 Statistik Bot', 'callback_data' => 'bot_stats']],
                [['text' => '💵 Konfirmasi Deposit', 'callback_data' => 'confirm_deposit_list']],
                [['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']]
            ]
        ];
        
        $this->telegram->editMessage($chat_id, $message_id, "👑 Menu Admin\n\nSilakan pilih menu admin:", $keyboard);
    }
    
    // Show deposit menu
    private function showDepositMenu($chat_id, $message_id) {
        $text = "💰 Deposit Saldo\n\n";
        $text .= "Silakan transfer ke rekening berikut:\n\n";
        $text .= "🏦 " . BANK_NAME . "\n";
        $text .= "📋 No. Rek: " . BANK_ACCOUNT . "\n";
        $text .= "👤 A.n: " . BANK_HOLDER . "\n\n";
        $text .= "Setelah transfer, kirimkan bukti transfer dengan format:\n";
        $text .= "DEPOSIT [JUMLAH]\n";
        $text .= "Contoh: DEPOSIT 50000";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🏠 Menu Utama', 'callback_data' => 'main_menu']]
            ]
        ];
        
        $this->telegram->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Update products
    private function updateProducts($chat_id, $message_id, $user_id) {
        if (!$this->isAdmin($user_id)) {
            return;
        }
        
        $this->telegram->editMessage($chat_id, $message_id, "⏳ Sedang mengupdate produk dari Digiflazz...\nMohon tunggu sebentar.");
        
        $result = $this->digiflazz->updateProductsToDatabase($this->db);
        
        if ($result[0]) {
            $text = "✅ " . $result[1];
        } else {
            $text = "❌ Gagal update produk: " . $result[1];
        }
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔙 Admin Menu', 'callback_data' => 'admin_menu']]
            ]
        ];
        
        $this->telegram->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Show bot statistics
    private function showBotStats($chat_id, $message_id, $user_id) {
        if (!$this->isAdmin($user_id)) {
            return;
        }
        
        $stats = $this->db->getBotStats();
        
        $text = "📊 Statistik Bot\n\n";
        $text .= "👥 Total User: " . number_format($stats['total_users']) . "\n";
        $text .= "💳 Total Transaksi: " . number_format($stats['total_transactions']) . "\n";
        $text .= "💰 Total Deposit: Rp " . number_format($stats['total_deposits']) . "\n";
        $text .= "📈 Transaksi Hari Ini: " . number_format($stats['today_transactions']) . "\n";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔙 Admin Menu', 'callback_data' => 'admin_menu']]
            ]
        ];
        
        $this->telegram->editMessage($chat_id, $message_id, $text, $keyboard);
    }
    
    // Start purchase flow
    private function startPurchaseFlow($chat_id, $message_id, $user_id, $product_code) {
        $product = $this->db->getProductByCode($product_code);
        
        if (!$product) {
            $this->telegram->editMessage($chat_id, $message_id, "❌ Produk tidak ditemukan.");
            return;
        }
        
        $balance = $this->db->getUserBalance($user_id);
        
        if ($balance < $product['price']) {
            $text = "❌ Saldo tidak cukup!\n\n";
            $text .= "Produk: " . $product['name'] . "\n";
            $text .= "Harga: Rp " . number_format($product['price']) . "\n";
            $text .= "Saldo Anda: Rp " . number_format($balance) . "\n\n";
            $text .= "Silakan deposit terlebih dahulu.";
            
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '💰 Deposit', 'callback_data' => 'deposit']],
                    [['text' => '🔙 Kembali', 'callback_data' => 'buy_product']]
                ]
            ];
            
            $this->telegram->editMessage($chat_id, $message_id, $text, $keyboard);
            return;
        }
        
        // Here you would implement the purchase confirmation flow
        // For now, just show product details
        $text = "🛒 Konfirmasi Pembelian\n\n";
        $text .= "Produk: " . $product['name'] . "\n";
        $text .= "Harga: Rp " . number_format($product['price']) . "\n";
        $text .= "Saldo Anda: Rp " . number_format($balance) . "\n\n";
        $text .= "Masukkan nomor tujuan untuk melanjutkan pembelian.";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '❌ Batal', 'callback_data' => 'buy_product']]
            ]
        ];
        
        $this->telegram->editMessage($chat_id, $message_id, $text, $keyboard);
    }
}
?>