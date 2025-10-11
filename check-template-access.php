<?php
// Check template access for nUser registration
include_once 'custom/head_main.php';

echo "<h1>Template Access Debug</h1>";

echo "<h2>1. Current User Status:</h2>";
echo "<pre>";
echo "loginUserType: " . htmlspecialchars($loginUserType) . "\n";
echo "loginUserRank: " . htmlspecialchars($loginUserRank) . "\n";
echo "NOT_LOGIN_USER_TYPE: " . htmlspecialchars($NOT_LOGIN_USER_TYPE) . "\n";
echo "ACTIVE_ACTIVATE: " . htmlspecialchars($ACTIVE_ACTIVATE) . "\n";
echo "</pre>";

echo "<h2>2. Template Database Query:</h2>";
$tgm = SystemUtil::getGMforType("template");
$tdb = $tgm->getDB();

$table = $tdb->getTable();
echo "<p>Total templates: " . $tdb->getRow($table) . "</p>";

// Search for nUser REGIST_FORM_PAGE_DESIGN
$search_table = $tdb->getTable();
$search_table = $tdb->searchTable( $search_table , 'label' , '==' , 'REGIST_FORM_PAGE_DESIGN' );
echo "<p>Templates with REGIST_FORM_PAGE_DESIGN label: " . $tdb->getRow($search_table) . "</p>";

$search_table2 = $tdb->searchTable( $search_table , 'target_type' , '==' , 'nUser' );
echo "<p>...and target_type = nUser: " . $tdb->getRow($search_table2) . "</p>";

// Now try the actual search that Template::getTemplate does
$search_table3 = $tdb->getTable();
$search_table3 = $tdb->searchTable( $search_table3 , 'label' , '==' , 'REGIST_FORM_PAGE_DESIGN' );
$search_table3 = $tdb->searchTable( $search_table3 , 'user_type' , '=' , "%/".$loginUserType."/%" );
$search_table3 = $tdb->searchTable( $search_table3 , 'target_type' , '==' , 'nUser' );
$search_table3 = $tdb->searchTable( $search_table3 , 'activate' , '&' , $loginUserRank , '=');
$search_table3 = $tdb->searchTable( $search_table3 , 'owner' , '&' , 2 , '=');

echo "<p>Final filtered templates: " . $tdb->getRow($search_table3) . "</p>";

if ($tdb->getRow($search_table3) > 0) {
    echo "<h3>Found templates:</h3>";
    echo "<pre>";
    for ($i = 0; $i < $tdb->getRow($search_table3); $i++) {
        $rec = $tdb->getRecord($search_table3, $i);
        echo "Template " . ($i+1) . ":\n";
        echo "  ID: " . $tdb->getData($rec, 'id') . "\n";
        echo "  user_type: " . $tdb->getData($rec, 'user_type') . "\n";
        echo "  target_type: " . $tdb->getData($rec, 'target_type') . "\n";
        echo "  activate: " . $tdb->getData($rec, 'activate') . "\n";
        echo "  owner: " . $tdb->getData($rec, 'owner') . "\n";
        echo "  label: " . $tdb->getData($rec, 'label') . "\n";
        echo "  file: " . $tdb->getData($rec, 'file') . "\n\n";
    }
    echo "</pre>";
} else {
    echo "<p><strong>NO TEMPLATES FOUND!</strong> This is why regist.php fails.</p>";

    // Let's see what templates DO exist for nUser
    echo "<h3>All nUser templates:</h3>";
    $all_nuser = $tdb->getTable();
    $all_nuser = $tdb->searchTable( $all_nuser , 'target_type' , '==' , 'nUser' );
    echo "<pre>";
    for ($i = 0; $i < min(10, $tdb->getRow($all_nuser)); $i++) {
        $rec = $tdb->getRecord($all_nuser, $i);
        echo "Template " . ($i+1) . ":\n";
        echo "  user_type: " . $tdb->getData($rec, 'user_type') . "\n";
        echo "  label: " . $tdb->getData($rec, 'label') . "\n";
        echo "  activate: " . $tdb->getData($rec, 'activate') . "\n";
        echo "  owner: " . $tdb->getData($rec, 'owner') . "\n\n";
    }
    echo "</pre>";
}

echo "<h2>3. Test Template::getTemplate() directly:</h2>";
$result = Template::getTemplate($loginUserType, $loginUserRank, 'nUser', 'REGIST_FORM_PAGE_DESIGN', 2);
echo "<p>Result: " . ($result ? htmlspecialchars($result) : "EMPTY STRING (template not found)") . "</p>";
?>
