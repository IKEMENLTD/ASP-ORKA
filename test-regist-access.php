<?php
// Test file to check if regist.php can be accessed
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html><html><head><title>Test Regist Access</title></head><body>";
echo "<h1>Testing regist.php Access</h1>";

// Simulate accessing regist.php?type=nUser
$_GET['type'] = 'nUser';

echo "<h2>Step 1: Set \$_GET['type'] = 'nUser'</h2>";
echo "<p>OK</p>";

echo "<h2>Step 2: Include custom/head_main.php</h2>";
try {
    include_once 'custom/head_main.php';
    echo "<p>OK - head_main.php included</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</body></html>";
    exit;
}

echo "<h2>Step 3: Check \$gm array</h2>";
echo "<p>isset(\$gm): " . (isset($gm) ? 'true' : 'false') . "</p>";
echo "<p>isset(\$gm['nUser']): " . (isset($gm['nUser']) ? 'true' : 'false') . "</p>";

if (isset($gm) && is_array($gm)) {
    echo "<p>Keys in \$gm: " . implode(', ', array_keys($gm)) . "</p>";
}

echo "<h2>Step 4: Check \$THIS_TABLE_IS_NOHTML</h2>";
echo "<p>isset(\$THIS_TABLE_IS_NOHTML['nUser']): " . (isset($THIS_TABLE_IS_NOHTML['nUser']) ? 'true' : 'false') . "</p>";
if (isset($THIS_TABLE_IS_NOHTML['nUser'])) {
    echo "<p>\$THIS_TABLE_IS_NOHTML['nUser'] = " . ($THIS_TABLE_IS_NOHTML['nUser'] ? 'true' : 'false') . "</p>";
}

echo "<h2>Step 5: Check regist.php conditions</h2>";
$would_throw_exception = false;
$exception_reason = '';

if (!isset($gm['nUser']) || !$gm['nUser']) {
    $would_throw_exception = true;
    $exception_reason = 'nUser is not defined (gm not set or empty)';
}

if (isset($THIS_TABLE_IS_NOHTML['nUser']) && $THIS_TABLE_IS_NOHTML['nUser']) {
    $would_throw_exception = true;
    $exception_reason = 'nUser cannot be operated (NOHTML=true)';
}

if ($would_throw_exception) {
    echo "<p style='color: red; font-weight: bold;'>⚠ Would throw IllegalAccessException: $exception_reason</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✓ Should proceed without exception</p>";
}

echo "<h2>Conclusion</h2>";
if ($would_throw_exception) {
    echo "<p style='background: #ffe6e6; padding: 10px; border: 2px solid red;'>";
    echo "<strong>PROBLEM FOUND:</strong> regist.php would throw an exception: " . htmlspecialchars($exception_reason);
    echo "</p>";
} else {
    echo "<p style='background: #e6ffe6; padding: 10px; border: 2px solid green;'>";
    echo "<strong>NO PROBLEM:</strong> regist.php should work correctly for nUser registration.";
    echo "</p>";
}

echo "</body></html>";
?>
