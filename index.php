<?php
	// 緊急デバッグモード
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);

	echo "<!-- DEBUG: index.php started -->\n";

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * index.php - 専用プログラム
	 * インデックスページを出力します。
	 *
	 * </PRE>
	 *******************************************************************************************************/

	ob_start();

	echo "<!-- DEBUG: Before try block -->\n";

	try
	{
		echo "<!-- DEBUG: Before include head_main.php -->\n";
		include_once 'custom/head_main.php';
		echo "<!-- DEBUG: After include head_main.php -->\n";

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
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );

		//例外に応じてエラーページを出力
		$className = get_class( $e_ );
		ExceptionManager::DrawErrorPage($className );
	}

	ob_end_flush();
?>