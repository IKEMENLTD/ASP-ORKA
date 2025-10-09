<?php
// エラー表示を強制的に有効化
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "=== PHP Test Script ===\n\n";

// 1. PHP情報
echo "1. PHP Version: " . PHP_VERSION . "\n";
echo "2. Error Reporting: " . error_reporting() . "\n";
echo "3. Display Errors: " . ini_get('display_errors') . "\n";
echo "4. Error Log: " . ini_get('error_log') . "\n\n";

// 2. 環境変数テスト
echo "=== Environment Variables ===\n";
$required_vars = [
    'SUPABASE_DB_HOST',
    'SUPABASE_DB_PORT',
    'SUPABASE_DB_NAME',
    'SUPABASE_DB_USER',
    'SUPABASE_DB_PASS'
];

foreach ($required_vars as $var) {
    $value = getenv($var);
    if ($value) {
        if ($var === 'SUPABASE_DB_PASS') {
            echo "$var: Set (" . strlen($value) . " chars)\n";
        } else {
            echo "$var: $value\n";
        }
    } else {
        echo "$var: NOT SET!\n";
    }
}
echo "\n";

// 3. .envファイル確認
echo "=== .env File Check ===\n";
if (file_exists('/var/www/html/.env')) {
    echo ".env file exists\n";
    $env_content = file_get_contents('/var/www/html/.env');
    $lines = explode("\n", $env_content);
    echo "Lines in .env: " . count($lines) . "\n";
} else {
    echo ".env file NOT FOUND!\n";
}
echo "\n";

// 4. データベース接続テスト
echo "=== Database Connection Test ===\n";
try {
    $host = getenv('SUPABASE_DB_HOST');
    $port = getenv('SUPABASE_DB_PORT');
    $dbname = getenv('SUPABASE_DB_NAME');
    $user = getenv('SUPABASE_DB_USER');
    $password = getenv('SUPABASE_DB_PASS');

    echo "Connecting to: $host:$port/$dbname\n";
    echo "User: $user\n";

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);

    echo "✓ Database connection successful!\n";

    // テストクエリ
    $stmt = $pdo->query('SELECT current_database(), version()');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Database: " . $result['current_database'] . "\n";
    echo "Version: " . $result['version'] . "\n";

} catch (PDOException $e) {
    echo "✗ Database connection FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
echo "\n";

// 5. ファイルシステムチェック
echo "=== File System Check ===\n";
$dirs = ['custom', 'include', 'file', 'logs', 'tdb'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "$dir: exists (" . (is_writable($dir) ? "writable" : "readonly") . ")\n";
    } else {
        echo "$dir: NOT FOUND!\n";
    }
}
echo "\n";

// 6. Include テスト
echo "=== Include Test ===\n";
$files_to_check = [
    'custom/conf.php',
    'custom/head_main.php',
    'custom/load_env.php',
    'custom/extends/sqlConf.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "$file: exists\n";
    } else {
        echo "$file: NOT FOUND!\n";
    }
}
echo "\n";

// 7. 意図的にエラーを発生させてログテスト
echo "=== Error Logging Test ===\n";
echo "Triggering a warning...\n";
trigger_error("Test warning message", E_USER_WARNING);
echo "Triggering a notice...\n";
trigger_error("Test notice message", E_USER_NOTICE);
echo "\n";

echo "=== Test Complete ===\n";
?>
