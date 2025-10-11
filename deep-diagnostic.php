<?php
// 超詳細診断ページ - エラーを全て表示
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h1>Deep Diagnostic Check</h1>";
echo "<pre style='background:#f5f5f5;padding:20px;'>";

// カスタムエラーハンドラーで全エラーをキャッチ
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "\n⚠️ ERROR CAUGHT:\n";
    echo "  Type: $errno\n";
    echo "  Message: $errstr\n";
    echo "  File: $errfile\n";
    echo "  Line: $errline\n\n";
    return true; // エラーを抑制しない
});

set_exception_handler(function($e) {
    echo "\n🔴 EXCEPTION CAUGHT:\n";
    echo "  Class: " . get_class($e) . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Trace:\n";
    foreach ($e->getTrace() as $i => $trace) {
        $file = isset($trace['file']) ? $trace['file'] : 'unknown';
        $line = isset($trace['line']) ? $trace['line'] : 'unknown';
        $func = isset($trace['function']) ? $trace['function'] : 'unknown';
        echo "    #$i $file:$line $func()\n";
    }
});

echo "1. Testing index.php inclusion...\n\n";

try {
    ob_start();
    include 'index.php';
    $output = ob_get_clean();

    echo "✓ index.php loaded successfully\n";
    echo "Output length: " . strlen($output) . " bytes\n\n";

    if (strpos($output, 'System Error') !== false) {
        echo "⚠️ WARNING: Output contains 'System Error'\n\n";
        echo "First 500 chars of output:\n";
        echo htmlspecialchars(substr($output, 0, 500)) . "\n\n";
    }

    if (empty(trim($output))) {
        echo "⚠️ WARNING: Output is empty!\n\n";
    }

} catch (Exception $e) {
    echo "✗ CAUGHT EXCEPTION in index.php:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n2. Checking for ¥ (Yen) character encoding issues...\n";
exec("grep -r $'\\xc2\\xa5' --include='*.php' . 2>/dev/null | head -20", $yenIssues);
if (!empty($yenIssues)) {
    echo "⚠️ Found " . count($yenIssues) . " files with Yen character issues:\n";
    foreach ($yenIssues as $issue) {
        echo "  " . substr($issue, 0, 100) . "\n";
    }
} else {
    echo "✓ No Yen character issues found\n";
}

echo "\n3. Checking error log tail...\n";
if (file_exists('logs/error.log')) {
    $lines = file('logs/error.log');
    $last = array_slice($lines, -10);
    echo "Last 10 lines:\n" . implode('', $last) . "\n";
} else {
    echo "No error.log found\n";
}

echo "</pre>";
?>
