<?php
// Version check file
echo "<!DOCTYPE html>\n";
echo "<html><head><title>Version Check</title></head><body>\n";
echo "<h1>Deployment Version Check</h1>\n";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>\n";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

// Check if the MobileUtil fix is deployed
$file = file_get_contents('include/extends/MobileUtil.php');
if (strpos($file, 'Handle IPv6 addresses') !== false) {
    echo "<p><strong>MobileUtil Fix:</strong> ✓ DEPLOYED (IPv6 check present)</p>\n";
} else {
    echo "<p><strong>MobileUtil Fix:</strong> ✗ NOT DEPLOYED (old version)</p>\n";
}

// Check FirePHP fix
$firephp = file_get_contents('include/extends/FirePHPCore/FirePHP.class.php');
if (strpos($firephp, '$name[0]') !== false) {
    echo "<p><strong>FirePHP Fix:</strong> ✓ DEPLOYED (square brackets)</p>\n";
} else {
    echo "<p><strong>FirePHP Fix:</strong> ✗ NOT DEPLOYED (curly braces)</p>\n";
}

// Show line 233 of MobileUtil to verify
echo "<h2>MobileUtil.php Line 233:</h2>\n";
$lines = file('include/extends/MobileUtil.php');
echo "<pre>" . htmlspecialchars($lines[232]) . "</pre>\n";

echo "</body></html>\n";
?>
