<?php
// Fix registration template access by adding explicit template entries
include_once 'custom/head_main.php';

echo "<h1>Fix Registration Template</h1>";

$tgm = SystemUtil::getGMforType("template");
$tdb = $tgm->getDB();

// Check if template already exists for //,nUser,REGIST_FORM_PAGE_DESIGN
$check_table = $tdb->getTable();
$check_table = $tdb->searchTable($check_table, 'user_type', '==', '//');
$check_table = $tdb->searchTable($check_table, 'target_type', '==', 'nUser');
$check_table = $tdb->searchTable($check_table, 'label', '==', 'REGIST_FORM_PAGE_DESIGN');

if ($tdb->getRow($check_table) == 0) {
    echo "<p>Adding fallback template for nUser registration (user_type: //)</p>";

    // Add a fallback template that matches ANY user type
    $new_rec = $tdb->getNewRecord();
    $tdb->setData($new_rec, 'id', '999'); // Use a high ID to avoid conflicts
    $tdb->setData($new_rec, 'user_type', '//'); // Match ALL user types
    $tdb->setData($new_rec, 'target_type', 'nUser');
    $tdb->setData($new_rec, 'activate', 15); // All activation statuses
    $tdb->setData($new_rec, 'owner', 3); // All owner statuses (1+2=3)
    $tdb->setData($new_rec, 'label', 'REGIST_FORM_PAGE_DESIGN');
    $tdb->setData($new_rec, 'file', 'nUser/Regist.html');
    $tdb->setData($new_rec, 'sort', 999);

    $tdb->addRecord($new_rec);
    echo "<p style='color: green;'>✓ Added fallback template</p>";
} else {
    echo "<p>Fallback template already exists</p>";
}

// Also add templates for CHECK, COMP, and ERROR
$labels_and_files = [
    'REGIST_CHECK_PAGE_DESIGN' => 'nUser/RegistCheck.html',
    'REGIST_COMP_PAGE_DESIGN' => 'nUser/RegistComp.html',
    'REGIST_ERROR_DESIGN' => 'nUser/RegistFailed.html',
];

foreach ($labels_and_files as $label => $file) {
    $check_table = $tdb->getTable();
    $check_table = $tdb->searchTable($check_table, 'user_type', '==', '//');
    $check_table = $tdb->searchTable($check_table, 'target_type', '==', 'nUser');
    $check_table = $tdb->searchTable($check_table, 'label', '==', $label);

    if ($tdb->getRow($check_table) == 0) {
        echo "<p>Adding fallback template for $label</p>";

        $new_rec = $tdb->getNewRecord();
        $id = 1000 + array_search($label, array_keys($labels_and_files));
        $tdb->setData($new_rec, 'id', strval($id));
        $tdb->setData($new_rec, 'user_type', '//');
        $tdb->setData($new_rec, 'target_type', 'nUser');
        $tdb->setData($new_rec, 'activate', 15);
        $tdb->setData($new_rec, 'owner', 3);
        $tdb->setData($new_rec, 'label', $label);
        $tdb->setData($new_rec, 'file', $file);
        $tdb->setData($new_rec, 'sort', $id);

        $tdb->addRecord($new_rec);
        echo "<p style='color: green;'>✓ Added $label template</p>";
    }
}

echo "<h2>Verification</h2>";
echo "<p>Testing Template::getTemplate() for nUser registration...</p>";

$result = Template::getTemplate('nobody', 2, 'nUser', 'REGIST_FORM_PAGE_DESIGN', 2);
if ($result) {
    echo "<p style='color: green;'>✓ SUCCESS! Template found: " . htmlspecialchars($result) . "</p>";
    echo "<p><a href='regist.php?type=nUser' style='font-size: 18px; font-weight: bold;'>→ Try Registration Page Now</a></p>";
} else {
    echo "<p style='color: red;'>✗ FAILED! Template still not found</p>";
}
?>
