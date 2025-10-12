<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Environment Debug</h1>";

// Check log file paths
$paths = [
    '/var/www/html/regist_debug.log',
    '/tmp/regist_debug.log',
    './regist_debug.log',
    'regist_debug.log'
];

echo "<h2>Log File Path Check:</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Path</th><th>Exists</th><th>Readable</th><th>Writable</th><th>Size</th></tr>";

foreach ($paths as $path) {
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $writable = $exists && is_writable($path);
    $size = $exists ? filesize($path) : 'N/A';
    
    echo "<tr>";
    echo "<td style='font-family: monospace;'>$path</td>";
    echo "<td>" . ($exists ? '✓' : '✗') . "</td>";
    echo "<td>" . ($readable ? '✓' : '✗') . "</td>";
    echo "<td>" . ($writable ? '✗' : '✗') . "</td>";
    echo "<td>$size</td>";
    echo "</tr>";
}
echo "</table>";

// Check directory permissions
echo "<h2>Directory Permissions:</h2>";
$dirs = ['/var/www/html', '/tmp', '.'];

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Perms</th></tr>";

foreach ($dirs as $dir) {
    $exists = is_dir($dir);
    $writable = $exists && is_writable($dir);
    $perms = $exists ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';
    
    echo "<tr>";
    echo "<td>$dir</td>";
    echo "<td>" . ($exists ? '✓' : '✗') . "</td>";
    echo "<td>" . ($writable ? '✓' : '✗') . "</td>";
    echo "<td>$perms</td>";
    echo "</tr>";
}
echo "</table>";

// Test write
echo "<h2>Write Test:</h2>";
$test_paths = ['/var/www/html/test_write.log', '/tmp/test_write.log', './test_write.log'];

foreach ($test_paths as $path) {
    $result = @file_put_contents($path, "Test write at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    if ($result !== false) {
        echo "<p style='color: green;'>✓ Successfully wrote to: $path</p>";
        @unlink($path);
    } else {
        echo "<p style='color: red;'>✗ Failed to write to: $path</p>";
    }
}

// PHP info
echo "<h2>PHP Info:</h2>";
echo "<table border='1'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>Current Working Dir</td><td>" . getcwd() . "</td></tr>";
echo "<tr><td>Script Filename</td><td>" . __FILE__ . "</td></tr>";
echo "<tr><td>User</td><td>" . get_current_user() . "</td></tr>";
echo "</table>";

// Try to access regist.php and capture output
echo "<h2>Test Registration Page Access:</h2>";
echo "<iframe src='regist.php?type=nUser' width='100%' height='400' style='border: 2px solid #ccc;'></iframe>";
?>
