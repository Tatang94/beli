<?php
/**
 * Script untuk setup webhook Telegram Bot
 * Jalankan sekali setelah upload ke hosting
 */

require_once '../config.php';
require_once '../includes/telegram.php';

$telegram = new TelegramAPI(BOT_TOKEN);

function testBotConnection($telegram) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getMe";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $http_code, 'response' => json_decode($result, true)];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Webhook Telegram Bot</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        .danger { background: #dc3545; }
        .danger:hover { background: #c82333; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow: auto; }
        .step { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>🤖 Setup Webhook Telegram Bot</h1>
    
    <!-- Test Bot Connection -->
    <div class="step">
        <h2>1. Test Koneksi Bot</h2>
        <?php
        $bot_test = testBotConnection($telegram);
        if ($bot_test['code'] == 200 && $bot_test['response']['ok']) {
            $bot_info = $bot_test['response']['result'];
            echo '<div class="success">✅ Bot berhasil terhubung!</div>';
            echo '<table>';
            echo '<tr><th>Bot Info</th><th>Value</th></tr>';
            echo '<tr><td>Bot Name</td><td>' . htmlspecialchars($bot_info['first_name']) . '</td></tr>';
            echo '<tr><td>Username</td><td>@' . htmlspecialchars($bot_info['username']) . '</td></tr>';
            echo '<tr><td>Bot ID</td><td>' . $bot_info['id'] . '</td></tr>';
            echo '<tr><td>Can Join Groups</td><td>' . ($bot_info['can_join_groups'] ? 'Yes' : 'No') . '</td></tr>';
            echo '<tr><td>Can Read Messages</td><td>' . ($bot_info['can_read_all_group_messages'] ? 'Yes' : 'No') . '</td></tr>';
            echo '</table>';
        } else {
            echo '<div class="error">❌ Gagal terhubung ke bot!</div>';
            echo '<pre>' . json_encode($bot_test, JSON_PRETTY_PRINT) . '</pre>';
            echo '<div class="warning">⚠️ Periksa BOT_TOKEN di file config.php</div>';
        }
        ?>
    </div>
    
    <!-- Database Test -->
    <div class="step">
        <h2>2. Test Koneksi Database</h2>
        <?php
        try {
            require_once '../includes/database.php';
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // Test basic query
            $stmt = $pdo->query("SELECT 1");
            if ($stmt) {
                echo '<div class="success">✅ Database berhasil terhubung!</div>';
                
                // Check tables
                $tables = ['users', 'products', 'transactions', 'deposits', 'settings'];
                echo '<table>';
                echo '<tr><th>Table</th><th>Status</th><th>Record Count</th></tr>';
                
                foreach ($tables as $table) {
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                        $count = $stmt->fetch()['count'];
                        echo '<tr><td>' . $table . '</td><td><span style="color:green">✅ OK</span></td><td>' . $count . '</td></tr>';
                    } catch (Exception $e) {
                        echo '<tr><td>' . $table . '</td><td><span style="color:red">❌ Missing</span></td><td>-</td></tr>';
                    }
                }
                echo '</table>';
                
            } else {
                echo '<div class="error">❌ Query test gagal!</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="warning">⚠️ Periksa pengaturan database di config.php dan pastikan database sudah diimport.</div>';
        }
        ?>
    </div>
    
    <!-- Webhook Setup -->
    <?php if (isset($_GET['action']) && $_GET['action'] === 'setup'): ?>
        <div class="step">
            <h2>3. Mengatur Webhook...</h2>
            <?php
            $webhook_url = WEBHOOK_URL;
            if (strpos($webhook_url, 'domain-anda.com') !== false) {
                echo '<div class="error">❌ URL webhook masih default! Ubah WEBHOOK_URL di config.php</div>';
            } else {
                $result = $telegram->setWebhook($webhook_url);
                
                if ($result && $result['ok']) {
                    echo '<div class="success">✅ Webhook berhasil diatur!</div>';
                    echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                } else {
                    echo '<div class="error">❌ Gagal mengatur webhook!</div>';
                    echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                }
            }
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Current Webhook Info -->
    <div class="step">
        <h2>4. Informasi Webhook Saat Ini</h2>
        <?php
        $webhook_info = $telegram->getWebhookInfo();
        if ($webhook_info && $webhook_info['ok']) {
            $info = $webhook_info['result'];
            echo '<table>';
            echo '<tr><th>Property</th><th>Value</th></tr>';
            echo '<tr><td>URL</td><td>' . ($info['url'] ?: '<em>Not set</em>') . '</td></tr>';
            echo '<tr><td>Has Custom Certificate</td><td>' . ($info['has_custom_certificate'] ? 'Yes' : 'No') . '</td></tr>';
            echo '<tr><td>Pending Update Count</td><td>' . $info['pending_update_count'] . '</td></tr>';
            echo '<tr><td>Max Connections</td><td>' . ($info['max_connections'] ?? 40) . '</td></tr>';
            
            if (isset($info['last_error_date'])) {
                echo '<tr><td>Last Error Date</td><td>' . date('Y-m-d H:i:s', $info['last_error_date']) . '</td></tr>';
                echo '<tr><td>Last Error Message</td><td>' . htmlspecialchars($info['last_error_message']) . '</td></tr>';
            }
            
            if (isset($info['allowed_updates'])) {
                echo '<tr><td>Allowed Updates</td><td>' . implode(', ', $info['allowed_updates']) . '</td></tr>';
            }
            echo '</table>';
            
            if (empty($info['url'])) {
                echo '<div class="warning">⚠️ Webhook belum diatur!</div>';
            } else {
                echo '<div class="success">✅ Webhook aktif: ' . htmlspecialchars($info['url']) . '</div>';
            }
            
            if (isset($info['last_error_message'])) {
                echo '<div class="error">❌ Last Error: ' . htmlspecialchars($info['last_error_message']) . '</div>';
            }
        } else {
            echo '<div class="error">❌ Gagal mendapatkan info webhook</div>';
        }
        ?>
    </div>
    
    <!-- Configuration Check -->
    <div class="step">
        <h2>5. Pemeriksaan Konfigurasi</h2>
        <?php
        $config_ok = true;
        echo '<table>';
        echo '<tr><th>Configuration</th><th>Status</th><th>Value</th></tr>';
        
        // Check bot token
        if (BOT_TOKEN === 'YOUR_BOT_TOKEN' || empty(BOT_TOKEN)) {
            echo '<tr><td>Bot Token</td><td><span style="color:red">❌ Not Set</span></td><td>-</td></tr>';
            $config_ok = false;
        } else {
            echo '<tr><td>Bot Token</td><td><span style="color:green">✅ Set</span></td><td>' . substr(BOT_TOKEN, 0, 10) . '...</td></tr>';
        }
        
        // Check Digiflazz credentials
        if (DIGIFLAZZ_USERNAME === 'your_username' || empty(DIGIFLAZZ_USERNAME)) {
            echo '<tr><td>Digiflazz Username</td><td><span style="color:red">❌ Not Set</span></td><td>-</td></tr>';
            $config_ok = false;
        } else {
            echo '<tr><td>Digiflazz Username</td><td><span style="color:green">✅ Set</span></td><td>' . htmlspecialchars(DIGIFLAZZ_USERNAME) . '</td></tr>';
        }
        
        if (DIGIFLAZZ_KEY === 'your_api_key' || empty(DIGIFLAZZ_KEY)) {
            echo '<tr><td>Digiflazz API Key</td><td><span style="color:red">❌ Not Set</span></td><td>-</td></tr>';
            $config_ok = false;
        } else {
            echo '<tr><td>Digiflazz API Key</td><td><span style="color:green">✅ Set</span></td><td>' . substr(DIGIFLAZZ_KEY, 0, 10) . '...</td></tr>';
        }
        
        // Check admin IDs
        if (empty(ADMIN_IDS) || in_array(123456789, ADMIN_IDS)) {
            echo '<tr><td>Admin IDs</td><td><span style="color:red">❌ Default/Not Set</span></td><td>-</td></tr>';
            $config_ok = false;
        } else {
            echo '<tr><td>Admin IDs</td><td><span style="color:green">✅ Set</span></td><td>' . implode(', ', ADMIN_IDS) . '</td></tr>';
        }
        
        // Check webhook URL
        if (strpos(WEBHOOK_URL, 'domain-anda.com') !== false) {
            echo '<tr><td>Webhook URL</td><td><span style="color:red">❌ Default URL</span></td><td>' . htmlspecialchars(WEBHOOK_URL) . '</td></tr>';
            $config_ok = false;
        } else {
            echo '<tr><td>Webhook URL</td><td><span style="color:green">✅ Set</span></td><td>' . htmlspecialchars(WEBHOOK_URL) . '</td></tr>';
        }
        
        echo '</table>';
        
        if (!$config_ok) {
            echo '<div class="error">❌ Konfigurasi belum lengkap! Lengkapi file config.php terlebih dahulu.</div>';
        } else {
            echo '<div class="success">✅ Konfigurasi sudah lengkap!</div>';
        }
        ?>
    </div>
    
    <!-- Action Buttons -->
    <div class="step">
        <h2>6. Actions</h2>
        <div class="info">
            <h3>ℹ️ Petunjuk Setup</h3>
            <ol>
                <li>Pastikan semua konfigurasi sudah benar (hijau semua)</li>
                <li>Klik "Setup Webhook" untuk mengatur webhook</li>
                <li>Test bot dengan mengirim /start ke bot Anda</li>
                <li>Hapus file setup ini setelah selesai</li>
            </ol>
        </div>
        
        <p>
            <button onclick="location.href='?action=setup'" <?= $config_ok ? '' : 'disabled' ?>>
                🔧 Setup Webhook
            </button>
            <button onclick="location.reload()">🔄 Refresh Info</button>
            <button onclick="if(confirm('Yakin ingin menghapus webhook?')) location.href='?action=delete'" class="danger">
                🗑️ Delete Webhook
            </button>
        </p>
        
        <?php if (isset($_GET['action']) && $_GET['action'] === 'delete'): ?>
            <?php
            $result = $telegram->setWebhook('');
            if ($result && $result['ok']) {
                echo '<div class="success">✅ Webhook berhasil dihapus!</div>';
            } else {
                echo '<div class="error">❌ Gagal menghapus webhook!</div>';
            }
            ?>
        <?php endif; ?>
    </div>
    
    <!-- Test Bot -->
    <div class="step">
        <h2>7. Test Bot</h2>
        <div class="info">
            <p>Untuk test bot:</p>
            <ol>
                <li>Buka Telegram dan cari bot Anda: <strong>@<?= $bot_info['username'] ?? 'your_bot' ?></strong></li>
                <li>Kirim pesan <code>/start</code></li>
                <li>Bot harus merespon dengan menu utama</li>
            </ol>
        </div>
    </div>
    
    <!-- Security Notice -->
    <div class="step">
        <h2>⚠️ Keamanan</h2>
        <div class="warning">
            <h3>Penting untuk Keamanan:</h3>
            <ul>
                <li>🗑️ <strong>Hapus folder setup/ setelah selesai</strong></li>
                <li>🔒 Jangan share bot token atau API key</li>
                <li>📝 Backup database secara berkala</li>
                <li>📊 Monitor error log secara rutin</li>
            </ul>
        </div>
    </div>
</body>
</html>