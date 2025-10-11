<?php
// Simple error log viewer
header('Content-Type: text/plain; charset=utf-8');

// Try different possible log locations
$logLocations = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    'logs/error.log',
    '/tmp/php-errors.log',
    ini_get('error_log'),
    '/opt/render/project/src/logs/error.log'
];

echo "=== PHP Error Log Viewer ===\n\n";
echo "Looking for error logs in common locations...\n\n";

foreach ($logLocations as $location) {
    if ($location && file_exists($location) && is_readable($location)) {
        echo "Found log at: $location\n";
        echo "Last 100 lines:\n";
        echo str_repeat("=", 80) . "\n";
        $lines = file($location);
        $lines = array_slice($lines, -100);
        foreach ($lines as $line) {
            if (strpos($line, 'DEBUG:') !== false || strpos($line, 'ERROR') !== false || strpos($line, 'WARNING') !== false) {
                echo $line;
            }
        }
        echo "\n" . str_repeat("=", 80) . "\n\n";
    }
}

echo "\nPHP error_log setting: " . ini_get('error_log') . "\n";
echo "Display errors: " . ini_get('display_errors') . "\n";
echo "Log errors: " . ini_get('log_errors') . "\n";
?>
