<?php
// Direct database insertion for REGIST_FORM_PAGE_DESIGN template
include_once 'custom/head_main.php';

try {
	$tgm = SystemUtil::getGMforType("template");
	$tdb = $tgm->getDB();

	// Check existing templates
	echo "<h1>Checking existing templates...</h1>";
	$check = $tdb->getTable();
	$check = $tdb->searchTable($check, 'label', '==', 'REGIST_FORM_PAGE_DESIGN');
	echo "<p>Found " . $tdb->getRow($check) . " templates with label REGIST_FORM_PAGE_DESIGN</p>";

	if ($tdb->getRow($check) > 0) {
		echo "<h2>Existing templates:</h2>";
		echo "<table border='1'>";
		echo "<tr><th>id</th><th>user_type</th><th>target_type</th><th>owner</th><th>activate</th><th>label</th><th>file</th></tr>";
		while ($rec = $tdb->getFirstRecord($check)) {
			echo "<tr>";
			echo "<td>" . $tdb->getData($rec, 'id') . "</td>";
			echo "<td>" . $tdb->getData($rec, 'user_type') . "</td>";
			echo "<td>" . $tdb->getData($rec, 'target_type') . "</td>";
			echo "<td>" . $tdb->getData($rec, 'owner') . "</td>";
			echo "<td>" . $tdb->getData($rec, 'activate') . "</td>";
			echo "<td>" . $tdb->getData($rec, 'label') . "</td>";
			echo "<td>" . $tdb->getData($rec, 'file') . "</td>";
			echo "</tr>";
			$check = $tdb->delFirstRecord($check);
		}
		echo "</table>";

		// Delete old templates
		echo "<h2>Deleting old templates...</h2>";
		$check = $tdb->getTable();
		$check = $tdb->searchTable($check, 'label', '==', 'REGIST_FORM_PAGE_DESIGN');
		while ($tdb->getRow($check) > 0) {
			$rec = $tdb->getFirstRecord($check);
			$id = $tdb->getData($rec, 'id');
			$tdb->deleteRecord($id);
			echo "<p>Deleted template ID: $id</p>";
			$check = $tdb->getTable();
			$check = $tdb->searchTable($check, 'label', '==', 'REGIST_FORM_PAGE_DESIGN');
		}
	}

	// Insert new template with correct settings
	echo "<h2>Inserting new template...</h2>";
	$new_rec = $tdb->getNewRecord();
	$tdb->setData($new_rec, 'user_type', '//');
	$tdb->setData($new_rec, 'target_type', 'nUser');
	$tdb->setData($new_rec, 'activate', 15);  // ALL bits set
	$tdb->setData($new_rec, 'owner', 2);
	$tdb->setData($new_rec, 'label', 'REGIST_FORM_PAGE_DESIGN');
	$tdb->setData($new_rec, 'file', 'nUser/Regist.html');
	$tdb->setData($new_rec, 'sort', 999);
	$tdb->addRecord($new_rec);

	echo "<p style='color: green; font-weight: bold;'>✓ Template inserted successfully!</p>";

	// Verify
	$check = $tdb->getTable();
	$check = $tdb->searchTable($check, 'label', '==', 'REGIST_FORM_PAGE_DESIGN');
	echo "<h2>Verification:</h2>";
	echo "<p>Found " . $tdb->getRow($check) . " templates after insertion</p>";

	if ($tdb->getRow($check) > 0) {
		$rec = $tdb->getFirstRecord($check);
		echo "<pre>";
		echo "id: " . $tdb->getData($rec, 'id') . "\n";
		echo "user_type: " . $tdb->getData($rec, 'user_type') . "\n";
		echo "target_type: " . $tdb->getData($rec, 'target_type') . "\n";
		echo "owner: " . $tdb->getData($rec, 'owner') . "\n";
		echo "activate: " . $tdb->getData($rec, 'activate') . "\n";
		echo "label: " . $tdb->getData($rec, 'label') . "\n";
		echo "file: " . $tdb->getData($rec, 'file') . "\n";
		echo "sort: " . $tdb->getData($rec, 'sort') . "\n";
		echo "</pre>";
	}

	echo "<hr>";
	echo "<p><a href='regist.php?type=nUser'>→ 新規会員登録ページをテスト</a></p>";

} catch (Exception $e) {
	echo "<h1 style='color: red;'>Error!</h1>";
	echo "<p>" . $e->getMessage() . "</p>";
	echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
