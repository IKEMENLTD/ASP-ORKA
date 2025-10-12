<?php
// Fix HEAD_DESIGN and FOOT_DESIGN templates
// These are required by System::getHead() and System::getFoot()

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Fix HEAD/FOOT Templates</h1>";

try {
    // Use environment variables (Render deployment)
    $DB_HOST = getenv('SUPABASE_DB_HOST') ?: 'aws-1-ap-northeast-1.pooler.supabase.com';
    $DB_PORT = getenv('SUPABASE_DB_PORT') ?: '5432';
    $DB_NAME = getenv('SUPABASE_DB_NAME') ?: 'postgres';
    $DB_USER = getenv('SUPABASE_DB_USER') ?: 'postgres.ezucbzqzvxgcyikkrznj';
    $DB_PASS = getenv('SUPABASE_DB_PASS') ?: 'akutu4256';

    echo "<p style='font-size: 11px; color: #666;'>Using: $DB_HOST:$DB_PORT/$DB_NAME as $DB_USER</p>";

    $dsn = "pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "<p>✅ Connected to database</p>";

    // Check existing HEAD/FOOT templates
    echo "<h2>1. Check Existing HEAD/FOOT Templates</h2>";
    $stmt = $pdo->query("SELECT id, label, user_type, target_type, owner, activate, file FROM template WHERE label LIKE '%HEAD%' OR label LIKE '%FOOT%' ORDER BY label");
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($existing) > 0) {
        echo "<p>Found " . count($existing) . " existing HEAD/FOOT templates:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #ddd;'><th>ID</th><th>Label</th><th>User Type</th><th>Target Type</th><th>Owner</th><th>Activate</th><th>File</th></tr>";
        foreach ($existing as $row) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['label']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['target_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['owner']) . "</td>";
            echo "<td>" . htmlspecialchars($row['activate']) . "</td>";
            echo "<td>" . htmlspecialchars($row['file']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No HEAD/FOOT templates found</p>";
    }

    // Insert HEAD_DESIGN
    echo "<h2>2. Insert HEAD_DESIGN Template</h2>";
    $sql_head = "INSERT INTO template (label, user_type, target_type, owner, activate, file)
                 SELECT 'HEAD_DESIGN', '/nobody/', '', '2', '15', 'base/Head.html'
                 WHERE NOT EXISTS (
                     SELECT 1 FROM template
                     WHERE label = 'HEAD_DESIGN' AND user_type LIKE '%nobody%'
                 )";

    $result_head = $pdo->exec($sql_head);
    if ($result_head > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Inserted HEAD_DESIGN template (rows affected: $result_head)</p>";
    } else {
        echo "<p style='color: orange;'>⚠ HEAD_DESIGN already exists or insert failed (rows affected: 0)</p>";
    }

    // Insert FOOT_DESIGN
    echo "<h2>3. Insert FOOT_DESIGN Template</h2>";
    $sql_foot = "INSERT INTO template (label, user_type, target_type, owner, activate, file)
                 SELECT 'FOOT_DESIGN', '/nobody/', '', '2', '15', 'base/Foot.html'
                 WHERE NOT EXISTS (
                     SELECT 1 FROM template
                     WHERE label = 'FOOT_DESIGN' AND user_type LIKE '%nobody%'
                 )";

    $result_foot = $pdo->exec($sql_foot);
    if ($result_foot > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Inserted FOOT_DESIGN template (rows affected: $result_foot)</p>";
    } else {
        echo "<p style='color: orange;'>⚠ FOOT_DESIGN already exists or insert failed (rows affected: 0)</p>";
    }

    // Verify inserts
    echo "<h2>4. Verify HEAD_DESIGN and FOOT_DESIGN</h2>";
    $stmt = $pdo->query("SELECT id, label, user_type, target_type, owner, activate, file FROM template WHERE label IN ('HEAD_DESIGN', 'FOOT_DESIGN') ORDER BY label");
    $verified = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($verified) >= 2) {
        echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS! Found " . count($verified) . " templates:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #ddd;'><th>ID</th><th>Label</th><th>User Type</th><th>Target Type</th><th>Owner</th><th>Activate</th><th>File</th></tr>";
        foreach ($verified as $row) {
            echo "<tr style='background: #ccffcc;'>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['label']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['target_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['owner']) . "</td>";
            echo "<td>" . htmlspecialchars($row['activate']) . "</td>";
            echo "<td>" . htmlspecialchars($row['file']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<hr>";
        echo "<h2 style='color: green;'>✅ Fix Complete!</h2>";
        echo "<p><a href='registration.php?type=nUser' style='font-size: 18px; font-weight: bold;'>→ Test Registration Page Now</a></p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ FAILED: Expected 2 templates, found " . count($verified) . "</p>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Database Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Exception</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><a href='index.php'>Back to home</a></p>";
?>
