<?php
/**
 * Main Bot File - Webhook Handler
 * Entry point untuk webhook Telegram
 */

// Include configuration
require_once 'config.php';

// Include classes
require_once 'includes/database.php';
require_once 'includes/telegram.php';
require_once 'includes/digiflazz.php';
require_once 'includes/bot_handlers.php';

// Error handling
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
});

// Initialize components
try {
    $db = Database::getInstance();
    $telegram = new TelegramAPI(BOT_TOKEN);
    $digiflazz = new DigiflazzAPI(DIGIFLAZZ_USERNAME, DIGIFLAZZ_KEY);
    $handlers = new BotHandlers($telegram, $db, $digiflazz, ADMIN_IDS);
} catch (Exception $e) {
    error_log("Bot initialization error: " . $e->getMessage());
    http_response_code(500);
    exit;
}

// Main webhook handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $update = json_decode($input, true);
    
    if (!$update) {
        error_log("Invalid JSON received");
        http_response_code(400);
        exit;
    }
    
    try {
        // Handle different update types
        if (isset($update['message'])) {
            handleMessage($update['message'], $handlers);
        } elseif (isset($update['callback_query'])) {
            $handlers->handleCallbackQuery($update['callback_query']);
        }
        
        // Return success response
        http_response_code(200);
        echo 'OK';
        
    } catch (Exception $e) {
        error_log("Webhook handling error: " . $e->getMessage());
        http_response_code(500);
    }
} else {
    // Show info page for GET requests
    showInfoPage();
}

/**
 * Handle incoming messages
 */
function handleMessage($message, $handlers) {
    $chat_id = $message['chat']['id'];
    $user = $message['from'];
    
    if (isset($message['text'])) {
        $text = trim($message['text']);
        
        // Handle commands
        if ($text === '/start') {
            $handlers->handleStart($chat_id, $user);
        } elseif (preg_match('/^DEPOSIT\s+(\d+)$/i', $text, $matches)) {
            handleDepositRequest($chat_id, $user['id'], $matches[1], $handlers);
        } elseif (preg_match('/^\d+$/', $text)) {
            // Handle phone number input for purchase
            handlePhoneNumberInput($chat_id, $user['id'], $text, $handlers);
        }
    } elseif (isset($message['photo'])) {
        // Handle photo upload for deposit proof
        handleDepositProof($chat_id, $user['id'], $message['photo'], $handlers);
    }
}

/**
 * Handle deposit request
 */
function handleDepositRequest($chat_id, $user_id, $amount, $handlers) {
    global $db, $telegram;
    
    if ($amount < 10000) {
        $telegram->sendMessage($chat_id, "❌ Minimum deposit Rp 10.000");
        return;
    }
    
    if ($amount > 10000000) {
        $telegram->sendMessage($chat_id, "❌ Maximum deposit Rp 10.000.000");
        return;
    }
    
    // Create deposit record
    $db->createDeposit($user_id, $amount);
    
    $text = "✅ Permintaan deposit sebesar Rp " . number_format($amount) . " telah diterima!\n\n";
    $text .= "Silakan transfer ke rekening:\n";
    $text .= "🏦 " . BANK_NAME . "\n";
    $text .= "📋 " . BANK_ACCOUNT . "\n";
    $text .= "👤 " . BANK_HOLDER . "\n\n";
    $text .= "Setelah transfer, kirim bukti transfer (foto) ke chat ini.";
    
    $telegram->sendMessage($chat_id, $text);
    
    // Notify admin
    foreach (ADMIN_IDS as $admin_id) {
        $admin_text = "💰 Deposit Request\n\n";
        $admin_text .= "User ID: $user_id\n";
        $admin_text .= "Amount: Rp " . number_format($amount) . "\n";
        $admin_text .= "Time: " . date('Y-m-d H:i:s');
        
        $telegram->sendMessage($admin_id, $admin_text);
    }
}

/**
 * Handle deposit proof photo
 */
function handleDepositProof($chat_id, $user_id, $photos, $handlers) {
    global $db, $telegram;
    
    // Get the largest photo
    $photo = end($photos);
    $file_id = $photo['file_id'];
    
    // Update pending deposit with proof
    $deposits = $db->getPendingDeposits();
    foreach ($deposits as $deposit) {
        if ($deposit['user_id'] == $user_id) {
            // Update deposit with photo proof
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("UPDATE deposits SET proof = ? WHERE deposit_id = ?");
            $stmt->execute([$file_id, $deposit['deposit_id']]);
            
            $telegram->sendMessage($chat_id, "✅ Bukti transfer telah diterima. Admin akan memverifikasi dalam 1x24 jam.");
            
            // Notify admin
            foreach (ADMIN_IDS as $admin_id) {
                $admin_text = "📸 Bukti Transfer Diterima\n\n";
                $admin_text .= "User ID: $user_id\n";
                $admin_text .= "Amount: Rp " . number_format($deposit['amount']) . "\n";
                $admin_text .= "Deposit ID: " . $deposit['deposit_id'];
                
                $telegram->sendPhoto($admin_id, $file_id, $admin_text);
            }
            break;
        }
    }
}

/**
 * Handle phone number input for purchase
 */
function handlePhoneNumberInput($chat_id, $user_id, $phone, $handlers) {
    // This would implement the purchase flow
    // For now, just acknowledge the input
    global $telegram;
    $telegram->sendMessage($chat_id, "📱 Nomor $phone diterima. Fitur pembelian akan segera tersedia.");
}

/**
 * Show info page for GET requests
 */
function showInfoPage() {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Telegram Bot Status</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; }
        .status { color: green; font-size: 18px; margin: 20px 0; }
        .info { background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🤖 Telegram Bot</h1>
    <div class="status">✅ Bot is running!</div>
    <div class="info">
        <p>Bot untuk penjualan produk digital (pulsa & PPOB)</p>
        <p>Webhook URL: <code>' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '</code></p>
        <p>Time: ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';
}
?>