<?php
/**
 * Database Migration Runner
 * Run via: https://asp-orka.onrender.com/run-migration.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Database Migration</h1>\n";
echo "<pre>\n";

// Load environment variables
require_once 'custom/load_env.php';

// PostgreSQL connection
$host = getenv('SUPABASE_DB_HOST');
$port = getenv('SUPABASE_DB_PORT') ?: '5432';
$dbname = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$user = getenv('SUPABASE_DB_USER');
$password = getenv('SUPABASE_DB_PASS');

echo "=== Connection Info ===\n";
echo "Host: {$host}\n";
echo "Port: {$port}\n";
echo "Database: {$dbname}\n";
echo "User: {$user}\n\n";

// Connect to PostgreSQL
$conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password} connect_timeout=10";
$conn = @pg_connect($conn_string);

if (!$conn) {
    echo "✗ Connection failed: " . pg_last_error() . "\n";
    exit(1);
}

echo "✓ Connected to PostgreSQL\n\n";

// Read migration SQL
$migration_file = __DIR__ . '/migration/001_create_system.sql';
if (!file_exists($migration_file)) {
    echo "✗ Migration file not found: {$migration_file}\n";
    pg_close($conn);
    exit(1);
}

$sql = file_get_contents($migration_file);
echo "=== Executing Migration ===\n";
echo "File: {$migration_file}\n\n";

// Execute migration
$result = @pg_query($conn, $sql);

if (!$result) {
    echo "✗ Migration failed: " . pg_last_error($conn) . "\n";
    pg_close($conn);
    exit(1);
}

echo "✓ Migration executed successfully\n\n";

// Verify table exists
$verify_sql = "SELECT column_name, data_type
               FROM information_schema.columns
               WHERE table_name = 'system'
               ORDER BY ordinal_position";

$result = pg_query($conn, $verify_sql);

if ($result && pg_num_rows($result) > 0) {
    echo "=== Table 'system' Columns ===\n";
    while ($row = pg_fetch_assoc($result)) {
        echo "  - {$row['column_name']}: {$row['data_type']}\n";
    }
    echo "\n✓ Table 'system' created successfully\n";
} else {
    echo "✗ Could not verify table creation\n";
}

// Check if any records exist
$count_sql = "SELECT COUNT(*) as cnt FROM system";
$result = pg_query($conn, $count_sql);
if ($result) {
    $row = pg_fetch_assoc($result);
    echo "\nRecords in system table: {$row['cnt']}\n";
}

pg_close($conn);

echo "\n=== Migration Complete ===\n";
echo "</pre>\n";
?>
