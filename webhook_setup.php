<?php
/**
 * Script untuk setup webhook Telegram Bot
 * Jalankan sekali setelah upload ke hosting
 */

require_once 'config.php';

function setWebhook($bot_token, $webhook_url) {
    $url = "https://api.telegram.org/bot{$bot_token}/setWebhook";
    
    $data = [
        'url' => $webhook_url,
        'max_connections' => 100,
        'allowed_updates' => json_encode(['message', 'callback_query'])
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $http_code, 'response' => json_decode($result, true)];
}

function getWebhookInfo($bot_token) {
    $url = "https://api.telegram.org/bot{$bot_token}/getWebhookInfo";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Webhook Telegram Bot</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <h1>🤖 Setup Webhook Telegram Bot</h1>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'setup'): ?>
        <h2>Mengatur Webhook...</h2>
        <?php
        $result = setWebhook(BOT_TOKEN, WEBHOOK_URL);
        
        if ($result['code'] == 200 && $result['response']['ok']) {
            echo '<div class="success">✅ Webhook berhasil diatur!</div>';
            echo '<pre>' . json_encode($result['response'], JSON_PRETTY_PRINT) . '</pre>';
        } else {
            echo '<div class="error">❌ Gagal mengatur webhook!</div>';
            echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
        }
        ?>
    <?php endif; ?>
    
    <h2>📋 Informasi Webhook Saat Ini</h2>
    <?php
    $webhook_info = getWebhookInfo(BOT_TOKEN);
    if ($webhook_info['ok']) {
        $info = $webhook_info['result'];
        echo '<pre>' . json_encode($info, JSON_PRETTY_PRINT) . '</pre>';
        
        if (empty($info['url'])) {
            echo '<div class="error">⚠️ Webhook belum diatur!</div>';
        } else {
            echo '<div class="info">✅ Webhook aktif: ' . $info['url'] . '</div>';
        }
    }
    ?>
    
    <h2>🚀 Langkah Setup</h2>
    <div class="info">
        <h3>Sebelum mengatur webhook, pastikan:</h3>
        <ol>
            <li>✅ File bot.php sudah diupload</li>
            <li>✅ Database sudah dibuat dan tabel sudah diimport</li>
            <li>✅ Config.php sudah disesuaikan dengan pengaturan hosting Anda</li>
            <li>✅ URL webhook sudah benar: <strong><?= WEBHOOK_URL ?></strong></li>
        </ol>
    </div>
    
    <p>
        <button onclick="location.href='?action=setup'">🔧 Setup Webhook</button>
        <button onclick="location.reload()">🔄 Refresh Info</button>
    </p>
    
    <h2>📝 Catatan Penting</h2>
    <div class="info">
        <ul>
            <li>Jalankan setup webhook ini hanya sekali setelah semua file diupload</li>
            <li>Pastikan SSL certificate domain Anda valid</li>
            <li>Jika webhook tidak berfungsi, cek error log di cPanel</li>
            <li>Hapus file ini setelah setup selesai untuk keamanan</li>
        </ul>
    </div>
</body>
</html>