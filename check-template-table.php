<?php
/**
 * Check template table records
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Template Table Check</h1>\n";
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

// Check template table structure
echo "=== Template Table Structure ===\n";
$result = pg_query($conn, "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'template' ORDER BY ordinal_position");
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        echo "  - {$row['column_name']}: {$row['data_type']}\n";
    }
}

// Count records
echo "\n=== Template Table Records ===\n";
$result = pg_query($conn, "SELECT COUNT(*) as cnt FROM template");
if ($result) {
    $row = pg_fetch_assoc($result);
    echo "Total records: {$row['cnt']}\n\n";
}

// Show sample records
if ($row['cnt'] > 0) {
    echo "Sample records (first 10):\n";
    $result = pg_query($conn, "SELECT * FROM template LIMIT 10");
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            echo "\n  ID: {$row['id']}\n";
            foreach ($row as $key => $value) {
                if ($key != 'id') {
                    echo "    {$key}: " . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . "\n";
                }
            }
        }
    }
} else {
    echo "⚠️  No records found in template table!\n";
    echo "\nThis is likely why templates are not loading.\n";
    echo "The template table needs to be populated with template definitions.\n";
}

// Check for HEAD_DESIGN specifically
echo "\n=== Checking for HEAD_DESIGN Template ===\n";
$result = pg_query($conn, "SELECT * FROM template WHERE label = 'HEAD_DESIGN'");
if ($result && pg_num_rows($result) > 0) {
    echo "✓ HEAD_DESIGN template found\n";
    while ($row = pg_fetch_assoc($result)) {
        foreach ($row as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
    }
} else {
    echo "✗ HEAD_DESIGN template NOT found\n";
}

pg_close($conn);
echo "</pre>\n";
?>
