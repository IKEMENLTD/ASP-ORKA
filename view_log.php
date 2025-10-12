<?php
// Simple log viewer for debugging
$log_file = '/var/www/html/regist_debug.log';

header('Content-Type: text/plain; charset=utf-8');

if (file_exists($log_file)) {
	echo "=== REGIST DEBUG LOG ===\n";
	echo "File: $log_file\n";
	echo "Size: " . filesize($log_file) . " bytes\n";
	echo "Last modified: " . date('Y-m-d H:i:s', filemtime($log_file)) . "\n";
	echo "================================\n\n";
	echo file_get_contents($log_file);
} else {
	echo "ERROR: Log file not found at: $log_file\n";
	echo "File exists: " . (file_exists($log_file) ? 'YES' : 'NO') . "\n";
	echo "Is readable: " . (is_readable($log_file) ? 'YES' : 'NO') . "\n";
}
?>
