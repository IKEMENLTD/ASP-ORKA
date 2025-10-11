<?php
// ステップバイステップデバッグ
ini_set('display_errors', '1');
error_reporting(E_ALL);

function test_step($step, $description, $callable) {
    echo "<div style='background:#eef;padding:10px;margin:5px;border-left:4px solid #00f;'>";
    echo "<h3>Step $step: $description</h3>";
    flush();

    try {
        $result = $callable();
        echo "<p style='color:green;'>✓ Success</p>";
        if ($result !== null) {
            echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
        }
        echo "</div>\n";
        flush();
        return true;
    } catch (Throwable $e) {
        echo "<p style='color:red;'>✗ Failed</p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
        echo "</div>\n";
        flush();
        return false;
    }
}

echo "<!DOCTYPE html><html><head><title>Step-by-Step Debug</title></head><body>\n";
echo "<h1>Step-by-Step Debug</h1>\n";
flush();

// Step 1: Check files
test_step(1, "Check if custom/head_main.php exists", function() {
    $file = 'custom/head_main.php';
    if (!file_exists($file)) {
        throw new Exception("File not found: $file");
    }
    return "File exists: " . filesize($file) . " bytes";
});

// Step 2: Check custom/conf.php
test_step(2, "Check if custom/conf.php exists", function() {
    $file = 'custom/conf.php';
    if (!file_exists($file)) {
        throw new Exception("File not found: $file");
    }
    return "File exists: " . filesize($file) . " bytes";
});

// Step 3: Include debugConf
test_step(3, "Include custom/extends/debugConf.php", function() {
    include_once "custom/extends/debugConf.php";
    return "debugConf.php loaded";
});

// Step 4: Include conf.php
test_step(4, "Include custom/conf.php", function() {
    include_once "custom/conf.php";
    return "conf.php loaded";
});

// Step 5: Include initConf
test_step(5, "Include custom/extends/initConf.php", function() {
    include_once "custom/extends/initConf.php";
    return "initConf.php loaded";
});

// Step 6: Check mobile detection
test_step(6, "Check mobile detection (MobileUtil::getTerminal)", function() {
    if (!class_exists('MobileUtil')) {
        throw new Exception("MobileUtil class not found");
    }
    $terminal = MobileUtil::getTerminal();
    return "Terminal type: $terminal";
});

// Step 7: Set session
test_step(7, "Start session", function() {
    global $CRON_SESSION_FLAG;
    if(!isset($CRON_SESSION_FLAG) || !$CRON_SESSION_FLAG){
        session_start();
        return "Session ID: " . session_id();
    }
    return "Session disabled (CRON mode)";
});

// Step 8: Include System utilities
test_step(8, "Include include/info/SystemInfo.php", function() {
    include_once "include/info/SystemInfo.php";
    return "SystemInfo.php loaded";
});

test_step(9, "Include include/Weave.php", function() {
    include_once "include/Weave.php";
    return "Weave.php loaded";
});

test_step(10, "Include include/base/Util.php", function() {
    include_once "include/base/Util.php";
    return "Util.php loaded";
});

echo "<div style='background:#efe;padding:20px;margin:10px;border:2px solid #0f0;'>";
echo "<h2>All Steps Completed Successfully!</h2>";
echo "<p>The application should now be able to load properly.</p>";
echo "</div>";

echo "</body></html>\n";
?>
