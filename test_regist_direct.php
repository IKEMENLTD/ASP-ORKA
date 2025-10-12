<?php
// Direct test - does regist.php file exist and is it readable?
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Regist.php Direct Test</h1>";

$regist_file = __DIR__ . '/regist.php';

echo "<h2>File Information:</h2>";
echo "<p>Looking for: $regist_file</p>";
echo "<p>File exists: " . (file_exists($regist_file) ? '✓ YES' : '✗ NO') . "</p>";
echo "<p>File readable: " . (is_readable($regist_file) ? '✓ YES' : '✗ NO') . "</p>";
echo "<p>File size: " . (file_exists($regist_file) ? filesize($regist_file) . ' bytes' : 'N/A') . "</p>";
echo "<p>File modified: " . (file_exists($regist_file) ? date('Y-m-d H:i:s', filemtime($regist_file)) : 'N/A') . "</p>";

if (file_exists($regist_file)) {
    echo "<h2>First 50 lines of regist.php:</h2>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto; font-size: 11px;'>";
    $lines = file($regist_file);
    echo htmlspecialchars(implode('', array_slice($lines, 0, 50)));
    echo "</pre>";
}

echo "<h2>PHP Information:</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Current Working Dir: " . getcwd() . "</p>";
echo "<p>Script Dir: " . __DIR__ . "</p>";

echo "<h2>Request Test:</h2>";
echo "<p>Attempting to access regist.php via internal request...</p>";

// Try to make internal request
$url = 'http://localhost/regist.php?type=nUser';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Status: $http_code</p>";
if ($error) {
    echo "<p style='color: red;'>cURL Error: $error</p>";
}

if ($response) {
    // Check for HTML comments
    if (preg_match_all('/<!-- (.*?) -->/', $response, $matches)) {
        echo "<h3>HTML Comments Found:</h3>";
        echo "<ul>";
        foreach ($matches[1] as $comment) {
            echo "<li>" . htmlspecialchars($comment) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No HTML comments found in response</p>";
    }

    echo "<h3>Response Preview (first 2000 chars):</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
    echo htmlspecialchars(substr($response, 0, 2000));
    echo "</pre>";
}
?>
