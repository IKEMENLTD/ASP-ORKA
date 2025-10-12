<?php
// Simple template data check
header('Content-Type: text/html; charset=utf-8');

try {
    $host = 'aws-0-ap-northeast-1.pooler.supabase.com';
    $port = '6543';
    $dbname = 'postgres';
    $user = 'postgres.ezucbzqzvxgcyikkrznj';
    $password = 'akutu4256';

    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    if (!$conn) die("Connection failed");

    echo "<h1>Template Data Check</h1>";

    // Simple query without type casting
    $query = "SELECT * FROM template WHERE label = 'REGIST_FORM_PAGE_DESIGN' LIMIT 5";
    $result = pg_query($conn, $query);

    echo "<h2>REGIST_FORM_PAGE_DESIGN Templates:</h2>";
    echo "<table border='1' style='border-collapse: collapse; font-family: monospace;'>";

    $first = true;
    while ($row = pg_fetch_assoc($result)) {
        if ($first) {
            echo "<tr style='background: #ddd;'>";
            foreach (array_keys($row) as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            $first = false;
        }

        echo "<tr>";
        foreach ($row as $key => $val) {
            $style = '';
            if (in_array($key, ['id', 'owner', 'activate', 'user_type', 'target_type'])) {
                $style = ' style="background: #ffffcc; font-weight: bold;"';
            }
            echo "<td$style>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // Show data types
    echo "<h2>Column Types:</h2>";
    $type_query = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'template' ORDER BY ordinal_position";
    $type_result = pg_query($conn, $type_query);

    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th></tr>";
    while ($row = pg_fetch_assoc($type_result)) {
        $style = '';
        if (in_array($row['column_name'], ['id', 'owner', 'activate'])) {
            $style = ' style="background: #ffcccc; font-weight: bold;"';
        }
        echo "<tr$style><td>{$row['column_name']}</td><td>{$row['data_type']}</td></tr>";
    }
    echo "</table>";

    pg_close($conn);

} catch (Exception $e) {
    echo "<h1>Error: " . htmlspecialchars($e->getMessage()) . "</h1>";
}
?>
