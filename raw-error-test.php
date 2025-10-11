<?php
// 生のエラー表示テスト
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "=== RAW ERROR TEST ===\n\n";

// 例外ハンドラを無効化
restore_exception_handler();
restore_error_handler();

// エラーログをクリアして新しいエラーのみキャッチ
$errorLog = 'logs/test-error.log';
if (file_exists($errorLog)) {
    @unlink($errorLog);
}

// カスタムエラーハンドラでキャッチ
$errors = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
    $errors[] = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    echo "ERROR: [$errno] $errstr in $errfile:$errline\n";
    return false; // 通常のエラーハンドラも実行
});

set_exception_handler(function($e) {
    echo "\n\nUNCAUGHT EXCEPTION:\n";
    echo "Class: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
});

echo "1. Including custom/head_main.php...\n";
try {
    include_once 'custom/head_main.php';
    echo "  ✓ Loaded successfully\n\n";
} catch (Throwable $e) {
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    die("Cannot continue without head_main.php\n");
}

echo "2. Checking global variables...\n";
echo "  \$gm defined: " . (isset($gm) ? "Yes" : "No") . "\n";
echo "  \$loginUserType: " . (isset($loginUserType) ? $loginUserType : "undefined") . "\n";
echo "  \$loginUserRank: " . (isset($loginUserRank) ? $loginUserRank : "undefined") . "\n";
echo "  \$NOT_LOGIN_USER_TYPE: " . (isset($NOT_LOGIN_USER_TYPE) ? $NOT_LOGIN_USER_TYPE : "undefined") . "\n\n";

echo "3. Checking if friendProc function exists...\n";
if (function_exists('friendProc')) {
    echo "  ✓ friendProc exists\n";
    try {
        friendProc();
        echo "  ✓ friendProc() executed\n\n";
    } catch (Throwable $e) {
        echo "  ✗ friendProc() failed: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "  ✗ friendProc NOT FOUND\n\n";
}

echo "4. Checking if System class exists...\n";
echo "  System class: " . (class_exists('System') ? "exists" : "NOT FOUND") . "\n\n";

echo "5. Checking if Template class exists...\n";
echo "  Template class: " . (class_exists('Template') ? "exists" : "NOT FOUND") . "\n\n";

if (!empty($errors)) {
    echo "=== ERRORS CAUGHT ===\n";
    foreach ($errors as $err) {
        echo "  [{$err['type']}] {$err['message']}\n";
        echo "    at {$err['file']}:{$err['line']}\n\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
?>
