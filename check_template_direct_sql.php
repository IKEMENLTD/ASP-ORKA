<?php
// Direct SQL query to check templates - bypass GUIManager

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Direct SQL Template Check</h1>";

try {
    // Load database configuration
    require_once 'custom/conf.php';

    echo "<h2>Database Connection Info</h2>";
    echo "Host: $DB_HOST<br>";
    echo "Database: $DB_NAME<br>";
    echo "User: $DB_USER<br><br>";

    // Connect directly
    $dsn = "pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Database connected<br><br>";

    echo "<h2>1. Count All Templates</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM template");
    $result = $stmt->fetch();
    echo "Total templates: <strong>" . $result['count'] . "</strong><br><br>";

    echo "<h2>2. Search for REGIST Templates</h2>";
    $sql = "SELECT id, label, user_type, target_type, owner, activate, file
            FROM template
            WHERE label LIKE '%REGIST%'
            ORDER BY label";

    $stmt = $pdo->query($sql);
    $templates = $stmt->fetchAll();

    echo "Found <strong>" . count($templates) . "</strong> REGIST templates:<br><br>";

    if (count($templates) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
        echo "<tr style='background: #ddd;'><th>ID</th><th>Label</th><th>User Type</th><th>Target Type</th><th>Owner</th><th>Activate</th><th>File</th></tr>";

        foreach ($templates as $row) {
            $highlight = (strpos($row['label'], 'REGIST_FORM') !== false) ? "background: #ffffcc;" : "";
            echo "<tr style='$highlight'>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
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
        echo "<p style='color: red; font-weight: bold;'>❌ NO REGIST templates found!</p>";
    }

    echo "<h2>3. Specific Search: REGIST_FORM_PAGE_DESIGN</h2>";
    $sql = "SELECT id, label, user_type, target_type, owner, activate, file
            FROM template
            WHERE label = 'REGIST_FORM_PAGE_DESIGN'";

    $stmt = $pdo->query($sql);
    $templates = $stmt->fetchAll();

    if (count($templates) > 0) {
        echo "✅ Found <strong>" . count($templates) . "</strong> exact matches:<br><br>";
        foreach ($templates as $row) {
            echo "ID: " . $row['id'] . "<br>";
            echo "Label: <strong>" . $row['label'] . "</strong><br>";
            echo "User Type: " . $row['user_type'] . "<br>";
            echo "Target Type: " . $row['target_type'] . "<br>";
            echo "Owner: " . $row['owner'] . "<br>";
            echo "Activate: " . $row['activate'] . "<br>";
            echo "File: " . $row['file'] . "<br><br>";
        }
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ REGIST_FORM_PAGE_DESIGN NOT FOUND</p>";
    }

    echo "<h2>4. Check nUser-related Templates</h2>";
    $sql = "SELECT id, label, user_type, target_type, file
            FROM template
            WHERE target_type = 'nUser' OR user_type LIKE '%nUser%'
            ORDER BY label";

    $stmt = $pdo->query($sql);
    $templates = $stmt->fetchAll();

    echo "Found <strong>" . count($templates) . "</strong> nUser templates:<br><br>";

    if (count($templates) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 11px;'>";
        echo "<tr style='background: #ddd;'><th>ID</th><th>Label</th><th>User Type</th><th>Target Type</th><th>File</th></tr>";

        foreach ($templates as $row) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['label']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['target_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['file']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h2>5. Sample Templates (first 10)</h2>";
    $sql = "SELECT id, label, user_type, target_type, file FROM template LIMIT 10";
    $stmt = $pdo->query($sql);
    $templates = $stmt->fetchAll();

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 11px;'>";
    echo "<tr style='background: #ddd;'><th>ID</th><th>Label</th><th>User Type</th><th>Target Type</th><th>File</th></tr>";

    foreach ($templates as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['label']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['target_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['file']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Database Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Exception</h2>";
    echo "<p><strong>Class:</strong> " . get_class($e) . "</p>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p><a href='index.php'>Back to home</a></p>";
?>
