<?php
/**
 * Clear log files
 */

$auto_log = 'auto_update_log.txt';
$scheduler_log = 'scheduler_log.txt';

// Clear auto update log
if (file_exists($auto_log)) {
    file_put_contents($auto_log, '');
}

// Clear scheduler log
if (file_exists($scheduler_log)) {
    file_put_contents($scheduler_log, '');
}

// Add cleared log entry
$timestamp = date('Y-m-d H:i:s');
file_put_contents($auto_log, "[$timestamp] Logs cleared manually\n", FILE_APPEND | LOCK_EX);
file_put_contents($scheduler_log, "[$timestamp] Logs cleared manually\n", FILE_APPEND | LOCK_EX);

echo "Logs cleared successfully";
?>