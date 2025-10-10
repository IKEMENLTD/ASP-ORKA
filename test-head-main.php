<?php
// Test head_main.php loading step by step
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Test head_main.php</title></head><body>\n";
echo "<h1>Testing head_main.php Loading</h1>\n";

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<p><strong>Error ($errno):</strong> $errstr in <strong>$errfile</strong> on line <strong>$errline</strong></p>\n";
    flush();
    return false; // Let PHP's default error handler also run
});

// Set exception handler
set_exception_handler(function($e) {
    echo "<h2>Uncaught Exception</h2>\n";
    echo "<p><strong>Class:</strong> " . htmlspecialchars(get_class($e)) . "</p>\n";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    flush();
});

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<h2>Fatal Error</h2>\n";
        echo "<p><strong>Type:</strong> " . $error['type'] . "</p>\n";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($error['message']) . "</p>\n";
        echo "<p><strong>File:</strong> " . htmlspecialchars($error['file']) . " (Line " . $error['line'] . ")</p>\n";
        echo "</body></html>\n";
        flush();
    }
});

echo "<p>Starting head_main.php load...</p>\n";
flush();

ob_start();

try {
    include_once 'custom/head_main.php';
    echo "<p>✓ head_main.php loaded successfully!</p>\n";
    flush();

    echo "<h2>Variables Set</h2>\n";
    echo "<p>Login User Type: " . (isset($loginUserType) ? htmlspecialchars($loginUserType) : 'NOT SET') . "</p>\n";
    echo "<p>Login User Rank: " . (isset($loginUserRank) ? htmlspecialchars($loginUserRank) : 'NOT SET') . "</p>\n";
    echo "<p>NOT_LOGIN_USER_TYPE: " . (isset($NOT_LOGIN_USER_TYPE) ? htmlspecialchars($NOT_LOGIN_USER_TYPE) : 'NOT SET') . "</p>\n";
    flush();

    echo "<h2>Global Managers</h2>\n";
    if (isset($gm) && is_array($gm)) {
        echo "<p>GM array exists with " . count($gm) . " elements</p>\n";
        echo "<p>GM keys: " . htmlspecialchars(implode(', ', array_keys($gm))) . "</p>\n";
    } else {
        echo "<p>GM not set or not an array</p>\n";
    }
    flush();

    echo "<p>✓ All checks passed!</p>\n";

} catch (Exception $e) {
    echo "<h2>Exception in head_main.php</h2>\n";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>\n";
    flush();
}

ob_end_flush();

echo "</body></html>\n";
?>
