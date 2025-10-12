<?php
// Check specific template ID 112 and search conditions
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Template ID 112 & Search Condition Check</h1>";

try {
    $host = getenv('SUPABASE_DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com';
    $port = getenv('SUPABASE_DB_PORT') ?: '6543';
    $dbname = getenv('SUPABASE_DB_NAME') ?: 'postgres';
    $user = getenv('SUPABASE_DB_USER') ?: 'postgres.ezucbzqzvxgcyikkrznj';
    $password = getenv('SUPABASE_DB_PASS') ?: 'akutu4256';

    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    if (!$conn) throw new Exception("DB connection failed");

    echo "<p style='color: green;'>✓ Connected to database</p>";

    // Check Template ID 112
    echo "<h2>Step 1: Check Template ID 112</h2>";
    $query = "SELECT * FROM template WHERE id = 112";
    $result = pg_query($conn, $query);

    if ($row = pg_fetch_assoc($result)) {
        echo "<table border='1'>";
        foreach ($row as $key => $value) {
            echo "<tr><th>$key</th><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";

        // Check bitwise operations
        $owner = $row['owner'];
        $activate = $row['activate'];
        echo "<h3>Bitwise Check:</h3>";
        echo "<p>owner & 2 = " . ($owner & 2) . " (should be non-zero)</p>";
        echo "<p>activate & 2 = " . ($activate & 2) . " (should be non-zero)</p>";

        // Check user_type pattern
        $user_type = $row['user_type'];
        echo "<h3>User Type Pattern Match:</h3>";
        echo "<p>user_type value: '$user_type'</p>";
        $pattern = '%/nobody/%';
        $matches = (strpos($user_type, '/nobody/') !== false);
        echo "<p>Matches pattern '$pattern': " . ($matches ? 'YES ✓' : 'NO ✗') . "</p>";
    } else {
        echo "<p style='color: red;'>⚠ Template ID 112 not found!</p>";
    }

    // Search with exact conditions from Template.php
    echo "<h2>Step 2: Search with Template.php conditions</h2>";
    echo "<p>label = 'REGIST_FORM_PAGE_DESIGN'</p>";
    echo "<p>user_type LIKE '%/nobody/%'</p>";
    echo "<p>target_type = 'nUser'</p>";
    echo "<p>owner & 2 != 0</p>";
    echo "<p>activate & 2 != 0</p>";

    $search_query = "
        SELECT id, user_type, target_type, owner, activate, label, file
        FROM template
        WHERE label = 'REGIST_FORM_PAGE_DESIGN'
          AND user_type LIKE '%/nobody/%'
          AND target_type = 'nUser'
          AND (owner & 2) != 0
          AND (activate & 2) != 0
        ORDER BY id
    ";

    $search_result = pg_query($conn, $search_query);
    $count = pg_num_rows($search_result);

    echo "<h3>Search Results: Found $count template(s)</h3>";

    if ($count > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>label</th><th>file</th></tr>";
        while ($row = pg_fetch_assoc($search_result)) {
            echo "<tr style='background: #d4edda;'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_type']}</td>";
            echo "<td>{$row['target_type']}</td>";
            echo "<td>{$row['owner']}</td>";
            echo "<td>{$row['activate']}</td>";
            echo "<td>{$row['label']}</td>";
            echo "<td>{$row['file']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green; font-weight: bold;'>✓ Template should be found by Template::getTemplate()</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>⚠ No templates match the search conditions!</p>";
    }

    // Check all REGIST_FORM_PAGE_DESIGN templates
    echo "<h2>Step 3: All REGIST_FORM_PAGE_DESIGN Templates</h2>";
    $all_query = "SELECT id, user_type, target_type, owner, activate, file FROM template WHERE label = 'REGIST_FORM_PAGE_DESIGN' AND target_type = 'nUser' ORDER BY id";
    $all_result = pg_query($conn, $all_query);

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>file</th><th>owner&2</th><th>activate&2</th><th>LIKE check</th></tr>";
    while ($row = pg_fetch_assoc($all_result)) {
        $owner_check = ($row['owner'] & 2) != 0;
        $activate_check = ($row['activate'] & 2) != 0;
        $like_check = strpos($row['user_type'], '/nobody/') !== false;
        $all_pass = $owner_check && $activate_check && $like_check;

        $bg_color = $all_pass ? '#d4edda' : '#f8d7da';

        echo "<tr style='background: $bg_color;'>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>{$row['target_type']}</td>";
        echo "<td>{$row['owner']}</td>";
        echo "<td>{$row['activate']}</td>";
        echo "<td>{$row['file']}</td>";
        echo "<td>" . ($owner_check ? '✓' : '✗') . "</td>";
        echo "<td>" . ($activate_check ? '✓' : '✗') . "</td>";
        echo "<td>" . ($like_check ? '✓' : '✗') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    pg_close($conn);

    echo "<hr>";
    echo "<h2>Conclusion</h2>";
    echo "<p>If Step 2 shows 0 results, the template cannot be found by Template::getTemplate()</p>";
    echo "<p><a href='regist.php?type=nUser'>→ Test Registration Page</a></p>";

} catch (Exception $e) {
    echo "<h1 style='color: red;'>Error!</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
