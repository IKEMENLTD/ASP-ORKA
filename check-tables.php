<?php
/**
 * Check existing PostgreSQL tables
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>PostgreSQL Table Check</h1>\n";
echo "<pre>\n";

require_once 'custom/load_env.php';

$host = getenv('SUPABASE_DB_HOST');
$port = getenv('SUPABASE_DB_PORT') ?: '5432';
$dbname = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$user = getenv('SUPABASE_DB_USER');
$password = getenv('SUPABASE_DB_PASS');

$conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
$conn = @pg_connect($conn_string);

if (!$conn) {
    echo "✗ Connection failed\n";
    exit(1);
}

echo "✓ Connected to PostgreSQL\n\n";

// List all tables
echo "=== All Tables ===\n";
$result = pg_query($conn, "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
if ($result) {
    $count = 0;
    while ($row = pg_fetch_assoc($result)) {
        echo "  - {$row['tablename']}\n";
        $count++;
    }
    echo "\nTotal: {$count} tables\n\n";
}

// Check if system table exists
echo "=== System Table Details ===\n";
$result = pg_query($conn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'system' ORDER BY ordinal_position");
if ($result && pg_num_rows($result) > 0) {
    echo "System table exists with columns:\n";
    while ($row = pg_fetch_assoc($result)) {
        echo "  - {$row['column_name']}: {$row['data_type']}\n";
    }
} else {
    echo "System table does NOT exist\n";
}

echo "\n=== Recommended Action ===\n";
$result = pg_query($conn, "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename = 'system'");
if ($result && pg_num_rows($result) > 0) {
    echo "DROP existing system table first, then recreate it.\n";
    echo "SQL: DROP TABLE system CASCADE;\n";
} else {
    echo "Create system table from scratch.\n";
}

pg_close($conn);
echo "</pre>\n";
?>
