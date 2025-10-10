<?php
// デバッグ用index.php - エラーを表示
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Debug Index</title></head><body>\n";
echo "<h1>Debug Index Page</h1>\n";

ob_start();
try
{
	echo "<p>1. Loading head_main.php...</p>\n";
	flush();

	include_once 'custom/head_main.php';

	echo "<p>✓ head_main.php loaded successfully</p>\n";
	echo "<p>Login User Type: " . (isset($loginUserType) ? htmlspecialchars($loginUserType) : 'NOT SET') . "</p>\n";
	flush();

	echo "<p>2. Calling friendProc()...</p>\n";
	flush();

	//紹介コード処理
	if (function_exists('friendProc')) {
		friendProc();
		echo "<p>✓ friendProc() executed</p>\n";
	} else {
		echo "<p>✗ friendProc() not found</p>\n";
	}
	flush();

	echo "<p>3. Checking System class...</p>\n";
	flush();

	if (class_exists('System')) {
		echo "<p>✓ System class exists</p>\n";
	} else {
		echo "<p>✗ System class not found</p>\n";
	}
	flush();

	echo "<p>4. Processing switch...</p>\n";
	flush();

	switch($loginUserType)
	{
	default:
		echo "<p>5. Generating head...</p>\n";
		flush();

		print System::getHead($gm,$loginUserType,$loginUserRank);

		echo "<p>6. Drawing template...</p>\n";
		flush();

		if( $loginUserType != $NOT_LOGIN_USER_TYPE )
			Template::drawTemplate( $gm[ $loginUserType ] , $rec , $loginUserType , $loginUserRank , '' , 'TOP_PAGE_DESIGN' );
		else
			Template::drawTemplate( $gm[ 'system' ] , $rec , $loginUserType , $loginUserRank , '' , 'TOP_PAGE_DESIGN' );

		echo "<p>7. Generating foot...</p>\n";
		flush();

		print System::getFoot($gm,$loginUserType,$loginUserRank);
		break;
	}

	echo "<p>✓ All steps completed successfully</p>\n";
}
catch( Exception $e_ )
{
	ob_end_clean();

	echo "<h2>Exception Caught</h2>\n";
	echo "<p><strong>Class:</strong> " . htmlspecialchars(get_class($e_)) . "</p>\n";
	echo "<p><strong>Message:</strong> " . htmlspecialchars($e_->getMessage()) . "</p>\n";
	echo "<p><strong>File:</strong> " . htmlspecialchars($e_->getFile()) . "</p>\n";
	echo "<p><strong>Line:</strong> " . htmlspecialchars($e_->getLine()) . "</p>\n";
	echo "<pre>" . htmlspecialchars($e_->getTraceAsString()) . "</pre>\n";

	// Try to output error page
	echo "<hr>\n";
	echo "<p>Attempting to draw error page...</p>\n";

	try {
		if (class_exists('ExceptionManager')) {
			$className = get_class( $e_ );
			ExceptionManager::DrawErrorPage($className );
		} else {
			echo "<p>ExceptionManager class not found</p>\n";
		}
	} catch (Exception $e2) {
		echo "<p>Error drawing error page: " . htmlspecialchars($e2->getMessage()) . "</p>\n";
	}
}

echo "</body></html>\n";

ob_end_flush();
?>
