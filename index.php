<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * index.php - 専用プログラム
	 * インデックスページを出力します。
	 *
	 * </PRE>
	 *******************************************************************************************************/

	ob_start();
	error_log("DEBUG index.php: Starting index.php execution");
	try
	{
		error_log("DEBUG index.php: About to include custom/head_main.php");
		include_once 'custom/head_main.php';
		error_log("DEBUG index.php: Successfully included custom/head_main.php");

		//紹介コード処理
		friendProc();

		switch($loginUserType)
		{
		default:
			print System::getHead($gm,$loginUserType,$loginUserRank);
			
			if( $loginUserType != $NOT_LOGIN_USER_TYPE )
				Template::drawTemplate( $gm[ $loginUserType ] , $rec , $loginUserType , $loginUserRank , '' , 'TOP_PAGE_DESIGN' );
			else
				Template::drawTemplate( $gm[ 'system' ] , $rec , $loginUserType , $loginUserRank , '' , 'TOP_PAGE_DESIGN' );
			
			print System::getFoot($gm,$loginUserType,$loginUserRank);
			break;
		}
	}
	catch( Exception $e_ )
	{
		ob_end_clean();

		//エラーメッセージをログに出力
		error_log("EXCEPTION CAUGHT in index.php: " . $e_->getMessage());
		error_log("Exception file: " . $e_->getFile());
		error_log("Exception line: " . $e_->getLine());
		error_log("Exception trace: " . $e_->getTraceAsString());

		try {
			if (class_exists('ErrorManager')) {
				$errorManager = new ErrorManager();
				$errorMessage = $errorManager->GetExceptionStr( $e_ );
				$errorManager->OutputErrorLog( $errorMessage );
			} else {
				error_log("ErrorManager class not available");
			}
		} catch (Exception $e2) {
			error_log("Exception in error handler: " . $e2->getMessage());
		}

		//例外に応じてエラーページを出力
		try {
			if (class_exists('ExceptionManager')) {
				$className = get_class( $e_ );
				ExceptionManager::DrawErrorPage($className );
			} else {
				// Fallback error page
				echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
				echo "<h1>System Error</h1>";
				echo "<p>An error occurred. Please contact the administrator.</p>";
				if (ini_get('display_errors')) {
					echo "<p><strong>Error:</strong> " . htmlspecialchars($e_->getMessage()) . "</p>";
					echo "<p><strong>File:</strong> " . htmlspecialchars($e_->getFile()) . " (Line " . $e_->getLine() . ")</p>";
				}
				echo "</body></html>";
			}
		} catch (Exception $e3) {
			// Ultimate fallback
			echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
			echo "<h1>Critical Error</h1>";
			echo "<p>A critical error occurred.</p>";
			echo "</body></html>";
		}
	}

	ob_end_flush();
?>