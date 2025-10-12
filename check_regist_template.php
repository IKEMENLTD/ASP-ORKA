<?php
// Check if REGIST_FORM_PAGE_DESIGN template exists

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Registration Template Check</h1>";

try {
    include_once 'custom/head_main.php';

    echo "<h2>1. Check nUser GUIManager</h2>";
    if (isset($gm['nUser'])) {
        echo "✅ gm['nUser'] exists<br>";
        echo "maxStep: " . $gm['nUser']->maxStep . "<br>";
    } else {
        echo "❌ gm['nUser'] NOT exists<br>";
    }

    echo "<h2>2. Search REGIST_FORM_PAGE_DESIGN templates</h2>";
    $tgm = SystemUtil::getGMforType("template");
    $tdb = $tgm->getDB();

    // Search for all REGIST templates
    $table = $tdb->getTable();
    $table = $tdb->searchTable( $table , 'label' , '=' , "%REGIST%" );

    echo "Found " . count($table) . " REGIST templates:<br><br>";

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #ddd;'><th>ID</th><th>Label</th><th>User Type</th><th>Target Type</th><th>Owner</th><th>Activate</th><th>File</th></tr>";

    foreach ($table as $rec) {
        $id = $tdb->getData($rec, 'id');
        $label = $tdb->getData($rec, 'label');
        $user_type = $tdb->getData($rec, 'user_type');
        $target_type = $tdb->getData($rec, 'target_type');
        $owner = $tdb->getData($rec, 'owner');
        $activate = $tdb->getData($rec, 'activate');
        $file = $tdb->getData($rec, 'file');

        echo "<tr>";
        echo "<td>$id</td>";
        echo "<td><strong>$label</strong></td>";
        echo "<td>$user_type</td>";
        echo "<td>$target_type</td>";
        echo "<td>$owner</td>";
        echo "<td>$activate</td>";
        echo "<td>$file</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h2>3. Test Template::getTemplate() for REGIST_FORM_PAGE_DESIGN</h2>";

    $test_cases = [
        ['usertype' => 'nobody', 'activate' => 2, 'target' => 'nUser', 'label' => 'REGIST_FORM_PAGE_DESIGN'],
        ['usertype' => 'nobody', 'activate' => 2, 'target' => 'nUser', 'label' => 'REGIST_FORM_PAGE_DESIGN1'],
        ['usertype' => 'nobody', 'activate' => 15, 'target' => 'nUser', 'label' => 'REGIST_FORM_PAGE_DESIGN'],
        ['usertype' => 'nobody', 'activate' => 15, 'target' => '', 'label' => 'REGIST_FORM_PAGE_DESIGN'],
    ];

    foreach ($test_cases as $i => $test) {
        echo "<h3>Test " . ($i+1) . ": usertype='{$test['usertype']}', activate={$test['activate']}, target='{$test['target']}', label='{$test['label']}'</h3>";

        $result = Template::getTemplate($test['usertype'], $test['activate'], $test['target'], $test['label']);

        if (strlen($result)) {
            echo "✅ Found: <strong>$result</strong><br>";
        } else {
            echo "❌ NOT FOUND<br>";
        }
    }

    echo "<h2>4. Check Template Database Directly</h2>";
    echo "<pre>";
    echo "SELECT * FROM template WHERE label LIKE '%REGIST%' ORDER BY label;\n\n";

    $all_table = $tdb->getTable();
    echo "Total templates in database: " . count($all_table) . "\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Exception Caught</h2>";
    echo "<p><strong>Class:</strong> " . get_class($e) . "</p>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p><a href='index.php'>Back to home</a></p>";
?>
