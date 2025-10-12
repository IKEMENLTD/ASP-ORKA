<?php
// Direct SQL update to fix template owner values
// This script updates REGIST_FORM_PAGE_DESIGN templates from owner=3 to owner=2

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Template Owner Update Script</h1>";
echo "<p>Fixing ALL template owner values (changing owner=3 to owner=2)...</p>";

try {
    // Get database connection details from environment variables
    $host = getenv('SUPABASE_DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com';
    $port = getenv('SUPABASE_DB_PORT') ?: '6543';
    $dbname = getenv('SUPABASE_DB_NAME') ?: 'postgres';
    $user = getenv('SUPABASE_DB_USER') ?: 'postgres.ezucbzqzvxgcyikkrznj';
    $password = getenv('SUPABASE_DB_PASS') ?: 'akutu4256';

    echo "<h2>Step 1: Connecting to database...</h2>";
    echo "<p>Host: $host</p>";

    // Connect to PostgreSQL
    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

    if (!$conn) {
        throw new Exception("Failed to connect to database: " . pg_last_error());
    }

    echo "<p style='color: green;'>✓ Connected successfully</p>";

    // Check existing templates with owner=3
    echo "<h2>Step 2: Checking existing templates with owner=3...</h2>";
    $query = "SELECT id, user_type, target_type, owner, activate, label, file FROM template WHERE owner = 3 ORDER BY label, id";
    $result = pg_query($conn, $query);

    if (!$result) {
        throw new Exception("Query failed: " . pg_last_error($conn));
    }

    $count = pg_num_rows($result);
    echo "<p>Found $count templates with owner=3</p>";

    if ($count > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>label</th><th>file</th></tr>";

        while ($row = pg_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['target_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['owner']) . "</td>";
            echo "<td>" . htmlspecialchars($row['activate']) . "</td>";
            echo "<td>" . htmlspecialchars($row['label']) . "</td>";
            echo "<td>" . htmlspecialchars($row['file']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Update ALL templates with owner values from 3 to 2
        echo "<h2>Step 3: Updating ALL template owner values...</h2>";
        $update_query = "UPDATE template SET owner = 2 WHERE owner = 3";
        $update_result = pg_query($conn, $update_query);

        if (!$update_result) {
            throw new Exception("Update failed: " . pg_last_error($conn));
        }

        $affected = pg_affected_rows($update_result);
        echo "<p style='color: green; font-weight: bold;'>✓ Updated $affected template(s) from owner=3 to owner=2</p>";

        // Verify update - check if any owner=3 remain
        echo "<h2>Step 4: Verification...</h2>";
        $verify_query = "SELECT COUNT(*) as count FROM template WHERE owner = 3";
        $verify_result = pg_query($conn, $verify_query);
        $verify_row = pg_fetch_assoc($verify_result);
        $remaining = $verify_row['count'];

        if ($remaining == 0) {
            echo "<p style='color: green; font-weight: bold;'>✓ All templates verified! No templates with owner=3 remaining.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>⚠ Warning: $remaining template(s) still have owner=3</p>";
        }

        // Show sample of updated templates
        echo "<h3>Sample of updated templates (showing first 20):</h3>";
        $sample_query = "SELECT id, user_type, target_type, owner, activate, label, file FROM template WHERE owner = 2 ORDER BY label, id LIMIT 20";
        $sample_result = pg_query($conn, $sample_query);

        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>label</th><th>file</th></tr>";

        while ($row = pg_fetch_assoc($sample_result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['target_type']) . "</td>";
            echo "<td style='color: green; font-weight: bold;'>" . htmlspecialchars($row['owner']) . "</td>";
            echo "<td>" . htmlspecialchars($row['activate']) . "</td>";
            echo "<td>" . htmlspecialchars($row['label']) . "</td>";
            echo "<td>" . htmlspecialchars($row['file']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No templates found. No update needed.</p>";
    }

    pg_close($conn);

    echo "<hr>";
    echo "<h2>✓ Template update completed successfully!</h2>";
    echo "<p><a href='regist.php?type=nUser'>→ Test Registration Page</a></p>";

} catch (Exception $e) {
    echo "<h1 style='color: red;'>Error!</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
