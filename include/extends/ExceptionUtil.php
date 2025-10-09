<?php

	/**
		@brief   例外ユーティリティクラス。
		@details 例外に関する関数をまとめたクラスです。
	*/
	class ExceptionUtil
	{
		/**
			@brief   例外エラーページを出力する。
			@details 例外の種類に応じてエラーテンプレートを出力します。¥n
			         対応するテンプレートが見つからない場合は標準のエラーテンプレートが出力されます。
			@param   $className_ 例外オブジェクトのクラス名。
			@remarks 例外エラーテンプレートはtargetに小文字のクラス名、labelにEXCEPTION_DESIGNを指定します。
		*/
		static function DrawErrorPage( $className_ )
		{
			global $gm;
			global $loginUserType;
			global $loginUserRank;

			try
			{
				ob_start();

				System::$head = false;
				System::$foot = false;

				if( $_GET[ 'type' ] && !is_array( $_GET[ 'type' ] ) && $gm[ $_GET[ 'type' ] ] )
					$tGM = SystemUtil::getGMforType( $_GET[ 'type' ] );
				else
					$tGM = SystemUtil::getGMforType( 'system' );

				print System::getHead( $gm , $loginUserType , $loginUserRank );

				//例外オブジェクトのテンプレートを検索する
				$template = Template::getTemplate( $loginUserType , $loginUserRank , $className_ , 'EXCEPTION_DESIGN' );

				if( $template && file_exists( $template ) )
					print $tGM->getString( $template );
				else
				{
					//Exceptionオブジェクトのテンプレートを検索する
					if( 'Exception' != $className_ )
						$template = Template::getTemplate( $loginUserType , $loginUserRank , 'exception' , 'EXCEPTION_DESIGN' );

					if( $template && file_exists( $template ) )
						print $tGM->getString( $template );
					else
						Template::drawErrorTemplate();
				}

				print System::getFoot( $gm , $loginUserType , $loginUserRank );

				ob_end_flush();
			}
			catch( Exception $e_ )
			{
				ob_end_clean();

				print System::getHead( $gm , $loginUserType , $loginUserRank );
				Template::drawErrorTemplate();
				print System::getFoot( $gm , $loginUserType , $loginUserRank );
			}
		}
	}
?>
