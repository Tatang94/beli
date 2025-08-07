<?php
/**
 * Log Viewer untuk Auto Update System
 */

$auto_log = 'auto_update_log.txt';
$scheduler_log = 'scheduler_log.txt';

function readLogFile($filename, $lines = 100) {
    if (!file_exists($filename)) {
        return "Log file tidak ditemukan.";
    }
    
    $file = file($filename);
    $file = array_reverse($file);
    $file = array_slice($file, 0, $lines);
    
    return implode('', array_reverse($file));
}

$current_log = isset($_GET['log']) ? $_GET['log'] : 'auto';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Update Logs</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            background: #1e1e1e; 
            color: #d4d4d4; 
            margin: 0; 
            padding: 20px; 
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #252526;
            border-radius: 8px;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #444;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
        }
        .tab {
            background: #444;
            color: #d4d4d4;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .tab.active, .tab:hover {
            background: #007acc;
        }
        .log-container {
            background: #1e1e1e;
            border: 1px solid #444;
            border-radius: 5px;
            padding: 15px;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.4;
        }
        .timestamp {
            color: #569cd6;
        }
        .error {
            color: #f44747;
        }
        .success {
            color: #4ec9b0;
        }
        .warning {
            color: #ffc107;
        }
        .refresh-btn {
            background: #007acc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 5px;
        }
        .stats {
            background: #2d2d30;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #4ec9b0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Auto Update System Logs</h1>
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
            <button class="refresh-btn" onclick="clearLogs()">🗑️ Clear Logs</button>
        </div>
        
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo file_exists($auto_log) ? count(file($auto_log)) : 0; ?></div>
                <div>Auto Update Log Lines</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo file_exists($scheduler_log) ? count(file($scheduler_log)) : 0; ?></div>
                <div>Scheduler Log Lines</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo file_exists($auto_log) ? date('H:i:s', filemtime($auto_log)) : 'N/A'; ?></div>
                <div>Last Auto Update</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo file_exists($scheduler_log) ? date('H:i:s', filemtime($scheduler_log)) : 'N/A'; ?></div>
                <div>Last Scheduler Log</div>
            </div>
        </div>
        
        <div class="tabs">
            <a href="?log=auto" class="tab <?php echo $current_log === 'auto' ? 'active' : ''; ?>">
                Auto Update Log
            </a>
            <a href="?log=scheduler" class="tab <?php echo $current_log === 'scheduler' ? 'active' : ''; ?>">
                Scheduler Log
            </a>
        </div>
        
        <div class="log-container" id="logContainer">
            <?php
            $logContent = '';
            if ($current_log === 'auto') {
                $logContent = readLogFile($auto_log);
            } else {
                $logContent = readLogFile($scheduler_log);
            }
            
            // Highlight different log levels
            $logContent = preg_replace('/(\[[\d\-\s:]+\])/', '<span class="timestamp">$1</span>', $logContent);
            $logContent = preg_replace('/(Error|error|ERROR|Failed|failed|FAILED)/', '<span class="error">$1</span>', $logContent);
            $logContent = preg_replace('/(Success|success|SUCCESS|completed|Completed|COMPLETED)/', '<span class="success">$1</span>', $logContent);
            $logContent = preg_replace('/(Warning|warning|WARNING)/', '<span class="warning">$1</span>', $logContent);
            
            echo $logContent;
            ?>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom
        document.getElementById('logContainer').scrollTop = document.getElementById('logContainer').scrollHeight;
        
        function clearLogs() {
            if (confirm('Yakin ingin menghapus semua log?')) {
                fetch('clear_logs.php')
                    .then(() => location.reload())
                    .catch(error => alert('Error clearing logs: ' + error));
            }
        }
        
        // Auto refresh every 10 seconds
        setInterval(() => {
            location.reload();
        }, 10000);
    </script>
</body>
</html>