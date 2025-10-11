<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'custom/head_main.php';

echo "<h1>nUser Debug</h1>";

echo "<h2>1. \$gm array status:</h2>";
echo "<pre>";
echo "isset(\$gm['nUser']): " . (isset($gm['nUser']) ? 'true' : 'false') . "\n";
echo "empty(\$gm['nUser']): " . (empty($gm['nUser']) ? 'true' : 'false') . "\n";
echo "\$gm['nUser']: " . ($gm['nUser'] ? 'truthy' : 'falsy') . "\n";
echo "</pre>";

echo "<h2>2. \$TABLE_NAME contents:</h2>";
echo "<pre>";
print_r($TABLE_NAME);
echo "</pre>";

echo "<h2>3. \$THIS_TABLE_IS_NOHTML['nUser']:</h2>";
echo "<pre>";
echo "Value: " . ($THIS_TABLE_IS_NOHTML['nUser'] ? 'true' : 'false') . "\n";
echo "</pre>";

echo "<h2>4. nUser GUIManager object:</h2>";
echo "<pre>";
if (isset($gm['nUser']) && $gm['nUser']) {
    echo "Type: " . get_class($gm['nUser']) . "\n";
    echo "Column Names: ";
    print_r($gm['nUser']->colName);
} else {
    echo "nUser GUIManager is NOT set or is empty\n";
}
echo "</pre>";

echo "<h2>5. Test regist.php conditions:</h2>";
echo "<pre>";
$_GET['type'] = 'nUser';
echo "Test type: nUser\n";
echo "!isset(\$gm[\$_GET['type']]): " . (!isset($gm[$_GET['type']]) ? 'true (ERROR)' : 'false (OK)') . "\n";
echo "!\$gm[\$_GET['type']]: " . (!$gm[$_GET['type']] ? 'true (ERROR)' : 'false (OK)') . "\n";
echo "\$THIS_TABLE_IS_NOHTML[\$_GET['type']]: " . ($THIS_TABLE_IS_NOHTML[$_GET['type']] ? 'true (ERROR)' : 'false (OK)') . "\n";
echo "</pre>";

echo "<h2>6. LST file path:</h2>";
echo "<pre>";
echo "LST['nUser']: " . $LST['nUser'] . "\n";
$lst_file = PathUtil::ModifyLSTFilePath($LST['nUser']);
echo "Full path: $lst_file\n";
echo "File exists: " . (file_exists($lst_file) ? 'yes' : 'NO') . "\n";
echo "</pre>";
?>
