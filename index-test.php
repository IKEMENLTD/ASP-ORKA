<?php
// 超シンプルなテストページ
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head><title>ASP-ORKA Test</title></head>\n";
echo "<body>\n";
echo "<h1>✓ PHP is working!</h1>\n";
echo "<p>PHP Version: " . PHP_VERSION . "</p>\n";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>\n";

// 環境変数チェック
echo "<h2>Database Configuration:</h2>\n";
echo "<p>SUPABASE_DB_HOST: " . (getenv('SUPABASE_DB_HOST') ?: 'NOT SET') . "</p>\n";

// include テスト
echo "<h2>Include Test:</h2>\n";
if (file_exists('custom/conf.php')) {
    echo "<p>✓ custom/conf.php exists</p>\n";
    try {
        include_once 'custom/conf.php';
        echo "<p>✓ custom/conf.php loaded successfully</p>\n";
    } catch (Exception $e) {
        echo "<p>✗ Error loading custom/conf.php: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p>✗ custom/conf.php NOT FOUND</p>\n";
}

echo "</body>\n";
echo "</html>\n";
?>
