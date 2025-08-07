<?php
/**
 * Simple Cron Scheduler untuk Auto Update Products
 * Script ini akan berjalan otomatis dan menjadwalkan update produk setiap 30 menit
 */

require_once 'config.php';

// Set time limit
set_time_limit(0);

function logScheduler($message) {
    $log_file = 'scheduler_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

function runProductUpdate() {
    logScheduler("Starting scheduled product update...");
    
    // Include dan jalankan auto update
    include 'auto_update_products.php';
    
    logScheduler("Scheduled product update completed");
}

function getLastUpdateTime() {
    try {
        $pdo = new PDO("sqlite:bot_database.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = 'last_product_sync'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return strtotime($result['setting_value']);
        }
    } catch (PDOException $e) {
        logScheduler("Database error: " . $e->getMessage());
    }
    
    return 0;
}

function shouldRunUpdate() {
    $last_update = getLastUpdateTime();
    $current_time = time();
    $interval = 30 * 60; // 30 menit dalam detik
    
    return ($current_time - $last_update) >= $interval;
}

// Main scheduler loop
function startScheduler() {
    logScheduler("Product Update Scheduler started");
    logScheduler("Update interval: 30 minutes");
    
    while (true) {
        try {
            if (shouldRunUpdate()) {
                runProductUpdate();
            }
            
            // Sleep selama 5 menit sebelum check lagi
            sleep(300); // 5 menit
            
        } catch (Exception $e) {
            logScheduler("Scheduler error: " . $e->getMessage());
            sleep(60); // Sleep 1 menit jika ada error
        }
    }
}

// Status checker untuk web interface
function getSchedulerStatus() {
    $last_update = getLastUpdateTime();
    $next_update = $last_update + (30 * 60);
    $current_time = time();
    
    return [
        'last_update' => $last_update ? date('Y-m-d H:i:s', $last_update) : 'Never',
        'next_update' => date('Y-m-d H:i:s', $next_update),
        'minutes_until_next' => max(0, ceil(($next_update - $current_time) / 60)),
        'is_overdue' => $current_time > $next_update
    ];
}

// Manual trigger untuk testing
function triggerManualUpdate() {
    logScheduler("Manual update triggered");
    runProductUpdate();
    return true;
}

// Jika dijalankan langsung dari command line
if (php_sapi_name() === 'cli') {
    if (isset($argv[1]) && $argv[1] === 'manual') {
        triggerManualUpdate();
    } else {
        startScheduler();
    }
}

// Jika diakses via web dengan parameter manual
if (isset($_GET['action']) && $_GET['action'] === 'manual') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => triggerManualUpdate(),
        'message' => 'Manual update triggered',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Jika diakses via web untuk status
if (isset($_GET['action']) && $_GET['action'] === 'status') {
    header('Content-Type: application/json');
    echo json_encode(getSchedulerStatus());
    exit;
}

// Default web response
if (!php_sapi_name() === 'cli') {
    $status = getSchedulerStatus();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Product Update Scheduler</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .status { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0; }
            .button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
            .button:hover { background: #005a87; }
            .overdue { color: red; font-weight: bold; }
            .normal { color: green; }
        </style>
    </head>
    <body>
        <h1>Product Update Scheduler Status</h1>
        
        <div class="status">
            <h3>Current Status</h3>
            <p><strong>Last Update:</strong> <?php echo $status['last_update']; ?></p>
            <p><strong>Next Update:</strong> <?php echo $status['next_update']; ?></p>
            <p class="<?php echo $status['is_overdue'] ? 'overdue' : 'normal'; ?>">
                <strong>Time Until Next Update:</strong> <?php echo $status['minutes_until_next']; ?> minutes
            </p>
        </div>
        
        <button class="button" onclick="triggerManualUpdate()">Trigger Manual Update</button>
        <button class="button" onclick="location.reload()">Refresh Status</button>
        
        <div id="result" style="margin-top: 20px;"></div>
        
        <script>
            function triggerManualUpdate() {
                document.getElementById('result').innerHTML = 'Triggering update...';
                fetch('?action=manual')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('result').innerHTML = 
                            '<div class="status">' + data.message + ' at ' + data.timestamp + '</div>';
                        setTimeout(() => location.reload(), 2000);
                    })
                    .catch(error => {
                        document.getElementById('result').innerHTML = 
                            '<div class="status" style="background: #ffeeee;">Error: ' + error + '</div>';
                    });
            }
        </script>
    </body>
    </html>
    <?php
}
?>