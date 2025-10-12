<?php
// Simple debug page - read logs and test registration
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Debug Information</h1>";

// Read debug log
$log_file = '/var/www/html/regist_debug.log';
echo "<h2>Registration Debug Log (last 100 lines):</h2>";
if (file_exists($log_file)) {
    $lines = file($log_file);
    $last_lines = array_slice($lines, -100);
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
    echo htmlspecialchars(implode('', $last_lines));
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Log file not found!</p>";
}

// Trigger new request
echo "<hr>";
echo "<h2>Test Registration Page:</h2>";
echo "<p><a href='regist.php?type=nUser' target='_blank'>→ Open Registration Page (New Window)</a></p>";
echo "<p><a href='debug_current.php'>→ Refresh This Page</a></p>";

// Check if we can access regist.php programmatically
echo "<h2>Attempting to access regist.php?type=nUser...</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/regist.php?type=nUser');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>cURL Error: " . htmlspecialchars($error) . "</p>";
} else {
    echo "<h3>Response Preview (first 2000 characters):</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
    echo htmlspecialchars(substr($response, 0, 2000));
    echo "</pre>";

    // Check for specific patterns
    if (strpos($response, 'アクセス権限がありません') !== false) {
        echo "<p style='color: red; font-weight: bold;'>⚠ Access denied error detected!</p>";
    }
    if (strpos($response, 'form') !== false || strpos($response, 'input') !== false) {
        echo "<p style='color: green; font-weight: bold;'>✓ Form elements detected!</p>";
    }
}
?>
