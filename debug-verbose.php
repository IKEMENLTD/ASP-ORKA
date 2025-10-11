<?php
// 詳細デバッグページ - すべてのエラーを表示
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Verbose Debug</title></head><body>\n";
echo "<h1>Verbose Debug - Step by Step Loading</h1>\n";

// Custom error and exception handlers
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<div style='background:#fee;padding:10px;margin:5px;border:1px solid #f00;'>";
    echo "<strong>Error ($errno):</strong> $errstr<br>";
    echo "<strong>File:</strong> $errfile<br>";
    echo "<strong>Line:</strong> $errline<br>";
    echo "</div>\n";
    flush();
    return false;
});

set_exception_handler(function($e) {
    echo "<div style='background:#fee;padding:10px;margin:5px;border:2px solid #f00;'>";
    echo "<h2>Exception Caught</h2>";
    echo "<strong>Class:</strong> " . get_class($e) . "<br>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>\n";
    flush();
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<div style='background:#fee;padding:10px;margin:5px;border:2px solid #f00;'>";
        echo "<h2>Fatal Error</h2>";
        echo "<strong>Type:</strong> " . $error['type'] . "<br>";
        echo "<strong>Message:</strong> " . htmlspecialchars($error['message']) . "<br>";
        echo "<strong>File:</strong> " . htmlspecialchars($error['file']) . "<br>";
        echo "<strong>Line:</strong> " . $error['line'] . "<br>";
        echo "</div>\n";
        echo "</body></html>\n";
        flush();
    }
});

// Start testing
echo "<div style='background:#efe;padding:10px;margin:5px;'>";
echo "<h2>Step 1: Environment Check</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Working Directory: " . getcwd() . "</p>";
echo "</div>\n";
flush();

echo "<div style='background:#efe;padding:10px;margin:5px;'>";
echo "<h2>Step 2: Load head_main.php</h2>";
echo "<p>Attempting to include custom/head_main.php...</p>";
flush();

try {
    ob_start();
    include_once 'custom/head_main.php';
    $output = ob_get_clean();

    echo "<p style='color:green;'>✓ head_main.php loaded successfully!</p>";
    echo "<p>Output captured: " . strlen($output) . " bytes</p>";

    echo "<h3>Variables Set:</h3>";
    echo "<ul>";
    echo "<li>loginUserType: " . (isset($loginUserType) ? htmlspecialchars($loginUserType) : 'NOT SET') . "</li>";
    echo "<li>loginUserRank: " . (isset($loginUserRank) ? htmlspecialchars($loginUserRank) : 'NOT SET') . "</li>";
    echo "<li>NOT_LOGIN_USER_TYPE: " . (isset($NOT_LOGIN_USER_TYPE) ? htmlspecialchars($NOT_LOGIN_USER_TYPE) : 'NOT SET') . "</li>";
    echo "</ul>";

    echo "<h3>Classes Available:</h3>";
    echo "<ul>";
    echo "<li>System: " . (class_exists('System') ? '✓' : '✗') . "</li>";
    echo "<li>SystemUtil: " . (class_exists('SystemUtil') ? '✓' : '✗') . "</li>";
    echo "<li>Template: " . (class_exists('Template') ? '✓' : '✗') . "</li>";
    echo "<li>ErrorManager: " . (class_exists('ErrorManager') ? '✓' : '✗') . "</li>";
    echo "</ul>";

    if (isset($gm) && is_array($gm)) {
        echo "<h3>Global Manager (gm):</h3>";
        echo "<p>GM is an array with " . count($gm) . " elements</p>";
        echo "<p>Keys: " . htmlspecialchars(implode(', ', array_keys($gm))) . "</p>";
    } else {
        echo "<p style='color:red;'>GM variable not set or not an array</p>";
    }

    echo "</div>\n";

} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Exception during head_main.php loading</p>";
    echo "<p>See exception details above</p>";
    echo "</div>\n";
}

echo "<div style='background:#efe;padding:10px;margin:5px;'>";
echo "<h2>Debug Complete</h2>";
echo "<p>Check all sections above for any errors or warnings</p>";
echo "</div>\n";

echo "</body></html>\n";
?>
