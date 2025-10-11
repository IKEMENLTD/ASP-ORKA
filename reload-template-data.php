<?php
/**
 * Reload template table data from CSV
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Reload Template Data</h1>\n";
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

// Read CSV file
$csv_file = __DIR__ . '/tdb/template.csv';
if (!file_exists($csv_file)) {
    echo "✗ CSV file not found: {$csv_file}\n";
    exit(1);
}

echo "Reading template data from CSV...\n";
$csv_data = file($csv_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "Found " . count($csv_data) . " lines\n\n";

// Delete existing data
echo "Deleting existing template records...\n";
$result = pg_query($conn, "DELETE FROM template");
if (!$result) {
    echo "✗ Failed to delete: " . pg_last_error($conn) . "\n";
    exit(1);
}
echo "✓ Cleared template table\n\n";

// Insert new data
echo "Inserting template records...\n";
$success_count = 0;
$error_count = 0;

foreach ($csv_data as $line_num => $line) {
    $line = trim($line);
    if (empty($line)) continue;

    // Parse CSV line
    $fields = str_getcsv($line);

    // CSV format appears to be:
    // 0: id, 1: uuid?, 2: ???, 3: user_type, 4: target_type, 5: activate, 6: owner, 7: label, 8: file, 9: regist
    if (count($fields) < 10) {
        echo "  ⚠️  Line " . ($line_num + 1) . ": Not enough fields (" . count($fields) . ")\n";
        $error_count++;
        continue;
    }

    $id = $fields[0];
    $user_type = $fields[3];
    $target_type = $fields[4];
    $activate = $fields[5];
    $owner = $fields[6];
    $label = $fields[7];
    $file = $fields[8];
    $regist = $fields[9];

    // Convert empty strings to NULL
    $user_type = empty($user_type) ? null : $user_type;
    $target_type = empty($target_type) ? null : $target_type;
    $activate = empty($activate) ? null : intval($activate);
    $owner = empty($owner) ? null : intval($owner);
    $regist = empty($regist) ? null : intval($regist);

    // Prepare SQL
    $sql = "INSERT INTO template (id, user_type, target_type, activate, owner, label, file, regist, delete_key, shadow_id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, FALSE, NULL)";

    $result = pg_query_params($conn, $sql, [
        $id,
        $user_type,
        $target_type,
        $activate,
        $owner,
        $label,
        $file,
        $regist
    ]);

    if ($result) {
        $success_count++;
        if ($label == 'HEAD_DESIGN' || $label == 'ERROR_PAGE_DESIGN' || $label == 'FOOT_DESIGN') {
            echo "  ✓ Inserted {$label}: {$file}\n";
        }
    } else {
        echo "  ✗ Failed to insert line " . ($line_num + 1) . ": " . pg_last_error($conn) . "\n";
        echo "    Data: id={$id}, label={$label}, file={$file}\n";
        $error_count++;
    }
}

echo "\n=== Summary ===\n";
echo "Successfully inserted: {$success_count}\n";
echo "Errors: {$error_count}\n";

// Verify HEAD_DESIGN
echo "\n=== Verifying HEAD_DESIGN ===\n";
$result = pg_query($conn, "SELECT * FROM template WHERE label = 'HEAD_DESIGN'");
if ($result && pg_num_rows($result) > 0) {
    echo "✓ Found " . pg_num_rows($result) . " HEAD_DESIGN templates:\n";
    while ($row = pg_fetch_assoc($result)) {
        echo "  - ID: {$row['id']}, user_type: {$row['user_type']}, file: {$row['file']}\n";
    }
} else {
    echo "✗ HEAD_DESIGN still not found!\n";
}

pg_close($conn);
echo "\n✓ Template data reload complete!\n";
echo "</pre>\n";
?>
