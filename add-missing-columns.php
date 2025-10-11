<?php
/**
 * Add missing delete_key and shadow_id columns to all tables
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Add Missing Columns</h1>\n";
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

// Get all tables
$result = pg_query($conn, "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
$tables = [];
while ($row = pg_fetch_assoc($result)) {
    $tables[] = $row['tablename'];
}

echo "Found " . count($tables) . " tables\n\n";
echo "=== Adding Missing Columns ===\n\n";

$success_count = 0;
$skip_count = 0;

foreach ($tables as $table) {
    echo "Processing: {$table}\n";

    // Check if delete_key column exists
    $check_sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}' AND column_name = 'delete_key'";
    $result = pg_query($conn, $check_sql);

    if (pg_num_rows($result) == 0) {
        // Add delete_key column
        $alter_sql = "ALTER TABLE {$table} ADD COLUMN delete_key BOOLEAN DEFAULT FALSE";
        if (pg_query($conn, $alter_sql)) {
            echo "  ✓ Added delete_key column\n";

            // Create index
            $index_sql = "CREATE INDEX IF NOT EXISTS idx_{$table}_delete_key ON {$table}(delete_key)";
            pg_query($conn, $index_sql);
            echo "  ✓ Created index on delete_key\n";

            $success_count++;
        } else {
            echo "  ✗ Failed to add delete_key: " . pg_last_error($conn) . "\n";
        }
    } else {
        echo "  - delete_key already exists\n";
        $skip_count++;
    }

    // Check if shadow_id column exists
    $check_sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}' AND column_name = 'shadow_id'";
    $result = pg_query($conn, $check_sql);

    if (pg_num_rows($result) == 0) {
        // Add shadow_id column
        $alter_sql = "ALTER TABLE {$table} ADD COLUMN shadow_id INTEGER";
        if (pg_query($conn, $alter_sql)) {
            echo "  ✓ Added shadow_id column\n";

            // Create index
            $index_sql = "CREATE INDEX IF NOT EXISTS idx_{$table}_shadow_id ON {$table}(shadow_id)";
            pg_query($conn, $index_sql);
            echo "  ✓ Created index on shadow_id\n";
        } else {
            echo "  ✗ Failed to add shadow_id: " . pg_last_error($conn) . "\n";
        }
    } else {
        echo "  - shadow_id already exists\n";
    }

    echo "\n";
}

pg_close($conn);

echo "=== Summary ===\n";
echo "Tables processed: " . count($tables) . "\n";
echo "Columns added: {$success_count}\n";
echo "Already existed: {$skip_count}\n";

echo "\n✓ Migration complete!\n";
echo "</pre>\n";
?>
