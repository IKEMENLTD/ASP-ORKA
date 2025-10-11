<?php
/**
 * Test index.php initialization with detailed error reporting
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Index.php Test</h1>\n";
echo "<pre>\n";

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "\n⚠️ ERROR: [$errno] $errstr\n";
    echo "  File: $errfile:$errline\n\n";
    return false;
});

set_exception_handler(function($e) {
    echo "\n🔴 EXCEPTION: " . get_class($e) . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
});

echo "1. Testing index.php inclusion...\n\n";

try {
    ob_start();
    include 'index.php';
    $output = ob_get_clean();

    echo "✓ index.php executed\n";
    echo "Output length: " . strlen($output) . " bytes\n\n";

    if (strlen($output) > 0) {
        echo "First 500 characters:\n";
        echo htmlspecialchars(substr($output, 0, 500)) . "\n";
    } else {
        echo "⚠️ No output generated\n";
    }

} catch (Throwable $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "</pre>\n";
?>
