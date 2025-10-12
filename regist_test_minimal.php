<?php
// MINIMAL TEST - REPLACE regist.php temporarily
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>REGIST TEST</title></head><body>";
echo "<h1>🟢 REGIST.PHP MINIMAL TEST - VERSION " . date('Y-m-d H:i:s') . "</h1>";
echo "<p>This is the MINIMAL test version of regist.php</p>";
echo "<p>GET params: " . htmlspecialchars(json_encode($_GET)) . "</p>";
echo "<p>POST params: " . htmlspecialchars(json_encode($_POST)) . "</p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Current file: " . __FILE__ . "</p>";
echo "<h2>If you see this, the deployment is working!</h2>";
echo "<p><a href='index.php'>Back to home</a></p>";
echo "</body></html>";
?>
