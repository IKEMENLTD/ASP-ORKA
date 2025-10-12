<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Extract ALL HTML Comments from regist.php</h1>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/regist.php?type=nUser');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status: <strong>$http_code</strong></p>";

if ($response) {
    // Extract ALL HTML comments
    if (preg_match_all('/<!--\s*(.*?)\s*-->/s', $response, $matches)) {
        echo "<h2>Found " . count($matches[1]) . " HTML Comments:</h2>";
        echo "<ol style='background: #f0f0f0; padding: 20px; font-family: monospace; font-size: 12px;'>";
        foreach ($matches[1] as $i => $comment) {
            $num = $i + 1;
            $cleaned = htmlspecialchars(trim($comment));
            
            // Highlight DEBUG comments
            $color = strpos($cleaned, 'DEBUG:') !== false ? 'color: blue; font-weight: bold;' : '';
            $color = strpos($cleaned, 'STEP') !== false ? 'color: green; font-weight: bold;' : $color;
            $color = strpos($cleaned, 'EXCEPTION') !== false ? 'color: red; font-weight: bold;' : $color;
            
            echo "<li style='$color'>$cleaned</li>";
        }
        echo "</ol>";
    } else {
        echo "<p style='color: red;'>No HTML comments found</p>";
    }
    
    echo "<h2>Last Lines of Response (may show visible error):</h2>";
    $lines = explode("\n", $response);
    $last_20 = array_slice($lines, -20);
    echo "<pre style='background: #ffe6e6; padding: 10px;'>";
    echo htmlspecialchars(implode("\n", $last_20));
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Failed to get response</p>";
}
?>
