<?php
// Simple signup redirect page
// This page properly initializes session and redirects to regist.php
session_start();

// Initialize friend session if not set
if (!isset($_SESSION['friend'])) {
    $_SESSION['friend'] = '';
}

// Redirect to regist.php with nUser type
header('Location: regist.php?type=nUser');
exit();
?>
