<?php
/**
 * Supabase PostgreSQL 接続テスト
 */

echo "========================================\n";
echo "  Supabase PostgreSQL 接続テスト\n";
echo "========================================\n\n";

// 環境変数読み込み
require_once 'custom/load_env.php';

echo "1. 環境変数確認...\n";
$host = getenv('SUPABASE_DB_HOST');
$port = getenv('SUPABASE_DB_PORT');
$dbname = getenv('SUPABASE_DB_NAME');
$user = getenv('SUPABASE_DB_USER');
$pass = getenv('SUPABASE_DB_PASS');

if (!$host || !$pass) {
    echo "✗ エラー: 環境変数が設定されていません\n";
    exit(1);
}

echo "  ✓ Host: {$host}\n";
echo "  ✓ Port: {$port}\n";
echo "  ✓ Database: {$dbname}\n";
echo "  ✓ User: {$user}\n";
echo "\n";

echo "2. PostgreSQL接続テスト...\n";
try {
    $connString = "host={$host} port={$port} dbname={$dbname} user={$user} password={$pass}";
    $conn = pg_connect($connString);

    if (!$conn) {
        echo "✗ 接続失敗\n";
        exit(1);
    }

    echo "  ✓ 接続成功\n";

    // エンコーディング確認
    $encoding = pg_client_encoding($conn);
    echo "  ✓ Encoding: {$encoding}\n";
    echo "\n";

    // テーブル一覧取得
    echo "3. テーブル確認...\n";
    $result = pg_query($conn, "
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        ORDER BY table_name
    ");

    $tables = [];
    while ($row = pg_fetch_assoc($result)) {
        $tables[] = $row['table_name'];
    }

    echo "  ✓ テーブル数: " . count($tables) . "件\n";
    foreach ($tables as $table) {
        echo "    - {$table}\n";
    }
    echo "\n";

    // データ取得テスト
    echo "4. データ取得テスト...\n";

    // admin テーブル
    $result = pg_query($conn, "SELECT COUNT(*) as count FROM admin");
    $row = pg_fetch_assoc($result);
    echo "  ✓ admin: {$row['count']}件\n";

    // prefectures テーブル
    $result = pg_query($conn, "SELECT COUNT(*) as count FROM prefectures");
    $row = pg_fetch_assoc($result);
    echo "  ✓ prefectures: {$row['count']}件\n";

    // template テーブル
    $result = pg_query($conn, "SELECT COUNT(*) as count FROM template");
    $row = pg_fetch_assoc($result);
    echo "  ✓ template: {$row['count']}件\n";

    echo "\n";

    // サンプルデータ取得
    echo "5. サンプルデータ取得...\n";
    $result = pg_query($conn, "SELECT id, name FROM prefectures LIMIT 5");

    while ($row = pg_fetch_assoc($result)) {
        echo "  - ID: {$row['id']}, Name: {$row['name']}\n";
    }

    pg_close($conn);

    echo "\n========================================\n";
    echo "  ✓ すべてのテスト成功\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
    exit(1);
}
?>
