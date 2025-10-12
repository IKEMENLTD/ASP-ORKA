<?php
// Check all template records for nobody/nUser
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Template Configuration Check</h1>";
echo "<p>Checking templates for nobody user (loginUserRank=2)</p>";

try {
    // Database connection
    $host = getenv('SUPABASE_DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com';
    $port = getenv('SUPABASE_DB_PORT') ?: '6543';
    $dbname = getenv('SUPABASE_DB_NAME') ?: 'postgres';
    $user = getenv('SUPABASE_DB_USER') ?: 'postgres.ezucbzqzvxgcyikkrznj';
    $password = getenv('SUPABASE_DB_PASS') ?: 'akutu4256';

    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    echo "<p style='color: green;'>✓ Connected to database</p>";

    // INSERT HEAD_DESIGN and FOOT_DESIGN if missing
    if (isset($_GET['fix']) && $_GET['fix'] == '1') {
        echo "<hr><h2 style='color: blue;'>🔧 FIX MODE: Inserting Missing Templates</h2>";

        // Insert HEAD_DESIGN
        $sql_head = "INSERT INTO template (label, user_type, target_type, owner, activate, file)
                     SELECT 'HEAD_DESIGN', '/nobody/', '', '2', 15, 'base/Head.html'
                     WHERE NOT EXISTS (
                         SELECT 1 FROM template
                         WHERE label = 'HEAD_DESIGN' AND user_type LIKE '%nobody%' AND target_type = ''
                     )";
        $result_head = pg_query($conn, $sql_head);
        if ($result_head) {
            $affected = pg_affected_rows($result_head);
            if ($affected > 0) {
                echo "<p style='color: green; font-weight: bold;'>✅ Inserted HEAD_DESIGN template</p>";
            } else {
                echo "<p style='color: orange;'>⚠ HEAD_DESIGN already exists</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to insert HEAD_DESIGN: " . pg_last_error($conn) . "</p>";
        }

        // Insert FOOT_DESIGN
        $sql_foot = "INSERT INTO template (label, user_type, target_type, owner, activate, file)
                     SELECT 'FOOT_DESIGN', '/nobody/', '', '2', 15, 'base/Foot.html'
                     WHERE NOT EXISTS (
                         SELECT 1 FROM template
                         WHERE label = 'FOOT_DESIGN' AND user_type LIKE '%nobody%' AND target_type = ''
                     )";
        $result_foot = pg_query($conn, $sql_foot);
        if ($result_foot) {
            $affected = pg_affected_rows($result_foot);
            if ($affected > 0) {
                echo "<p style='color: green; font-weight: bold;'>✅ Inserted FOOT_DESIGN template</p>";
            } else {
                echo "<p style='color: orange;'>⚠ FOOT_DESIGN already exists</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to insert FOOT_DESIGN: " . pg_last_error($conn) . "</p>";
        }

        echo "<hr>";
    }

    // Check HEAD_DESIGN
    echo "<h2>HEAD_DESIGN Templates</h2>";
    $query = "SELECT id, user_type, target_type, owner, activate, label, file FROM template WHERE label = 'HEAD_DESIGN' ORDER BY id";
    $result = pg_query($conn, $query);
    $count = pg_num_rows($result);
    echo "<p>Found $count templates</p>";

    if ($count > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>file</th></tr>";
        while ($row = pg_fetch_assoc($result)) {
            $owner_color = ($row['owner'] & 2) ? 'green' : 'red';
            $activate_color = ($row['activate'] & 2) ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_type']}</td>";
            echo "<td>{$row['target_type']}</td>";
            echo "<td style='color: $owner_color; font-weight: bold;'>{$row['owner']}</td>";
            echo "<td style='color: $activate_color; font-weight: bold;'>{$row['activate']}</td>";
            echo "<td>{$row['file']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>⚠ No HEAD_DESIGN template found!</p>";
        echo "<p><a href='?fix=1' style='background: #ff6600; color: white; padding: 10px 20px; text-decoration: none; font-weight: bold;'>🔧 FIX NOW</a></p>";
    }

    // Check FOOT_DESIGN
    echo "<h2>FOOT_DESIGN Templates</h2>";
    $query = "SELECT id, user_type, target_type, owner, activate, label, file FROM template WHERE label = 'FOOT_DESIGN' ORDER BY id";
    $result = pg_query($conn, $query);
    $count = pg_num_rows($result);
    echo "<p>Found $count templates</p>";

    if ($count > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>file</th></tr>";
        while ($row = pg_fetch_assoc($result)) {
            $owner_color = ($row['owner'] & 2) ? 'green' : 'red';
            $activate_color = ($row['activate'] & 2) ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_type']}</td>";
            echo "<td>{$row['target_type']}</td>";
            echo "<td style='color: $owner_color; font-weight: bold;'>{$row['owner']}</td>";
            echo "<td style='color: $activate_color; font-weight: bold;'>{$row['activate']}</td>";
            echo "<td>{$row['file']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>⚠ No FOOT_DESIGN template found!</p>";
        echo "<p><a href='?fix=1' style='background: #ff6600; color: white; padding: 10px 20px; text-decoration: none; font-weight: bold;'>🔧 FIX NOW</a></p>";
    }

    // Check REGIST_FORM_PAGE_DESIGN
    echo "<h2>REGIST_FORM_PAGE_DESIGN Templates</h2>";
    $query = "SELECT id, user_type, target_type, owner, activate, label, file FROM template WHERE label = 'REGIST_FORM_PAGE_DESIGN' AND target_type = 'nUser' ORDER BY id";
    $result = pg_query($conn, $query);
    $count = pg_num_rows($result);
    echo "<p>Found $count templates</p>";

    if ($count > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>file</th></tr>";
        while ($row = pg_fetch_assoc($result)) {
            $owner_color = ($row['owner'] & 2) ? 'green' : 'red';
            $activate_color = ($row['activate'] & 2) ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_type']}</td>";
            echo "<td>{$row['target_type']}</td>";
            echo "<td style='color: $owner_color; font-weight: bold;'>{$row['owner']}</td>";
            echo "<td style='color: $activate_color; font-weight: bold;'>{$row['activate']}</td>";
            echo "<td>{$row['file']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>⚠ No REGIST_FORM_PAGE_DESIGN template found for nUser!</p>";
    }

    // Check EXCEPTION_DESIGN
    echo "<h2>EXCEPTION_DESIGN Templates</h2>";
    $query = "SELECT id, user_type, target_type, owner, activate, label, file FROM template WHERE label = 'EXCEPTION_DESIGN' ORDER BY id LIMIT 5";
    $result = pg_query($conn, $query);
    $count = pg_num_rows($result);
    echo "<p>Found $count templates (showing first 5)</p>";

    if ($count > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>file</th></tr>";
        while ($row = pg_fetch_assoc($result)) {
            $owner_color = ($row['owner'] & 2) ? 'green' : 'red';
            $activate_color = ($row['activate'] & 2) ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_type']}</td>";
            echo "<td>{$row['target_type']}</td>";
            echo "<td style='color: $owner_color; font-weight: bold;'>{$row['owner']}</td>";
            echo "<td style='color: $activate_color; font-weight: bold;'>{$row['activate']}</td>";
            echo "<td>{$row['file']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    pg_close($conn);

    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<p><strong>Owner & Activate Values:</strong></p>";
    echo "<ul>";
    echo "<li>Green = Bitwise AND with 2 returns TRUE (matches loginUserRank=2)</li>";
    echo "<li>Red = Bitwise AND with 2 returns FALSE (will NOT match)</li>";
    echo "</ul>";
    echo "<p><a href='regist.php?type=nUser'>→ Test Registration Page</a></p>";

} catch (Exception $e) {
    echo "<h1 style='color: red;'>Error!</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
