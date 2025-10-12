<?php
// Simple log reader
header('Content-Type: text/plain; charset=utf-8');

$log_file = '/var/www/html/regist_debug.log';

if (file_exists($log_file)) {
    echo "=== REGIST DEBUG LOG ===\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($log_file)) . "\n";
    echo "Size: " . filesize($log_file) . " bytes\n";
    echo "=======================================\n\n";

    // Get last 200 lines
    $lines = file($log_file);
    $last_lines = array_slice($lines, -200);
    echo implode('', $last_lines);
} else {
    echo "ERROR: Log file not found at: $log_file\n";
}
?>
