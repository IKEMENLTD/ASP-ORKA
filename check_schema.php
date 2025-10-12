<?php
// Check template table schema and actual data
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Template Table Schema Check</h1>";

try {
    $host = getenv('SUPABASE_DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com';
    $port = getenv('SUPABASE_DB_PORT') ?: '6543';
    $dbname = getenv('SUPABASE_DB_NAME') ?: 'postgres';
    $user = getenv('SUPABASE_DB_USER') ?: 'postgres.ezucbzqzvxgcyikkrznj';
    $password = getenv('SUPABASE_DB_PASS') ?: 'akutu4256';

    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    if (!$conn) throw new Exception("DB connection failed");

    echo "<p style='color: green;'>✓ Connected to database</p>";

    // Get table schema
    echo "<h2>Step 1: Template Table Column Types</h2>";
    $schema_query = "
        SELECT column_name, data_type, character_maximum_length
        FROM information_schema.columns
        WHERE table_name = 'template'
        ORDER BY ordinal_position
    ";
    $schema_result = pg_query($conn, $schema_query);

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column Name</th><th>Data Type</th><th>Max Length</th></tr>";
    while ($row = pg_fetch_assoc($schema_result)) {
        $highlight = '';
        if (in_array($row['column_name'], ['id', 'owner', 'activate'])) {
            $highlight = " style='background: #fff3cd; font-weight: bold;'";
        }
        echo "<tr$highlight>";
        echo "<td>{$row['column_name']}</td>";
        echo "<td>{$row['data_type']}</td>";
        echo "<td>" . ($row['character_maximum_length'] ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check REGIST_FORM_PAGE_DESIGN templates with actual data types
    echo "<h2>Step 2: REGIST_FORM_PAGE_DESIGN Templates (Raw Data)</h2>";
    $data_query = "
        SELECT id, user_type, target_type, owner, activate, label, file,
               pg_typeof(id) as id_type,
               pg_typeof(owner) as owner_type,
               pg_typeof(activate) as activate_type
        FROM template
        WHERE label = 'REGIST_FORM_PAGE_DESIGN'
        ORDER BY id::text
    ";
    $data_result = pg_query($conn, $data_query);

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>id_type</th><th>user_type</th><th>target_type</th><th>owner</th><th>owner_type</th><th>activate</th><th>activate_type</th><th>file</th></tr>";

    $templates = [];
    while ($row = pg_fetch_assoc($data_result)) {
        $templates[] = $row;
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td style='color: blue;'>{$row['id_type']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>{$row['target_type']}</td>";
        echo "<td style='font-weight: bold;'>{$row['owner']}</td>";
        echo "<td style='color: blue;'>{$row['owner_type']}</td>";
        echo "<td>{$row['activate']}</td>";
        echo "<td style='color: blue;'>{$row['activate_type']}</td>";
        echo "<td>{$row['file']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check which template matches nobody/nUser conditions
    echo "<h2>Step 3: Match Analysis for loginUserType='nobody', target='nUser'</h2>";

    foreach ($templates as $tpl) {
        echo "<div style='border: 2px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<h3>Template ID: {$tpl['id']}</h3>";
        echo "<p><strong>user_type:</strong> '{$tpl['user_type']}'</p>";
        echo "<p><strong>target_type:</strong> '{$tpl['target_type']}'</p>";
        echo "<p><strong>owner:</strong> '{$tpl['owner']}' (type: {$tpl['owner_type']})</p>";
        echo "<p><strong>activate:</strong> '{$tpl['activate']}' (type: {$tpl['activate_type']})</p>";

        // Check user_type pattern
        $user_match = (strpos($tpl['user_type'], '/nobody/') !== false);
        echo "<p>✓ user_type contains '/nobody/': " . ($user_match ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . "</p>";

        // Check target_type
        $target_match = ($tpl['target_type'] === 'nUser');
        echo "<p>✓ target_type == 'nUser': " . ($target_match ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . "</p>";

        // Check owner (if it's a string, what should match?)
        $owner_match = ($tpl['owner'] === 'nUser' || $tpl['owner'] === 'nobody');
        echo "<p>✓ owner matches 'nUser' or 'nobody': " . ($owner_match ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . "</p>";

        $all_match = $user_match && $target_match && $owner_match;
        echo "<p style='font-size: 18px; font-weight: bold;'>Overall Match: " . ($all_match ? '<span style="color: green;">✓ SHOULD WORK</span>' : '<span style="color: red;">✗ WON\'T WORK</span>') . "</p>";
        echo "</div>";
    }

    // Test the search pattern that Template.php uses
    echo "<h2>Step 4: Test Template.php Search Pattern</h2>";
    echo "<p>Simulating: getTemplate('nobody', 2, 'nUser', 'REGIST_FORM_PAGE_DESIGN', 2)</p>";

    $test_query = "
        SELECT id, user_type, target_type, owner, activate, label, file
        FROM template
        WHERE label = 'REGIST_FORM_PAGE_DESIGN'
          AND target_type = 'nUser'
          AND user_type LIKE '%/nobody/%'
        ORDER BY id::text
    ";
    $test_result = pg_query($conn, $test_query);
    $test_count = pg_num_rows($test_result);

    echo "<p>Query returned: <strong>$test_count</strong> template(s)</p>";

    if ($test_count > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>file</th></tr>";
        while ($row = pg_fetch_assoc($test_result)) {
            echo "<tr style='background: #d4edda;'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_type']}</td>";
            echo "<td>{$row['target_type']}</td>";
            echo "<td>{$row['owner']}</td>";
            echo "<td>{$row['activate']}</td>";
            echo "<td>{$row['file']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    pg_close($conn);

    echo "<hr>";
    echo "<h2>Conclusion</h2>";
    echo "<p>This shows the actual data types and values in the database.</p>";
    echo "<p><a href='regist.php?type=nUser'>→ Test Registration Page</a></p>";

} catch (Exception $e) {
    echo "<h1 style='color: red;'>Error!</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
