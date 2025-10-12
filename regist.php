<?php
// INDEPENDENT TEST - NO INCLUDES
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>INDEPENDENT REGIST TEST</title>
</head>
<body>
    <h1 style="color: green;">✓ INDEPENDENT REGIST.PHP TEST</h1>
    <p><strong>Version:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p><strong>This version does NOT include custom/head_main.php</strong></p>
    
    <h2>Request Information:</h2>
    <ul>
        <li><strong>GET:</strong> <?php echo htmlspecialchars(json_encode($_GET)); ?></li>
        <li><strong>POST:</strong> <?php echo htmlspecialchars(json_encode($_POST)); ?></li>
        <li><strong>COOKIE:</strong> <?php echo htmlspecialchars(json_encode($_COOKIE)); ?></li>
        <li><strong>SESSION:</strong> <?php session_start(); echo htmlspecialchars(json_encode($_SESSION)); ?></li>
        <li><strong>REQUEST_METHOD:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
        <li><strong>REQUEST_URI:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></li>
        <li><strong>HTTP_REFERER:</strong> <?php echo isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'N/A'; ?></li>
    </ul>
    
    <h2>Test Result:</h2>
    <p style="background: #d4edda; padding: 20px; border: 2px solid green; font-size: 18px;">
        <strong>✓ If you see this page, regist.php is being executed successfully!</strong><br>
        The access denial error is likely caused by code in custom/head_main.php or related includes.
    </p>
    
    <p><a href="index.php">← Back to home</a></p>
</body>
</html>
