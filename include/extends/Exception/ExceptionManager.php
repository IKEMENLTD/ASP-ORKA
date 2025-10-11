<?php

	include_once 'include/extends/Exception/Exception.php';

	/**
		@brief   例外ユーティリティクラス。
		@details 例外に関する関数をまとめたクラスです。
	*/
	class ExceptionManager
	{
		var  $_DEBUG	 = DEBUG_FLAG_EXCEPTION;

		function ExceptionHandler( $exception ){
			global $EXCEPTION_CONF;

			ob_end_clean();

			//エラーメッセージをログに出力
			$className = get_class( $exception );

			if( !in_array( $className , $EXCEPTION_CONF[ 'SecretExceptionType' ] ) )
			{
				$errorManager = new ErrorManager();
				$errorMessage = $errorManager->GetExceptionStr( $exception );
				$errorManager->OutputErrorLog( $errorMessage );
			}

			ExceptionManager::setHttpStatus( $className );

			//例外に応じてエラーページを出力
			if( $this->_DEBUG ){ d("DrawErrorPage:class ${className},message ".$exception->getMessage() ); }
			ExceptionManager::DrawErrorPage( $className );
		}

		/**
			@brief   例外エラーページを出力する。
			@details 例外の種類に応じてエラーテンプレートを出力します。\n
			         対応するテンプレートが見つからない場合は標準のエラーテンプレートが出力されます。
			@param   $className_ 例外オブジェクトのクラス名。
			@remarks 例外エラーテンプレートはtargetに小文字のクラス名、labelにEXCEPTION_DESIGNを指定します。
		*/
		static function DrawErrorPage( $className )
		{
			global $gm;
			global $loginUserType;
			global $loginUserRank;
			global $template_path;

			try
			{
				ob_start();

				// PHP 8 compatibility: Check if System class exists
				if (class_exists('System')) {
					System::$head = false;
					System::$foot = false;
				}

				// PHP 8 compatibility: Check if SystemUtil and globals exist, and if $_GET['type'] is set
				if( isset($_GET['type']) && $_GET[ 'type' ] && !is_array( $_GET[ 'type' ] ) && isset($gm) && isset($gm[ $_GET[ 'type' ] ]) && class_exists('SystemUtil') )
					$tGM = SystemUtil::getGMforType( $_GET[ 'type' ] );
				else if (class_exists('SystemUtil'))
					$tGM = SystemUtil::getGMforType( 'system' );
				else
					$tGM = null;

				// PHP 8 compatibility: Check if System class and globals exist
				if (class_exists('System') && isset($gm, $loginUserType, $loginUserRank))
					print System::getHead( $gm , $loginUserType , $loginUserRank );
				else
					echo "<!DOCTYPE html><html><head><title>System Error</title></head><body><h1>System Error</h1>";

				//例外オブジェクトのテンプレートを検索する
				
				$template = $template_path . 'other/exception/' . $className . '.html';
				
				if( !file_exists( $template ) ){
					$template = class_exists('Template') ? Template::getTemplate( $loginUserType , $loginUserRank , $className , 'EXCEPTION_DESIGN' ) : null;
				}
	
				if( $template && file_exists( $template ) )
					print $tGM->getString( $template );
				else
				{
					//Exceptionオブジェクトのテンプレートを検索する
					if( 'Exception' != $className && class_exists('Template') )
						$template = Template::getTemplate( $loginUserType , $loginUserRank , 'exception' , 'EXCEPTION_DESIGN' );
					else
						$template = null;

					if( $template && file_exists( $template ) )
						print $tGM->getString( $template );
					else
						if (class_exists('Template'))
						Template::drawErrorTemplate();
					else
						echo "<p>An error occurred. Please contact the administrator.</p>";
				}

				// PHP 8 compatibility: Check if System class exists before using it
				if (class_exists('System') && isset($gm, $loginUserType, $loginUserRank)) {
					print System::getFoot( $gm , $loginUserType , $loginUserRank );
					System::flush();
				} else {
					echo "</body></html>";
				}
			}
			catch( Exception $e_ )
			{
				ob_end_clean();

				// PHP 8 compatibility: Check if System class and globals exist
				if (class_exists('System') && isset($gm, $loginUserType, $loginUserRank))
					print System::getHead( $gm , $loginUserType , $loginUserRank );
				else
					echo "<!DOCTYPE html><html><head><title>System Error</title></head><body><h1>System Error</h1>";

				if (class_exists('Template'))
					Template::drawErrorTemplate();
				else
					echo "<p>An error occurred. Please contact the administrator.</p>";

				if (class_exists('System') && isset($gm, $loginUserType, $loginUserRank)) {
					print System::getFoot( $gm , $loginUserType , $loginUserRank );
					System::flush();
				} else {
					echo "</body></html>";
				}
			}
		}


		function setHttpStatus($className)
		{
			$header = "";
			switch($className)
			{
			case 'InvalidQueryException':
				$header = 'HTTP/1.0 400 Bad Request';
				break;
			case 'IllegalAccessException':
				$header = 'HTTP/1.0 403 Forbidden';
				break;
			case 'RecordNotFoundException':
				$header = 'HTTP/1.0 404 Not Found';
				break;
			}

			if( strlen($header) > 0 ) { header( $header ); }
		}
	}
	
	//ハンドラ登録
	function ExceptionManager_ExceptionHandler( $e )
	{
		$object = new ExceptionManager();
		$object->ExceptionHandler( $e );
	}

	set_exception_handler( 'ExceptionManager_ExceptionHandler' );

?>
