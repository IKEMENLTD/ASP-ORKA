<?php
// 段階的デバッグ用index.php
ob_start();

echo "<!DOCTYPE html>\n";
echo "<html><head><meta charset='UTF-8'><title>ASP-ORKA</title></head><body>\n";
echo "<h1>ASP-ORKA System</h1>\n";
echo "<p>PHP Version: " . PHP_VERSION . "</p>\n";
echo "<p>System is initializing...</p>\n";

// ここでhead_main.phpをincludeしてみる
echo "<hr><p>Loading head_main.php...</p>\n";
ob_flush();
flush();

try {
    include_once 'custom/head_main.php';
    echo "<p>✓ head_main.php loaded</p>\n";

    // 簡単なテスト
    echo "<p>Database host: " . getenv('SUPABASE_DB_HOST') . "</p>\n";

} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "</body></html>\n";
ob_end_flush();
?>
