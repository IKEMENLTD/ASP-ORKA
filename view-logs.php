<?php
// View error logs
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h1>Error Log Viewer</h1>";
echo "<pre style='background:#f5f5f5;padding:20px;'>";

$logFile = 'logs/error.log';

if (file_exists($logFile)) {
    echo "=== Last 200 lines of error.log ===\n\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -200);
    echo htmlspecialchars(implode('', $lastLines));
} else {
    echo "Error log file not found at: $logFile\n";
    echo "Current directory: " . getcwd() . "\n";

    // Check if logs directory exists
    if (is_dir('logs')) {
        echo "\nlogs/ directory exists\n";
        echo "Contents:\n";
        $files = scandir('logs');
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "  - $file\n";
            }
        }
    } else {
        echo "\nlogs/ directory does not exist\n";
    }
}

echo "</pre>";
?>
