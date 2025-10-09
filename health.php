<?php
/**
 * ヘルスチェック・デバッグ用エンドポイント
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== ASP-ORKA Health Check ===\n\n";

// 1. PHP バージョン
echo "1. PHP Version: " . PHP_VERSION . "\n\n";

// 2. 環境変数チェック
echo "2. Environment Variables:\n";
$env_vars = [
    'APP_ENV',
    'APP_DEBUG',
    'SUPABASE_DB_HOST',
    'SUPABASE_DB_PORT',
    'SUPABASE_DB_NAME',
    'SUPABASE_DB_USER',
    'SUPABASE_DB_PASS',
    'SENDGRID_API_KEY',
    'SQL_PASSWORD_KEY',
    'SESSION_SECRET'
];

foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value) {
        if (in_array($var, ['SUPABASE_DB_PASS', 'SENDGRID_API_KEY', 'SQL_PASSWORD_KEY', 'SESSION_SECRET'])) {
            echo "   {$var}: Set (" . strlen($value) . " chars)\n";
        } else {
            echo "   {$var}: {$value}\n";
        }
    } else {
        echo "   {$var}: NOT SET ❌\n";
    }
}

echo "\n";

// 3. PHP拡張機能チェック
echo "3. PHP Extensions:\n";
$required_extensions = ['pgsql', 'pdo_pgsql', 'mbstring', 'curl'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "   {$ext}: " . ($loaded ? "✓" : "❌") . "\n";
}

echo "\n";

// 4. データベース接続テスト
echo "4. Database Connection Test:\n";
try {
    $host = getenv('SUPABASE_DB_HOST');
    $port = getenv('SUPABASE_DB_PORT');
    $dbname = getenv('SUPABASE_DB_NAME');
    $user = getenv('SUPABASE_DB_USER');
    $password = getenv('SUPABASE_DB_PASS');

    if (!$host || !$port || !$dbname || !$user || !$password) {
        echo "   ❌ Database credentials not configured\n";
    } else {
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        echo "   Connecting to: {$host}:{$port}/{$dbname}\n";
        echo "   User: {$user}\n";

        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);

        echo "   ✓ Database connection successful!\n";

        // テストクエリ
        $stmt = $pdo->query('SELECT version()');
        $version = $stmt->fetchColumn();
        echo "   PostgreSQL Version: {$version}\n";

        $pdo = null;
    }
} catch (PDOException $e) {
    echo "   ❌ Database connection failed!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
}

echo "\n";

// 5. ファイルパーミッション
echo "5. Directory Permissions:\n";
$dirs = ['file', 'logs', 'tdb', 'custom'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? "✓" : "❌";
        echo "   {$dir}: {$perms} {$writable}\n";
    } else {
        echo "   {$dir}: NOT FOUND ❌\n";
    }
}

echo "\n=== Health Check Complete ===\n";
?>
