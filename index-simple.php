<?php
// 超シンプルなテスト
echo "<!DOCTYPE html>\n";
echo "<html><head><title>Simple Test</title></head><body>\n";
echo "<h1>✓ PHP Works!</h1>\n";
echo "<p>If you see this, PHP is working fine.</p>\n";

// head_main.phpを読み込んでみる
echo "<h2>Testing includes:</h2>\n";
echo "<p>Attempting to include head_main.php...</p>\n";

try {
    ob_start();
    include_once 'custom/head_main.php';
    $output = ob_get_clean();
    echo "<p>✓ head_main.php loaded successfully</p>\n";
    echo "<p>Output length: " . strlen($output) . " bytes</p>\n";
} catch (Exception $e) {
    echo "<p>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "</body></html>\n";
?>
