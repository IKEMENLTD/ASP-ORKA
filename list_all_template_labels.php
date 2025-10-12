<?php
// List all unique template labels in database

header('Content-Type: text/html; charset=utf-8');
echo "<h1>All Template Labels</h1>";

try {
    require_once 'custom/conf.php';

    $dsn = "pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<p>✅ Connected to database</p>";

    // Get all unique labels
    $sql = "SELECT DISTINCT label FROM template ORDER BY label";
    $stmt = $pdo->query($sql);
    $labels = $stmt->fetchAll();

    echo "<h2>Total Unique Labels: " . count($labels) . "</h2>";

    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; font-size: 13px;'>";
    echo "<tr style='background: #ddd;'><th>#</th><th>Label</th><th>Count</th></tr>";

    $i = 1;
    foreach ($labels as $row) {
        $label = $row['label'];

        // Count how many templates have this label
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM template WHERE label = ?");
        $count_stmt->execute([$label]);
        $count = $count_stmt->fetch()['cnt'];

        // Highlight important labels
        $style = "";
        if (stripos($label, 'HEAD') !== false || stripos($label, 'FOOT') !== false) {
            $style = "background: #ffffcc; font-weight: bold;";
        } else if (stripos($label, 'REGIST') !== false) {
            $style = "background: #ccffcc; font-weight: bold;";
        } else if (stripos($label, 'ERROR') !== false) {
            $style = "background: #ffcccc; font-weight: bold;";
        }

        echo "<tr style='$style'>";
        echo "<td>$i</td>";
        echo "<td>" . htmlspecialchars($label) . "</td>";
        echo "<td>$count</td>";
        echo "</tr>";
        $i++;
    }
    echo "</table>";

    echo "<h2>HEAD/FOOT Templates Detail</h2>";
    $sql = "SELECT id, label, user_type, target_type, owner, activate, file
            FROM template
            WHERE label LIKE '%HEAD%' OR label LIKE '%FOOT%'
            ORDER BY label";

    $stmt = $pdo->query($sql);
    $templates = $stmt->fetchAll();

    if (count($templates) > 0) {
        echo "<p>Found <strong>" . count($templates) . "</strong> HEAD/FOOT templates:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
        echo "<tr style='background: #ddd;'><th>ID</th><th>Label</th><th>User Type</th><th>Target Type</th><th>Owner</th><th>Activate</th><th>File</th></tr>";

        foreach ($templates as $t) {
            echo "<tr>";
            echo "<td>" . $t['id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($t['label']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($t['user_type']) . "</td>";
            echo "<td>" . htmlspecialchars($t['target_type']) . "</td>";
            echo "<td>" . htmlspecialchars($t['owner']) . "</td>";
            echo "<td>" . htmlspecialchars($t['activate']) . "</td>";
            echo "<td>" . htmlspecialchars($t['file']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ NO HEAD/FOOT templates found!</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><a href='index.php'>Back to home</a></p>";
?>
