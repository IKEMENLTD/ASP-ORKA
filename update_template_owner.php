<?php
// Direct SQL update to fix template owner values
// This script updates REGIST_FORM_PAGE_DESIGN templates from owner=3 to owner=2

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Template Owner Update Script</h1>";
echo "<p>Fixing REGIST_FORM_PAGE_DESIGN template owner values...</p>";

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

    // Check existing templates
    echo "<h2>Step 2: Checking existing templates...</h2>";
    $query = "SELECT id, user_type, target_type, owner, activate, label, file FROM template WHERE label = 'REGIST_FORM_PAGE_DESIGN' ORDER BY id";
    $result = pg_query($conn, $query);

    if (!$result) {
        throw new Exception("Query failed: " . pg_last_error($conn));
    }

    $count = pg_num_rows($result);
    echo "<p>Found $count templates with label REGIST_FORM_PAGE_DESIGN</p>";

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

        // Update owner values from 3 to 2
        echo "<h2>Step 3: Updating owner values...</h2>";
        $update_query = "UPDATE template SET owner = 2 WHERE label = 'REGIST_FORM_PAGE_DESIGN' AND owner = 3";
        $update_result = pg_query($conn, $update_query);

        if (!$update_result) {
            throw new Exception("Update failed: " . pg_last_error($conn));
        }

        $affected = pg_affected_rows($update_result);
        echo "<p style='color: green; font-weight: bold;'>✓ Updated $affected template(s) from owner=3 to owner=2</p>";

        // Verify update
        echo "<h2>Step 4: Verification...</h2>";
        $verify_query = "SELECT id, user_type, target_type, owner, activate, label, file FROM template WHERE label = 'REGIST_FORM_PAGE_DESIGN' ORDER BY id";
        $verify_result = pg_query($conn, $verify_query);

        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>label</th><th>file</th></tr>";

        while ($row = pg_fetch_assoc($verify_result)) {
            $owner_color = ($row['owner'] == 2) ? 'green' : 'red';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['target_type']) . "</td>";
            echo "<td style='color: $owner_color; font-weight: bold;'>" . htmlspecialchars($row['owner']) . "</td>";
            echo "<td>" . htmlspecialchars($row['activate']) . "</td>";
            echo "<td>" . htmlspecialchars($row['label']) . "</td>";
            echo "<td>" . htmlspecialchars($row['file']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<p style='color: green; font-weight: bold;'>✓ All templates verified!</p>";
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
